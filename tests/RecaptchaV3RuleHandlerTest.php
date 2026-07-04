<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use YiiRocks\Recaptcha\RecaptchaClient;
use YiiRocks\Recaptcha\RecaptchaConfig;
use YiiRocks\Recaptcha\RecaptchaRegistry;
use YiiRocks\Recaptcha\RecaptchaV3Rule;
use YiiRocks\Recaptcha\RecaptchaV3RuleHandler;
use Yiisoft\RequestProvider\RequestProvider;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\ValidationContext;

final class RecaptchaV3RuleHandlerTest extends TestCase
{
    private RecaptchaV3RuleHandler $_handler;

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

        $this->_handler = new RecaptchaV3RuleHandler();
    }

    protected function tearDown(): void
    {
        RecaptchaRegistry::reset();
    }

    public function testActionMismatchErrorParametersWhenActionIsPresent(): void
    {
        $client = $this->_createClient(['success' => true, 'score' => 0.9, 'action' => 'register']);
        $handler = new RecaptchaV3RuleHandler(client: $client);
        $rule = new RecaptchaV3Rule(action: 'login');

        $result = $handler->validate('token', $rule, new ValidationContext());

        $this->assertSame('register', $result->getErrors()[0]->getParameters()['actual']);
    }

    public function testActionMismatchErrorParametersWhenActionMissing(): void
    {
        $client = $this->_createClient(['success' => true, 'score' => 0.9]);
        $handler = new RecaptchaV3RuleHandler(client: $client);
        $rule = new RecaptchaV3Rule(action: 'login');

        $result = $handler->validate('token', $rule, new ValidationContext());

        $parameters = $result->getErrors()[0]->getParameters();
        $this->assertSame('login', $parameters['expected']);
        $this->assertSame('', $parameters['actual']);
    }

    public function testClientIpNotSentWhenRuleDisablesSendRemoteIp(): void
    {
        [$client, $capture] = $this->_createCapturingClient(
            ['success' => true, 'score' => 0.9, 'action' => 'submit'],
            true,
        );

        $serverRequest = new ServerRequest('POST', '/', [], null, '1.1', ['REMOTE_ADDR' => '9.9.9.9']);
        $requestProvider = new RequestProvider($serverRequest);

        $handler = new RecaptchaV3RuleHandler(client: $client, requestProvider: $requestProvider);
        $rule = new RecaptchaV3Rule(sendRemoteIp: false);
        $handler->validate('token', $rule, new ValidationContext());

        parse_str((string) $capture->request->getBody(), $params);

        $this->assertArrayNotHasKey('remoteip', $params);
    }

    public function testClientIpSentOnlyWhenRuleEnablesSendRemoteIp(): void
    {
        [$client, $capture] = $this->_createCapturingClient(
            ['success' => true, 'score' => 0.9, 'action' => 'submit'],
            true,
        );

        $serverRequest = new ServerRequest('POST', '/', [], null, '1.1', ['REMOTE_ADDR' => '9.9.9.9']);
        $requestProvider = new RequestProvider($serverRequest);

        $handler = new RecaptchaV3RuleHandler(client: $client, requestProvider: $requestProvider);
        $rule = new RecaptchaV3Rule(sendRemoteIp: true);
        $handler->validate('token', $rule, new ValidationContext());

        parse_str((string) $capture->request->getBody(), $params);

        $this->assertSame('9.9.9.9', $params['remoteip']);
    }

    public function testExplicitClientTakesPrecedenceOverRegistry(): void
    {
        $explicitClient = $this->_createClient(['success' => true, 'score' => 0.9, 'action' => 'submit']);
        RecaptchaRegistry::configure($this->_createClient(['success' => false]));

        $handler = new RecaptchaV3RuleHandler(client: $explicitClient);
        $rule = new RecaptchaV3Rule();
        $result = $handler->validate('token', $rule, new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    public function testFailureErrorIncludesErrorCodesParameter(): void
    {
        $client = $this->_createClient([
            'success' => false,
            'error-codes' => ['timeout-or-duplicate'],
        ]);
        $handler = new RecaptchaV3RuleHandler(client: $client);
        $rule = new RecaptchaV3Rule();

        $result = $handler->validate('token', $rule, new ValidationContext());

        $this->assertSame('timeout-or-duplicate', $result->getErrors()[0]->getParameters()['errorCodes']);
    }

    public function testInvalidThreshold(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new RecaptchaV3Rule(threshold: 1.5);
    }

    public function testScoreTooLowErrorParametersWhenScoreIsPresent(): void
    {
        $client = $this->_createClient(['success' => true, 'score' => 0.3, 'action' => 'submit']);
        $handler = new RecaptchaV3RuleHandler(client: $client);
        $rule = new RecaptchaV3Rule(threshold: 0.5);

        $result = $handler->validate('token', $rule, new ValidationContext());

        $this->assertSame('0.3', $result->getErrors()[0]->getParameters()['score']);
    }

    public function testScoreTooLowErrorParametersWhenScoreMissing(): void
    {
        $client = $this->_createClient(['success' => true]);
        $handler = new RecaptchaV3RuleHandler(client: $client);
        $rule = new RecaptchaV3Rule(threshold: 0.5);

        $result = $handler->validate('token', $rule, new ValidationContext());

        $parameters = $result->getErrors()[0]->getParameters();
        $this->assertSame('0', $parameters['score']);
        $this->assertSame('0.5', $parameters['threshold']);
    }

    public function testTranslatorUsesExplicitlyInjectedTranslatorOverRegistry(): void
    {
        $client = $this->_createClient(['success' => false]);

        $explicitTranslator = $this->createStub(TranslatorInterface::class);
        $explicitTranslator->method('translate')->willReturn('explicit-translation');

        $registryTranslator = $this->createStub(TranslatorInterface::class);
        $registryTranslator->method('translate')->willReturn('registry-translation');

        RecaptchaRegistry::configure(
            client: $this->_createClient(['success' => true, 'score' => 0.9, 'action' => 'submit']),
            translator: $registryTranslator,
        );

        $handler = new RecaptchaV3RuleHandler(client: $client, translator: $explicitTranslator);
        $rule = new RecaptchaV3Rule();
        $result = $handler->validate('bad-token', $rule, new ValidationContext());

        $this->assertSame('explicit-translation', $result->getErrors()[0]->getMessage());
    }

    public function testValidateEmptyValue(): void
    {
        $rule = new RecaptchaV3Rule();
        $result = $this->_handler->validate('', $rule, new ValidationContext());

        $this->assertFalse($result->isValid());
    }

    public function testValidateSucceedsWhenScoreExactlyMeetsThreshold(): void
    {
        $client = $this->_createClient(['success' => true, 'score' => 0.5, 'action' => 'submit']);
        $handler = new RecaptchaV3RuleHandler(client: $client);
        $rule = new RecaptchaV3Rule(threshold: 0.5);

        $result = $handler->validate('token', $rule, new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    public function testValidateSuccess(): void
    {
        $rule = new RecaptchaV3Rule();
        $result = $this->_handler->validate('valid-token', $rule, new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    /**
     * @return array{0: RecaptchaClient, 1: object{request: ?RequestInterface}}
     */
    private function _createCapturingClient(array $responseData, bool $sendRemoteIp = false): array
    {
        $capture = new class() {
            public ?RequestInterface $request = null;
        };

        $httpClient = $this->createStub(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturnCallback(
            function (RequestInterface $request) use ($capture, $responseData) {
                $capture->request = $request;
                $factory = new Psr17Factory();

                return new \Nyholm\Psr7\Response(200, [], $factory->createStream(json_encode($responseData)));
            },
        );

        $factory = new Psr17Factory();
        $config = new RecaptchaConfig(secretV3: 'test-secret', sendRemoteIp: $sendRemoteIp);
        $client = new RecaptchaClient($config, $httpClient, $factory, $factory);

        return [$client, $capture];
    }

    private function _createClient(array $responseData): RecaptchaClient
    {
        $factory = new Psr17Factory();

        $httpClient = $this->createStub(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn(
            new \Nyholm\Psr7\Response(200, [], $factory->createStream(json_encode($responseData))),
        );

        $config = new RecaptchaConfig(secretV3: 'test-secret');

        return new RecaptchaClient($config, $httpClient, $factory, $factory);
    }
}
