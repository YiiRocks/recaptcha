<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use YiiRocks\Recaptcha\RecaptchaClient;
use YiiRocks\Recaptcha\RecaptchaConfig;
use YiiRocks\Recaptcha\RecaptchaRegistry;
use YiiRocks\Recaptcha\RecaptchaV3Badge;
use YiiRocks\Recaptcha\RecaptchaV3Field;
use Yiisoft\FormModel\FormModel;
use Yiisoft\Translator\TranslatorInterface;

final class RecaptchaV3FieldTest extends AbstractRecaptchaField
{

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

    public function testPerFieldBadgeOverridesRegistryDefault(): void
    {
        RecaptchaRegistry::setBadgeDefault(RecaptchaV3Badge::Hidden);

        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('key-r')
            ->withBadge(RecaptchaV3Badge::BottomRight)
            ->render();

        $this->assertStringNotContainsString('.grecaptcha-badge{visibility:hidden !important;}', $html);
    }

    public function testRegistryBadgeDefaultApplied(): void
    {
        RecaptchaRegistry::setBadgeDefault(RecaptchaV3Badge::Hidden);

        $html = RecaptchaV3Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('key-r')
            ->render();

        $this->assertStringContainsString('.grecaptcha-badge{visibility:hidden !important;}', $html);
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

        $this->assertStringContainsString('"timeout":1234', $base->withExecuteTimeout(1234)->render());
        $this->assertStringContainsString('"timeout":5000', $base->render());
    }
    protected function fieldClass(): string
    {
        return RecaptchaV3Field::class;
    }
}
