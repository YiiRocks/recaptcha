<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Result;
use YiiRocks\Recaptcha\RecaptchaClient;
use YiiRocks\Recaptcha\RecaptchaConfig;
use YiiRocks\Recaptcha\RecaptchaRegistry;
use YiiRocks\Recaptcha\RecaptchaV3Badge;
use YiiRocks\Recaptcha\RecaptchaV3Field;

final class RecaptchaV3FieldTest extends TestCase
{
    private function createFormModel(): FormModel
    {
        return new class () extends FormModel {
            public string $captcha = '';
        };
    }

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

    public function testRenderWithSiteKey(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI')
            ->render();

        $this->assertStringContainsString('render=6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI', $html);
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('grecaptcha.execute', $html);
        $this->assertStringContainsString('closest("form")', $html);
        $this->assertStringContainsString('addEventListener("submit"', $html);
        $this->assertStringContainsString('formRef=this', $html);
        $this->assertStringContainsString('submitForm', $html);
        $this->assertStringContainsString('"timeout":15000', $html);
        $this->assertStringContainsString('setTimeout(submitForm,o.timeout)', $html);
        $this->assertStringContainsString('fSubmitted', $html);
        $this->assertStringContainsString('typeof grecaptcha', $html);
        $this->assertStringContainsString('typeof grecaptcha.execute', $html);
        $this->assertStringContainsString('try{', $html);
        $this->assertStringContainsString('}catch(e)', $html);
    }

    public function testRenderWithCustomAction(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->withAction('login')
            ->render();

        $this->assertStringContainsString('"action":"login"', $html);
    }

    public function testRenderWithFormId(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->withFormId('login-form')
            ->render();

        $this->assertStringContainsString('"formId":"login-form"', $html);
        $this->assertStringContainsString('document.getElementById(o.formId)', $html);
        $this->assertStringContainsString('addEventListener("submit"', $html);
    }

    public function testRenderWithHiddenBadge(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->withBadge(RecaptchaV3Badge::Hidden)
            ->render();

        $this->assertStringContainsString('style="display: none;"', $html);
        $this->assertStringContainsString('Privacy Policy', $html);
        $this->assertStringContainsString('Terms of Service', $html);
    }

    public function testRenderWithCustomExecuteTimeout(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->withExecuteTimeout(10000)
            ->render();

        $this->assertStringContainsString('"timeout":10000', $html);
        $this->assertStringContainsString('setTimeout(submitForm,o.timeout)', $html);
    }

    public function testRenderWithExecuteTimeoutDisabled(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->withExecuteTimeout(null)
            ->render();

        $this->assertStringContainsString('"timeout":0', $html);
        $this->assertStringNotContainsString('setTimeout', $html);
    }

    public function testRenderWithCustomName(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->name('my_recaptcha_token')
            ->render();

        $this->assertStringContainsString('name="my_recaptcha_token"', $html);
    }

    public function testRenderWithCustomInputId(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->name('token')
            ->inputId('token-field')
            ->render();

        $this->assertStringContainsString('id="token-field"', $html);
    }

    public function testThrowsWithoutSiteKey(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Site key must be set');

        RecaptchaV3Field::field($this->createFormModel(), 'captcha')->render();
    }

    public function testRenderUsesTranslatorFromRegistry(): void
    {
        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('translate')
            ->willReturnCallback(function (string $id, array $params, string $category): string {
                return match ($id) {
                    'Privacy Policy' => 'Datenschutzerklärung',
                    'Terms of Service' => 'Nutzungsbedingungen',
                    default => str_replace(
                        ['{privacyPolicy}', '{termsOfService}'],
                        [$params['privacyPolicy'] ?? '', $params['termsOfService'] ?? ''],
                        match ($category) {
                            'recaptcha' => match ($id) {
                                'This site is protected by reCAPTCHA and the Google {privacyPolicy} and {termsOfService} apply.' =>
                                    'Diese Seite ist durch reCAPTCHA geschützt und es gelten die Google {privacyPolicy} und {termsOfService}.',
                                default => $id,
                            },
                            default => $id,
                        },
                    ),
                };
            });

        RecaptchaRegistry::configure(
            new RecaptchaClient(
                new RecaptchaConfig(siteKeyV3: 'test-key'),
                $this->createStub(ClientInterface::class),
                new Psr17Factory(),
                new Psr17Factory(),
            ),
            translator: $translator,
        );

        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withBadge(RecaptchaV3Badge::Hidden)
            ->render();

        $this->assertStringContainsString('Datenschutzerklärung', $html);
        $this->assertStringContainsString('Nutzungsbedingungen', $html);
        $this->assertStringContainsString('reCAPTCHA geschützt', $html);
        $this->assertStringNotContainsString('Privacy Policy', $html);
    }

    public function testRenderUsesSiteKeyFromRegistry(): void
    {
        RecaptchaRegistry::configure(
            new RecaptchaClient(
                new RecaptchaConfig(siteKeyV3: 'registry-key-v3'),
                $this->createStub(ClientInterface::class),
                new Psr17Factory(),
                new Psr17Factory(),
            ),
        );

        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')->render();

        $this->assertStringContainsString('render=registry-key-v3', $html);
    }

    public function testRenderWithFormModelShowsErrors(): void
    {
        $result = new Result();
        $result->addError('The captcha is invalid.', valuePath: ['captcha']);

        $formModel = new class () extends FormModel {
            public string $captcha = '';
        };
        $formModel->processValidationResult($result);

        $html = RecaptchaV3Field::field($formModel, 'captcha')
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

        $html = RecaptchaV3Field::field($formModel, 'captcha')
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

        $html = RecaptchaV3Field::field($formModel, 'other')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringNotContainsString('invalid-feedback', $html);
    }

    public function testRenderWrappedInDivWithMb3ByDefault(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringStartsWith('<div class="mb-3">' . "\n", $html);
        $this->assertStringEndsWith("\n" . '</div>', $html);
    }

    public function testRenderWithoutContainer(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->useContainer(false)
            ->render();

        $this->assertStringStartsWith('<script', $html);
        $this->assertStringNotContainsString('mb-3', $html);
    }

    public function testRenderWithCustomContainerTag(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->containerTag('section')
            ->render();

        $this->assertStringStartsWith('<section class="mb-3">', $html);
        $this->assertStringEndsWith('</section>', $html);
    }

    public function testRenderWithCustomContainerAttributes(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->containerAttributes(['class' => 'my-wrapper', 'data-theme' => 'dark'])
            ->render();

        $this->assertStringStartsWith('<div class="my-wrapper" data-theme="dark">', $html);
    }

    public function testRenderWithContainerClass(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->containerClass('form-group', 'custom')
            ->render();

        $this->assertStringStartsWith('<div class="form-group custom">', $html);
    }

    public function testRenderWithAddContainerClass(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->containerClass('mb-3', 'extra-class')
            ->render();

        $this->assertStringStartsWith('<div class="mb-3 extra-class">', $html);
    }

    public function testFieldAutoDerivesNameFromFormModel(): void
    {
        $formModel = new class () extends FormModel {
            public string $captcha = '';
        };

        $html = RecaptchaV3Field::field($formModel, 'captcha')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringContainsString('name="captcha"', $html);
    }

    public function testFieldAutoDerivesNameFromNamedFormModel(): void
    {
        $formModel = new class () extends FormModel {
            public string $captcha = '';
            public function getFormName(): string
            {
                return 'ContactForm';
            }
        };

        $html = RecaptchaV3Field::field($formModel, 'captcha')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringContainsString('name="ContactForm[captcha]"', $html);
    }

    public function testNameOverridesAutoDerivation(): void
    {
        $formModel = new class () extends FormModel {
            public string $captcha = '';
            public function getFormName(): string
            {
                return 'ContactForm';
            }
        };

        $html = RecaptchaV3Field::field($formModel, 'captcha')
            ->withSiteKey('test-key')
            ->name('g-recaptcha-response')
            ->render();

        $this->assertStringContainsString('name="g-recaptcha-response"', $html);
    }

    public function testMultipleWidgetsHaveUniqueInputIds(): void
    {
        $html1 = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->render();
        $html2 = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->render();

        preg_match('/id="([^"]+)"/', $html1, $m1);
        preg_match('/id="([^"]+)"/', $html2, $m2);

        $this->assertNotNull($m1[1] ?? null);
        $this->assertNotNull($m2[1] ?? null);
        $this->assertNotSame($m1[1], $m2[1]);
    }

    public function testRegistryContainerDefaultsApplied(): void
    {
        RecaptchaRegistry::setContainerDefaults([
            'attributes' => ['class' => 'global-class'],
        ]);

        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringStartsWith('<div class="global-class">' . "\n", $html);
    }

    public function testPerFieldOverrideTakesPrecedenceOverRegistryDefaults(): void
    {
        RecaptchaRegistry::setContainerDefaults([
            'attributes' => ['class' => 'global-class'],
        ]);

        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
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

        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringStartsWith('<section class="mb-3">', $html);
    }

    public function testRegistryUseContainerFalseDisablesContainer(): void
    {
        RecaptchaRegistry::setContainerDefaults([
            'useContainer' => false,
        ]);

        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringNotContainsString('mb-3', $html);
        $this->assertStringStartsWith('<script', $html);
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
            translator: null,
        );
    }
}
