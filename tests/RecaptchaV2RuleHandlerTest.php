<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use Nyholm\Psr7\ServerRequest;
use YiiRocks\Recaptcha\Exception\InvalidRuleException;
use YiiRocks\Recaptcha\Exception\MissingClientException;
use YiiRocks\Recaptcha\RecaptchaConfig;
use YiiRocks\Recaptcha\RecaptchaRegistry;
use YiiRocks\Recaptcha\RecaptchaV2Rule;
use YiiRocks\Recaptcha\RecaptchaV2RuleHandler;
use YiiRocks\Recaptcha\RecaptchaV3Rule;
use Yiisoft\RequestProvider\RequestNotSetException;
use Yiisoft\RequestProvider\RequestProvider;
use Yiisoft\RequestProvider\RequestProviderInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\ValidationContext;

final class RecaptchaV2RuleHandlerTest extends AbstractRecaptchaRuleHandler
{
    private RecaptchaV2RuleHandler $_handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->_handler = new RecaptchaV2RuleHandler();
    }

    public function testClientIpIsNullWhenNoProviderConfigured(): void
    {
        $handler = new RecaptchaV2RuleHandler(client: $this->createClient(['success' => true]));
        $rule = new RecaptchaV2Rule(sendRemoteIp: true);

        $result = $handler->validate('token', $rule, new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    public function testClientIpIsNullWhenRemoteAddrIsNotAString(): void
    {
        $serverRequest = new ServerRequest('POST', '/', [], null, '1.1', ['REMOTE_ADDR' => null]);
        $requestProvider = new RequestProvider($serverRequest);
        $handler = new RecaptchaV2RuleHandler(
            client: $this->createClient(['success' => true]),
            requestProvider: $requestProvider,
        );
        $rule = new RecaptchaV2Rule(sendRemoteIp: true);

        $result = $handler->validate('token', $rule, new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    public function testClientIpIsNullWhenRequestNotSet(): void
    {
        $requestProvider = $this->createStub(RequestProviderInterface::class);
        $requestProvider->method('get')->willThrowException(new RequestNotSetException());
        $handler = new RecaptchaV2RuleHandler(
            client: $this->createClient(['success' => true]),
            requestProvider: $requestProvider,
        );
        $rule = new RecaptchaV2Rule(sendRemoteIp: true);

        $result = $handler->validate('token', $rule, new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    public function testClientIpNotSentWhenRuleDisablesSendRemoteIp(): void
    {
        [$client, $capture] = $this->createCapturingClient(['success' => true], true);

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
        [$client, $capture] = $this->createCapturingClient(['success' => true], true);

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
        $client = $this->createClient([
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
        $explicitClient = $this->createClient(['success' => true]);
        RecaptchaRegistry::configure($this->createClient(['success' => false]));

        $handler = new RecaptchaV2RuleHandler(client: $explicitClient);
        $rule = new RecaptchaV2Rule();
        $result = $handler->validate('token', $rule, new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    public function testTranslatorUsesExplicitlyInjectedTranslatorOverRegistry(): void
    {
        $client = $this->createClient(['success' => false]);

        $explicitTranslator = $this->createStub(TranslatorInterface::class);
        $explicitTranslator->method('translate')->willReturn('explicit-translation');

        $registryTranslator = $this->createStub(TranslatorInterface::class);
        $registryTranslator->method('translate')->willReturn('registry-translation');

        RecaptchaRegistry::configure(
            client: $this->createClient(['success' => true]),
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

    public function testValidateSkipsVerificationWhenSecretNotConfigured(): void
    {
        $client = $this->createClient(['success' => false], $this->createConfig(''));
        $handler = new RecaptchaV2RuleHandler(client: $client);
        $rule = new RecaptchaV2Rule();

        $result = $handler->validate('token', $rule, new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    public function testValidateSkipsVerificationWhenSiteKeyNotConfigured(): void
    {
        $config = new RecaptchaConfig(siteKeyV2: '', secretV2: 'configured-secret');
        $client = $this->createClient(['success' => false], $config);
        $handler = new RecaptchaV2RuleHandler(client: $client);
        $rule = new RecaptchaV2Rule();

        $result = $handler->validate('token', $rule, new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    public function testValidateSuccess(): void
    {
        $rule = new RecaptchaV2Rule();
        $result = $this->_handler->validate('valid-token', $rule, new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    public function testValidateThrowsForUnsupportedRule(): void
    {
        $handler = new RecaptchaV2RuleHandler(client: $this->createClient(['success' => true]));

        $this->expectException(InvalidRuleException::class);
        $this->expectExceptionMessage('Expected ' . RecaptchaV2Rule::class . ', got ' . RecaptchaV3Rule::class . '.');

        $handler->validate('token', new RecaptchaV3Rule(), new ValidationContext());
    }

    public function testValidateThrowsWhenClientMissing(): void
    {
        RecaptchaRegistry::reset();
        $handler = new RecaptchaV2RuleHandler();
        $rule = new RecaptchaV2Rule();

        $this->expectException(MissingClientException::class);
        $this->expectExceptionMessage('RecaptchaClient is not configured.');

        $handler->validate('token', $rule, new ValidationContext());
    }

    public function testValidateWithSecretUsesProvidedSecret(): void
    {
        $client = $this->createClient(['success' => true]);
        $handler = new RecaptchaV2RuleHandler(client: $client);
        $rule = new RecaptchaV2Rule(secret: 'custom-secret');

        $result = $handler->validate('token', $rule, new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    protected function createConfig(string $secret, bool $sendRemoteIp = false): RecaptchaConfig
    {
        return new RecaptchaConfig(siteKeyV2: 'test-site-key', secretV2: $secret, sendRemoteIp: $sendRemoteIp);
    }
}
