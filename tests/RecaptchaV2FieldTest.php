<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Validator\Result;
use YiiRocks\Recaptcha\RecaptchaClient;
use YiiRocks\Recaptcha\RecaptchaConfig;
use YiiRocks\Recaptcha\RecaptchaRegistry;
use YiiRocks\Recaptcha\RecaptchaV2Field;
use YiiRocks\Recaptcha\RecaptchaV2Size;
use YiiRocks\Recaptcha\RecaptchaV2Theme;
use YiiRocks\Recaptcha\RecaptchaV2Type;

final class RecaptchaV2FieldTest extends TestCase
{
    private function createFormModel(): FormModel
    {
        return new class () extends FormModel {
            public string $captcha = '';
        };
    }

    public function testRenderWithSiteKey(): void
    {
        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI')
            ->render();

        $this->assertStringContainsString('data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"', $html);
        $this->assertStringContainsString('class="g-recaptcha"', $html);
        $this->assertStringContainsString('async defer', $html);
    }

    public function testRenderWithDarkTheme(): void
    {
        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->withTheme(RecaptchaV2Theme::Dark)
            ->render();

        $this->assertStringContainsString('data-theme="dark"', $html);
    }

    public function testRenderWithCompactSize(): void
    {
        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->withSize(RecaptchaV2Size::Compact)
            ->render();

        $this->assertStringContainsString('data-size="compact"', $html);
    }

    public function testRenderWithAudioType(): void
    {
        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->withType(RecaptchaV2Type::Audio)
            ->render();

        $this->assertStringContainsString('data-type="audio"', $html);
    }

    public function testRenderWithCustomId(): void
    {
        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->withId('my-captcha')
            ->render();

        $this->assertStringContainsString('id="my-captcha"', $html);
    }

    public function testRenderWithCallbacks(): void
    {
        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->withCallback('onSuccess')
            ->withExpiredCallback('onExpired')
            ->withErrorCallback('onError')
            ->render();

        $this->assertStringContainsString('data-callback="onSuccess"', $html);
        $this->assertStringContainsString('data-expired-callback="onExpired"', $html);
        $this->assertStringContainsString('data-error-callback="onError"', $html);
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

    public function testRenderWithCustomJsApiUrl(): void
    {
        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->withJsApiUrl('https://custom.example.com/api.js')
            ->render();

        $this->assertStringContainsString('https://custom.example.com/api.js', $html);
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

    public function testRenderWithFormModelShowsErrors(): void
    {
        $result = new Result();
        $result->addError('The captcha is invalid.', valuePath: ['captcha']);

        $formModel = new class () extends FormModel {
            public string $captcha = '';
        };
        $formModel->processValidationResult($result);

        $html = RecaptchaV2Field::field($formModel, 'captcha')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringContainsString('The captcha is invalid.', $html);
    }

    public function testRenderWithFormModelShowsNoErrorsWhenNoErrors(): void
    {
        $formModel = new class () extends FormModel {
            public string $captcha = '';
        };
        $formModel->processValidationResult(new Result());

        $html = RecaptchaV2Field::field($formModel, 'captcha')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringNotContainsString('invalid-feedback', $html);
    }

    public function testRenderWithFormModelShowsNoErrorsForDifferentAttribute(): void
    {
        $result = new Result();
        $result->addError('The captcha is invalid.', valuePath: ['captcha']);

        $formModel = new class () extends FormModel {
            public string $captcha = '';
            public string $other = '';
        };
        $formModel->processValidationResult($result);

        $html = RecaptchaV2Field::field($formModel, 'other')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringNotContainsString('invalid-feedback', $html);
    }

    public function testRenderWrappedInDivWithMb3ByDefault(): void
    {
        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringStartsWith('<div class="mb-3">' . "\n", $html);
        $this->assertStringEndsWith("\n" . '</div>', $html);
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

    public function testRenderWithCustomContainerTag(): void
    {
        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->containerTag('section')
            ->render();

        $this->assertStringStartsWith('<section class="mb-3">', $html);
        $this->assertStringEndsWith('</section>', $html);
    }

    public function testRenderWithCustomContainerAttributes(): void
    {
        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->containerAttributes(['class' => 'my-wrapper', 'data-theme' => 'dark'])
            ->render();

        $this->assertStringStartsWith('<div class="my-wrapper" data-theme="dark">', $html);
    }

    public function testRenderWithContainerClass(): void
    {
        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->containerClass('form-group', 'custom')
            ->render();

        $this->assertStringStartsWith('<div class="form-group custom">', $html);
    }

    public function testRenderWithAddContainerClass(): void
    {
        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->containerClass('mb-3', 'extra-class')
            ->render();

        $this->assertStringStartsWith('<div class="mb-3 extra-class">', $html);
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

    protected function tearDown(): void
    {
        RecaptchaRegistry::setContainerDefaults([]);
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
