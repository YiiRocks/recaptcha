<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use Nyholm\Psr7\ServerRequest;
use YiiRocks\Recaptcha\Exception\InvalidRuleException;
use YiiRocks\Recaptcha\Exception\MissingClientException;
use YiiRocks\Recaptcha\RecaptchaConfig;
use YiiRocks\Recaptcha\RecaptchaRegistry;
use YiiRocks\Recaptcha\RecaptchaV2Rule;
use YiiRocks\Recaptcha\RecaptchaV3Rule;
use YiiRocks\Recaptcha\RecaptchaV3RuleHandler;
use Yiisoft\RequestProvider\RequestProvider;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\ValidationContext;

final class RecaptchaV3RuleHandlerTest extends AbstractRecaptchaRuleHandler
{
    private RecaptchaV3RuleHandler $_handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->_handler = new RecaptchaV3RuleHandler();
    }

    public function testActionMismatchErrorParametersWhenActionIsPresent(): void
    {
        $client = $this->createClient(['success' => true, 'score' => 0.9, 'action' => 'register']);
        $handler = new RecaptchaV3RuleHandler(client: $client);
        $rule = new RecaptchaV3Rule(action: 'login');

        $result = $handler->validate('token', $rule, new ValidationContext());

        $this->assertSame('register', $result->getErrors()[0]->getParameters()['actual']);
    }

    public function testActionMismatchErrorParametersWhenActionMissing(): void
    {
        $client = $this->createClient(['success' => true, 'score' => 0.9]);
        $handler = new RecaptchaV3RuleHandler(client: $client);
        $rule = new RecaptchaV3Rule(action: 'login');

        $result = $handler->validate('token', $rule, new ValidationContext());

        $parameters = $result->getErrors()[0]->getParameters();
        $this->assertSame('login', $parameters['expected']);
        $this->assertSame('', $parameters['actual']);
    }

    public function testClientIpNotSentWhenRuleDisablesSendRemoteIp(): void
    {
        [$client, $capture] = $this->createCapturingClient(
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
        [$client, $capture] = $this->createCapturingClient(
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
        $explicitClient = $this->createClient(['success' => true, 'score' => 0.9, 'action' => 'submit']);
        RecaptchaRegistry::configure($this->createClient(['success' => false]));

        $handler = new RecaptchaV3RuleHandler(client: $explicitClient);
        $rule = new RecaptchaV3Rule();
        $result = $handler->validate('token', $rule, new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    public function testFailureErrorIncludesErrorCodesParameter(): void
    {
        $client = $this->createClient([
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
        $client = $this->createClient(['success' => true, 'score' => 0.3, 'action' => 'submit']);
        $handler = new RecaptchaV3RuleHandler(client: $client);
        $rule = new RecaptchaV3Rule(threshold: 0.5);

        $result = $handler->validate('token', $rule, new ValidationContext());

        $this->assertSame('0.3', $result->getErrors()[0]->getParameters()['score']);
    }

    public function testScoreTooLowErrorParametersWhenScoreMissing(): void
    {
        $client = $this->createClient(['success' => true]);
        $handler = new RecaptchaV3RuleHandler(client: $client);
        $rule = new RecaptchaV3Rule(threshold: 0.5);

        $result = $handler->validate('token', $rule, new ValidationContext());

        $parameters = $result->getErrors()[0]->getParameters();
        $this->assertSame('0', $parameters['score']);
        $this->assertSame('0.5', $parameters['threshold']);
    }

    public function testTranslatorUsesExplicitlyInjectedTranslatorOverRegistry(): void
    {
        $client = $this->createClient(['success' => false]);

        $explicitTranslator = $this->createStub(TranslatorInterface::class);
        $explicitTranslator->method('translate')->willReturn('explicit-translation');

        $registryTranslator = $this->createStub(TranslatorInterface::class);
        $registryTranslator->method('translate')->willReturn('registry-translation');

        RecaptchaRegistry::configure(
            client: $this->createClient(['success' => true, 'score' => 0.9, 'action' => 'submit']),
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

    public function testValidateSkipsVerificationWhenSecretNotConfigured(): void
    {
        $client = $this->createClient(['success' => false], $this->createConfig(''));
        $handler = new RecaptchaV3RuleHandler(client: $client);
        $rule = new RecaptchaV3Rule();

        $result = $handler->validate('token', $rule, new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    public function testValidateSkipsVerificationWhenSiteKeyNotConfigured(): void
    {
        $config = new RecaptchaConfig(siteKeyV3: '', secretV3: 'configured-secret');
        $client = $this->createClient(['success' => false], $config);
        $handler = new RecaptchaV3RuleHandler(client: $client);
        $rule = new RecaptchaV3Rule();

        $result = $handler->validate('token', $rule, new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    public function testValidateSucceedsWhenScoreExactlyMeetsThreshold(): void
    {
        $client = $this->createClient(['success' => true, 'score' => 0.5, 'action' => 'submit']);
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

    public function testValidateThrowsForUnsupportedRule(): void
    {
        $handler = new RecaptchaV3RuleHandler(client: $this->createClient(['success' => true, 'score' => 0.9, 'action' => 'submit']));

        $this->expectException(InvalidRuleException::class);
        $this->expectExceptionMessage('Expected ' . RecaptchaV3Rule::class . ', got ' . RecaptchaV2Rule::class . '.');

        $handler->validate('token', new RecaptchaV2Rule(), new ValidationContext());
    }

    public function testValidateThrowsWhenClientMissing(): void
    {
        RecaptchaRegistry::reset();
        $handler = new RecaptchaV3RuleHandler();
        $rule = new RecaptchaV3Rule();

        $this->expectException(MissingClientException::class);
        $this->expectExceptionMessage('RecaptchaClient is not configured.');

        $handler->validate('token', $rule, new ValidationContext());
    }

    public function testValidateWithSecretUsesProvidedSecret(): void
    {
        $client = $this->createClient(['success' => true, 'score' => 0.9, 'action' => 'submit']);
        $handler = new RecaptchaV3RuleHandler(client: $client);
        $rule = new RecaptchaV3Rule(secret: 'custom-secret');

        $result = $handler->validate('token', $rule, new ValidationContext());

        $this->assertTrue($result->isValid());
    }

    protected function createConfig(string $secret, bool $sendRemoteIp = false): RecaptchaConfig
    {
        return new RecaptchaConfig(siteKeyV3: 'test-site-key', secretV3: $secret, sendRemoteIp: $sendRemoteIp);
    }
}
