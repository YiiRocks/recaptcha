<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use ReflectionMethod;
use YiiRocks\Recaptcha\Exception\MissingSiteKeyException;
use YiiRocks\Recaptcha\RecaptchaClient;
use YiiRocks\Recaptcha\RecaptchaConfig;
use YiiRocks\Recaptcha\RecaptchaRegistry;
use YiiRocks\Recaptcha\RecaptchaV3Badge;
use YiiRocks\Recaptcha\RecaptchaV3Field;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Result;

final class RecaptchaV3FieldTest extends TestCase
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

    public function testDefaultFieldIdIsDerivedFromFieldName(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('key-c')
            ->render();

        $this->assertMatchesRegularExpression('/id="captcha-[a-zA-Z0-9]+"/', $html);
    }

    public function testExplicitTranslatorTakesPrecedenceOverRegistry(): void
    {
        $explicitTranslator = $this->createStub(TranslatorInterface::class);
        $explicitTranslator->method('translate')->willReturn('explicit-notice');

        $registryTranslator = $this->createStub(TranslatorInterface::class);
        $registryTranslator->method('translate')->willReturn('registry-notice');

        RecaptchaRegistry::configure(
            new RecaptchaClient(
                new RecaptchaConfig(),
                $this->createStub(ClientInterface::class),
                new Psr17Factory(),
                new Psr17Factory(),
            ),
            translator: $registryTranslator,
        );

        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->withBadge(RecaptchaV3Badge::Hidden)
            ->withTranslator($explicitTranslator)
            ->render();

        $this->assertStringContainsString('explicit-notice', $html);
        $this->assertStringNotContainsString('registry-notice', $html);
    }

    public function testFieldAutoDerivesNameFromFormModel(): void
    {
        $formModel = new class() extends FormModel {
            public string $captcha = '';
        };

        $html = RecaptchaV3Field::field($formModel, 'captcha')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringContainsString('name="captcha"', $html);
    }

    public function testFieldAutoDerivesNameFromNamedFormModel(): void
    {
        $formModel = new class() extends FormModel {
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

    public function testGenerateInputThrowsWhenSiteKeyNotSet(): void
    {
        $field = RecaptchaV3Field::field($this->createFormModel(), 'captcha');

        $method = new ReflectionMethod($field, 'generateInput');

        $this->expectException(MissingSiteKeyException::class);

        $method->invoke($field);
    }

    public function testJsonOptionsEscapeSpecialCharacters(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('key-d')
            ->withAction("a<b&c'd\"e")
            ->inputId('fid')
            ->render();

        $this->assertStringContainsString('\u003C', $html);
        $this->assertStringContainsString('\u0026', $html);
        $this->assertStringContainsString('\u0027', $html);
        $this->assertStringContainsString('\u0022', $html);
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

    public function testNameOverridesAutoDerivation(): void
    {
        $formModel = new class() extends FormModel {
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

    public function testRenderExactHtmlWithDefaultBadgeAndAction(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('key-a')
            ->withAction('login')
            ->withFormId('login-form')
            ->withExecuteTimeout(10000)
            ->withJsApiUrl('https://example.com/api.js')
            ->inputId('fixed-id')
            ->useContainer(false)
            ->render();

        $this->assertSame(
            '<script src="https://example.com/api.js?render=key-a"></script>'
            . '<input type="hidden" name="captcha" id="fixed-id">'
            . '<script>(function(){'
            . 'var o={"siteKey":"key-a","action":"login","fieldId":"fixed-id","formId":"login-form","timeout":10000};'
            . 'var f=document.getElementById(o.fieldId);'
            . 'var form=o.formId?document.getElementById(o.formId):f.closest("form");'
            . 'if(form){'
            . 'form.addEventListener("submit",function(e){'
            . 'e.preventDefault();'
            . 'var formRef=this;'
            . 'var fSubmitted=false;'
            . 'function submitForm(){if(!fSubmitted){fSubmitted=true;HTMLFormElement.prototype.submit.call(formRef);}}'
            . 'if(o.timeout){setTimeout(submitForm,o.timeout);}'
            . 'try{'
            . 'if(typeof grecaptcha!=="undefined"&&typeof grecaptcha.execute==="function"){'
            . 'grecaptcha.execute(o.siteKey,{action:o.action}).then(function(t){'
            . 'f.value=t;submitForm();'
            . '})["catch"](function(){submitForm();});'
            . '}else{submitForm();}'
            . '}catch(e){submitForm();}'
            . '});'
            . '}'
            . '})();</script>',
            $html,
        );
    }

    public function testRenderExactHtmlWithHiddenBadgeAndNoAction(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('key-b')
            ->withBadge(RecaptchaV3Badge::Hidden)
            ->withExecuteTimeout(null)
            ->inputId('fixed-id2')
            ->useContainer(false)
            ->render();

        $this->assertSame(
            '<style>.grecaptcha-badge{visibility:hidden !important;}</style>'
            . '<div style="display: none;">'
            . '<script src="https://www.google.com/recaptcha/api.js?render=key-b"></script>'
            . '<input type="hidden" name="captcha" id="fixed-id2">'
            . '<script>(function(){'
            . 'var o={"siteKey":"key-b","action":"","fieldId":"fixed-id2","formId":"","timeout":0};'
            . 'var f=document.getElementById(o.fieldId);'
            . 'var form=o.formId?document.getElementById(o.formId):f.closest("form");'
            . 'if(form){'
            . 'form.addEventListener("submit",function(e){'
            . 'e.preventDefault();'
            . 'var formRef=this;'
            . 'var fSubmitted=false;'
            . 'function submitForm(){if(!fSubmitted){fSubmitted=true;HTMLFormElement.prototype.submit.call(formRef);}}'
            . 'try{'
            . 'if(typeof grecaptcha!=="undefined"&&typeof grecaptcha.execute==="function"){'
            . 'grecaptcha.execute(o.siteKey).then(function(t){'
            . 'f.value=t;submitForm();'
            . '})["catch"](function(){submitForm();});'
            . '}else{submitForm();}'
            . '}catch(e){submitForm();}'
            . '});'
            . '}'
            . '})();</script>'
            . '</div>'
            . '<div>This site is protected by reCAPTCHA and the Google '
            . '<a href="https://policies.google.com/privacy">Privacy Policy</a> and '
            . '<a href="https://policies.google.com/terms">Terms of Service</a> apply.</div>',
            $html,
        );
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

    public function testRenderWithContainerClass(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->containerClass('form-group', 'custom')
            ->render();

        $this->assertStringStartsWith('<div class="form-group custom">', $html);
    }

    public function testRenderWithCustomContainerAttributes(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->containerAttributes(['class' => 'my-wrapper', 'data-theme' => 'dark'])
            ->render();

        $this->assertStringStartsWith('<div class="my-wrapper" data-theme="dark">', $html);
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

    public function testRenderWithFormModelShowsErrors(): void
    {
        $result = new Result();
        $result->addError('The captcha is invalid.', valuePath: ['captcha']);

        $formModel = new class() extends FormModel {
            public string $captcha = '';
        };
        $formModel->processValidationResult($result);

        $html = RecaptchaV3Field::field($formModel, 'captcha')
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

        $html = RecaptchaV3Field::field($formModel, 'other')
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

        $html = RecaptchaV3Field::field($formModel, 'captcha')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringNotContainsString('invalid-feedback', $html);
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

    public function testRenderWrappedInDivWithMb3ByDefault(): void
    {
        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('test-key')
            ->render();

        $this->assertStringStartsWith('<div class="mb-3">' . "\n", $html);
        $this->assertStringEndsWith("\n" . '</div>', $html);
    }

    public function testThrowsWhenRegistryHasNoClientConfigured(): void
    {
        $reflection = new \ReflectionClass(RecaptchaRegistry::class);
        $property = $reflection->getProperty('client');
        $property->setValue(null, null);

        $this->expectException(MissingSiteKeyException::class);

        RecaptchaV3Field::field($this->createFormModel(), 'captcha')->render();
    }

    public function testThrowsWithoutSiteKey(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Site key must be set');

        RecaptchaV3Field::field($this->createFormModel(), 'captcha')->render();
    }

    public function testWithMethodsDoNotMutateOriginalInstance(): void
    {
        $base = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('base-key');

        $this->assertStringContainsString('render=other-key', $base->withSiteKey('other-key')->render());
        $this->assertStringContainsString('render=base-key', $base->render());

        $this->assertStringContainsString('"action":"login"', $base->withAction('login')->render());
        $this->assertStringContainsString('"action":""', $base->render());

        $this->assertStringContainsString('"formId":"my-form"', $base->withFormId('my-form')->render());
        $this->assertStringContainsString('"formId":""', $base->render());

        $this->assertStringContainsString(
            'style="display: none;"',
            $base->withBadge(RecaptchaV3Badge::Hidden)->render(),
        );
        $this->assertStringNotContainsString('style="display: none;"', $base->render());

        $customUrl = 'https://custom.example.com/api.js';
        $this->assertStringContainsString($customUrl, $base->withJsApiUrl($customUrl)->render());
        $this->assertStringNotContainsString($customUrl, $base->render());

        $translator = $this->createStub(TranslatorInterface::class);
        $translator->method('translate')->willReturn('custom-notice');
        $this->assertStringContainsString(
            'custom-notice',
            $base->withTranslator($translator)->withBadge(RecaptchaV3Badge::Hidden)->render(),
        );
        $this->assertStringNotContainsString(
            'custom-notice',
            $base->withBadge(RecaptchaV3Badge::Hidden)->render(),
        );

        $this->assertStringContainsString('"timeout":5000', $base->withExecuteTimeout(5000)->render());
        $this->assertStringContainsString('"timeout":15000', $base->render());
    }

    private function createFormModel(): FormModel
    {
        return new class() extends FormModel {
            public string $captcha = '';
        };
    }
}
