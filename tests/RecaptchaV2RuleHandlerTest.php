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
use YiiRocks\Recaptcha\RecaptchaV2Rule;
use YiiRocks\Recaptcha\RecaptchaV2RuleHandler;
use Yiisoft\RequestProvider\RequestProvider;
use Yiisoft\Translator\TranslatorInterface;
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

    protected function tearDown(): void
    {
        RecaptchaRegistry::reset();
    }

    public function testClientIpNotSentWhenRuleDisablesSendRemoteIp(): void
    {
        [$client, $capture] = $this->_createCapturingClient(['success' => true], true);

        $serverRequest = new ServerRequest('POST', '/', [], null, '1.1', ['REMOTE_ADDR' => '9.9.9.9']);
        $requestProvider = new RequestProvider($serverRequest);

        $handler = new RecaptchaV2RuleHandler(client: $client, requestProvider: $requestProvider);
        $rule = new RecaptchaV2Rule(sendRemoteIp: false);
        $handler->validate('token', $rule, new ValidationContext());

        parse_str((string) $capture->request->getBody(), $params);

        $this->assertArrayNotHasKey('remoteip', $params);
    }

    public function testClientIpSentOnlyWhenRuleEnablesSendRemoteIp(): void
    {
        [$client, $capture] = $this->_createCapturingClient(['success' => true], true);

        $serverRequest = new ServerRequest('POST', '/', [], null, '1.1', ['REMOTE_ADDR' => '9.9.9.9']);
        $requestProvider = new RequestProvider($serverRequest);

        $handler = new RecaptchaV2RuleHandler(client: $client, requestProvider: $requestProvider);
        $rule = new RecaptchaV2Rule(sendRemoteIp: true);
        $handler->validate('token', $rule, new ValidationContext());

        parse_str((string) $capture->request->getBody(), $params);

        $this->assertSame('9.9.9.9', $params['remoteip']);
    }

    public function testErrorIncludesErrorCodesParameter(): void
    {
        $client = $this->_createClient([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ]);

        $handler = new RecaptchaV2RuleHandler(client: $client);
        $rule = new RecaptchaV2Rule();
        $result = $handler->validate('bad-token', $rule, new ValidationContext());

        $errors = $result->getErrors();
        $this->assertSame('invalid-input-response', $errors[0]->getParameters()['errorCodes']);
    }

    public function testExplicitClientTakesPrecedenceOverRegistry(): void
    {
        $explicitClient = $this->_createClient(['success' => true]);
        RecaptchaRegistry::configure($this->_createClient(['success' => false]));

        $handler = new RecaptchaV2RuleHandler(client: $explicitClient);
        $rule = new RecaptchaV2Rule();
        $result = $handler->validate('token', $rule, new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    public function testTranslatorUsesExplicitlyInjectedTranslatorOverRegistry(): void
    {
        $client = $this->_createClient(['success' => false]);

        $explicitTranslator = $this->createStub(TranslatorInterface::class);
        $explicitTranslator->method('translate')->willReturn('explicit-translation');

        $registryTranslator = $this->createStub(TranslatorInterface::class);
        $registryTranslator->method('translate')->willReturn('registry-translation');

        RecaptchaRegistry::configure(
            client: $this->_createClient(['success' => true]),
            translator: $registryTranslator,
        );

        $handler = new RecaptchaV2RuleHandler(client: $client, translator: $explicitTranslator);
        $rule = new RecaptchaV2Rule();
        $result = $handler->validate('bad-token', $rule, new ValidationContext());

        $this->assertSame('explicit-translation', $result->getErrors()[0]->getMessage());
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

    public function testValidateSuccess(): void
    {
        $rule = new RecaptchaV2Rule();
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
        $config = new RecaptchaConfig(secretV2: 'test-secret', sendRemoteIp: $sendRemoteIp);
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

        $config = new RecaptchaConfig(secretV2: 'test-secret');

        return new RecaptchaClient($config, $httpClient, $factory, $factory);
    }
}
