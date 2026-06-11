<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use YiiRocks\Recaptcha\RecaptchaClient;
use YiiRocks\Recaptcha\RecaptchaConfig;
use YiiRocks\Recaptcha\RecaptchaRegistry;
use YiiRocks\Recaptcha\RecaptchaV3Rule;
use YiiRocks\Recaptcha\RecaptchaV3RuleHandler;
use Yiisoft\Validator\ValidationContext;

final class RecaptchaV3RuleHandlerTest extends TestCase
{
    private RecaptchaV3RuleHandler $handler;

    protected function setUp(): void
    {
        $factory = new Psr17Factory();

        $httpClient = $this->createStub(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn(
            new \Nyholm\Psr7\Response(200, [], $factory->createStream(json_encode([
                'success' => true,
                'score' => 0.9,
                'action' => 'submit',
            ]))),
        );

        $config = new RecaptchaConfig(secretV3: 'test-secret');
        $client = new RecaptchaClient($config, $httpClient, $factory, $factory);

        RecaptchaRegistry::configure($client);

        $this->handler = new RecaptchaV3RuleHandler();
    }

    public function testValidateSuccess(): void
    {
        $rule = new RecaptchaV3Rule();
        $result = $this->handler->validate('valid-token', $rule, new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    public function testValidateLowScore(): void
    {
        $factory = new Psr17Factory();
        $httpClient = $this->createStub(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn(
            new \Nyholm\Psr7\Response(200, [], $factory->createStream(json_encode([
                'success' => true,
                'score' => 0.3,
                'action' => 'submit',
            ]))),
        );

        $config = new RecaptchaConfig(secretV3: 'test-secret');
        $client = new RecaptchaClient($config, $httpClient, $factory, $factory);
        RecaptchaRegistry::configure($client);

        $handler = new RecaptchaV3RuleHandler();
        $rule = new RecaptchaV3Rule(threshold: 0.5);
        $result = $handler->validate('low-score-token', $rule, new ValidationContext());

        $this->assertFalse($result->isValid());
    }

    public function testValidateActionMismatch(): void
    {
        $factory = new Psr17Factory();
        $httpClient = $this->createStub(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn(
            new \Nyholm\Psr7\Response(200, [], $factory->createStream(json_encode([
                'success' => true,
                'score' => 0.9,
                'action' => 'register',
            ]))),
        );

        $config = new RecaptchaConfig(secretV3: 'test-secret');
        $client = new RecaptchaClient($config, $httpClient, $factory, $factory);
        RecaptchaRegistry::configure($client);

        $handler = new RecaptchaV3RuleHandler();
        $rule = new RecaptchaV3Rule(action: 'login');
        $result = $handler->validate('action-mismatch-token', $rule, new ValidationContext());

        $this->assertFalse($result->isValid());
    }

    public function testValidateEmptyValue(): void
    {
        $rule = new RecaptchaV3Rule();
        $result = $this->handler->validate('', $rule, new ValidationContext());

        $this->assertFalse($result->isValid());
    }

    public function testValidateFailure(): void
    {
        $factory = new Psr17Factory();
        $httpClient = $this->createStub(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn(
            new \Nyholm\Psr7\Response(200, [], $factory->createStream(json_encode([
                'success' => false,
                'error-codes' => ['timeout-or-duplicate'],
            ]))),
        );

        $config = new RecaptchaConfig(secretV3: 'test-secret');
        $client = new RecaptchaClient($config, $httpClient, $factory, $factory);
        RecaptchaRegistry::configure($client);

        $handler = new RecaptchaV3RuleHandler();
        $rule = new RecaptchaV3Rule();
        $result = $handler->validate('bad-token', $rule, new ValidationContext());

        $this->assertFalse($result->isValid());
    }

    public function testInvalidThreshold(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RecaptchaV3Rule(threshold: 1.5);
    }

    protected function tearDown(): void
    {
        RecaptchaRegistry::configure(
            new RecaptchaClient(
                new RecaptchaConfig(),
                $this->createStub(ClientInterface::class),
                new Psr17Factory(),
                new Psr17Factory(),
            ),
        );
    }
}
