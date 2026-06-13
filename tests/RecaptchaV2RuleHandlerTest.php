<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use YiiRocks\Recaptcha\RecaptchaClient;
use YiiRocks\Recaptcha\RecaptchaConfig;
use YiiRocks\Recaptcha\RecaptchaRegistry;
use YiiRocks\Recaptcha\RecaptchaV2Rule;
use YiiRocks\Recaptcha\RecaptchaV2RuleHandler;
use Yiisoft\Validator\ValidationContext;

final class RecaptchaV2RuleHandlerTest extends TestCase
{
    private RecaptchaV2RuleHandler $_handler;

    protected function setUp(): void
    {
        $factory = new Psr17Factory();

        $httpClient = $this->createStub(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn(
            new \Nyholm\Psr7\Response(200, [], $factory->createStream(json_encode([
                'success' => true,
            ]))),
        );

        $config = new RecaptchaConfig(secretV2: 'test-secret');
        $client = new RecaptchaClient($config, $httpClient, $factory, $factory);

        RecaptchaRegistry::configure($client);

        $this->_handler = new RecaptchaV2RuleHandler();
    }

    public function testValidateSuccess(): void
    {
        $rule = new RecaptchaV2Rule();
        $result = $this->_handler->validate('valid-token', $rule, new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    public function testValidateEmptyValue(): void
    {
        $rule = new RecaptchaV2Rule();
        $result = $this->_handler->validate('', $rule, new ValidationContext());

        $this->assertFalse($result->isValid());
    }

    public function testValidateNonStringValue(): void
    {
        $rule = new RecaptchaV2Rule();
        $result = $this->_handler->validate(null, $rule, new ValidationContext());

        $this->assertFalse($result->isValid());
    }

    public function testValidateFailure(): void
    {
        $factory = new Psr17Factory();
        $httpClient = $this->createStub(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn(
            new \Nyholm\Psr7\Response(200, [], $factory->createStream(json_encode([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ]))),
        );

        $config = new RecaptchaConfig(secretV2: 'test-secret');
        $client = new RecaptchaClient($config, $httpClient, $factory, $factory);
        RecaptchaRegistry::configure($client);

        $handler = new RecaptchaV2RuleHandler();
        $rule = new RecaptchaV2Rule();
        $result = $handler->validate('bad-token', $rule, new ValidationContext());

        $this->assertFalse($result->isValid());
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
