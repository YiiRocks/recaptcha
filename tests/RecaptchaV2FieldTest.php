<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use ReflectionClass;
use ReflectionMethod;
use YiiRocks\Recaptcha\Exception\MissingSiteKeyException;
use YiiRocks\Recaptcha\RecaptchaClient;
use YiiRocks\Recaptcha\RecaptchaConfig;
use YiiRocks\Recaptcha\RecaptchaRegistry;
use YiiRocks\Recaptcha\RecaptchaV2Field;
use YiiRocks\Recaptcha\RecaptchaV2Size;
use YiiRocks\Recaptcha\RecaptchaV2Theme;
use YiiRocks\Recaptcha\RecaptchaV2Type;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Result;

final class RecaptchaV2FieldTest extends TestCase
{
    protected function setUp(): void
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

    protected function tearDown(): void
    {
        RecaptchaRegistry::reset();
    }

    public function testGenerateInputThrowsWhenSiteKeyNotSet(): void
    {
        $field = RecaptchaV2Field::field($this->createFormModel(), 'captcha');

        $method = new ReflectionMethod($field, 'generateInput');

        $this->expectException(MissingSiteKeyException::class);

        $method->invoke($field);
    }

    public function testMultipleWidgetsHaveUniqueIds(): void
    {
        $html1 = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('key1')
            ->render();
        $html2 = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('key2')
            ->render();

        preg_match('/id="([^"]+)"/', $html1, $m1);
        preg_match('/id="([^"]+)"/', $html2, $m2);

        $this->assertNotNull($m1[1] ?? null);
        $this->assertNotNull($m2[1] ?? null);
        $this->assertNotSame($m1[1], $m2[1]);
    }

    public function testPerFieldOverrideTakesPrecedenceOverRegistryDefaults(): void
    {
        RecaptchaRegistry::setContainerDefaults([
            'attributes' => ['class' => 'global-class'],
        ]);

        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->containerAttributes(['class' => 'per-field'])
            ->render();

        $this->assertStringStartsWith('<div class="per-field">', $html);
    }

    public function testRegistryContainerDefaultsApplied(): void
    {
        RecaptchaRegistry::setContainerDefaults([
            'attributes' => ['class' => 'global-class'],
        ]);

        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringStartsWith('<div class="global-class">' . "\n", $html);
    }

    public function testRegistryContainerTagOverridesDefaultDiv(): void
    {
        RecaptchaRegistry::setContainerDefaults([
            'tag' => 'section',
        ]);

        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringStartsWith('<section class="mb-3">', $html);
    }

    public function testRegistryUseContainerFalseDisablesContainer(): void
    {
        RecaptchaRegistry::setContainerDefaults([
            'useContainer' => false,
        ]);

        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringNotContainsString('mb-3', $html);
        $this->assertStringStartsWith('<div id="g-recaptcha', $html);
    }

    public function testRenderExactHtmlWithAllOptionsSet(): void
    {
        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('key-a')
            ->withId('fixed-id')
            ->withTheme(RecaptchaV2Theme::Dark)
            ->withType(RecaptchaV2Type::Audio)
            ->withSize(RecaptchaV2Size::Compact)
            ->withJsApiUrl('https://example.com/api.js')
            ->withCallback('cb1')
            ->withExpiredCallback('cb2')
            ->withErrorCallback('cb3')
            ->useContainer(false)
            ->render();

        $this->assertSame(
            '<div id="fixed-id" class="g-recaptcha" data-sitekey="key-a" data-theme="dark" data-type="audio"'
            . ' data-size="compact" data-callback="cb1" data-expired-callback="cb2" data-error-callback="cb3">'
            . '</div>' . "\n"
            . '<script src="https://example.com/api.js" async defer></script>',
            $html,
        );
    }

    public function testRenderUsesSiteKeyFromRegistry(): void
    {
        RecaptchaRegistry::configure(
            new RecaptchaClient(
                new RecaptchaConfig(siteKeyV2: 'registry-key-v2'),
                $this->createStub(ClientInterface::class),
                new Psr17Factory(),
                new Psr17Factory(),
            ),
        );

        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')->render();

        $this->assertStringContainsString('data-sitekey="registry-key-v2"', $html);
    }

    public function testRenderWithContainerClass(): void
    {
        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->containerClass('form-group', 'custom')
            ->render();

        $this->assertStringStartsWith('<div class="form-group custom">', $html);
    }

    public function testRenderWithCustomContainerAttributes(): void
    {
        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->containerAttributes(['class' => 'my-wrapper', 'data-theme' => 'dark'])
            ->render();

        $this->assertStringStartsWith('<div class="my-wrapper" data-theme="dark">', $html);
    }

    public function testRenderWithCustomContainerTag(): void
    {
        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->containerTag('section')
            ->render();

        $this->assertStringStartsWith('<section class="mb-3">', $html);
        $this->assertStringEndsWith('</section>', $html);
    }

    public function testRenderWithFormModelShowsErrors(): void
    {
        $result = new Result();
        $result->addError('The captcha is invalid.', valuePath: ['captcha']);

        $formModel = new class() extends FormModel {
            public string $captcha = '';
        };
        $formModel->processValidationResult($result);

        $html = RecaptchaV2Field::field($formModel, 'captcha')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringContainsString('The captcha is invalid.', $html);
    }

    public function testRenderWithFormModelShowsNoErrorsForDifferentAttribute(): void
    {
        $result = new Result();
        $result->addError('The captcha is invalid.', valuePath: ['captcha']);

        $formModel = new class() extends FormModel {
            public string $captcha = '';
            public string $other = '';
        };
        $formModel->processValidationResult($result);

        $html = RecaptchaV2Field::field($formModel, 'other')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringNotContainsString('invalid-feedback', $html);
    }

    public function testRenderWithFormModelShowsNoErrorsWhenNoErrors(): void
    {
        $formModel = new class() extends FormModel {
            public string $captcha = '';
        };
        $formModel->processValidationResult(new Result());

        $html = RecaptchaV2Field::field($formModel, 'captcha')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringNotContainsString('invalid-feedback', $html);
    }

    public function testRenderWithoutContainer(): void
    {
        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->useContainer(false)
            ->render();

        $this->assertStringStartsWith('<div id="g-recaptcha', $html);
        $this->assertStringNotContainsString('mb-3', $html);
    }

    public function testRenderWrappedInDivWithMb3ByDefault(): void
    {
        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringStartsWith('<div class="mb-3">' . "\n", $html);
        $this->assertStringEndsWith("\n" . '</div>', $html);
    }

    public function testThrowsWhenRegistryHasNoClientConfigured(): void
    {
        $reflection = new ReflectionClass(RecaptchaRegistry::class);
        $property = $reflection->getProperty('client');
        $property->setValue(null, null);

        $this->expectException(MissingSiteKeyException::class);

        RecaptchaV2Field::field($this->createFormModel(), 'captcha')->render();
    }

    public function testThrowsWithoutSiteKey(): void
    {
        RecaptchaRegistry::configure(
            new RecaptchaClient(
                new RecaptchaConfig(),
                $this->createStub(ClientInterface::class),
                new Psr17Factory(),
                new Psr17Factory(),
            ),
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Site key must be set');

        RecaptchaV2Field::field($this->createFormModel(), 'captcha')->render();
    }

    public function testWithMethodsDoNotMutateOriginalInstance(): void
    {
        $base = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('base-key');

        $this->assertStringContainsString('data-sitekey="other-key"', $base->withSiteKey('other-key')->render());
        $this->assertStringContainsString('data-sitekey="base-key"', $base->render());

        $this->assertStringContainsString('id="custom-id"', $base->withId('custom-id')->render());
        $this->assertStringNotContainsString('id="custom-id"', $base->render());

        $this->assertStringContainsString('data-theme="dark"', $base->withTheme(RecaptchaV2Theme::Dark)->render());
        $this->assertStringContainsString('data-theme="light"', $base->render());

        $this->assertStringContainsString('data-type="audio"', $base->withType(RecaptchaV2Type::Audio)->render());
        $this->assertStringContainsString('data-type="image"', $base->render());

        $this->assertStringContainsString('data-size="compact"', $base->withSize(RecaptchaV2Size::Compact)->render());
        $this->assertStringContainsString('data-size="normal"', $base->render());

        $customUrl = 'https://custom.example.com/api.js';
        $this->assertStringContainsString($customUrl, $base->withJsApiUrl($customUrl)->render());
        $this->assertStringNotContainsString($customUrl, $base->render());

        $this->assertStringContainsString('data-callback="onSuccess"', $base->withCallback('onSuccess')->render());
        $this->assertStringNotContainsString('data-callback', $base->render());

        $this->assertStringContainsString(
            'data-expired-callback="onExpired"',
            $base->withExpiredCallback('onExpired')->render(),
        );
        $this->assertStringNotContainsString('data-expired-callback', $base->render());

        $this->assertStringContainsString(
            'data-error-callback="onError"',
            $base->withErrorCallback('onError')->render(),
        );
        $this->assertStringNotContainsString('data-error-callback', $base->render());
    }

    private function createFormModel(): FormModel
    {
        return new class() extends FormModel {
            public string $captcha = '';
        };
    }
}
