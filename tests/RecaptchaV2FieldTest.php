<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Client\ClientInterface;
use YiiRocks\Recaptcha\RecaptchaClient;
use YiiRocks\Recaptcha\RecaptchaConfig;
use YiiRocks\Recaptcha\RecaptchaRegistry;
use YiiRocks\Recaptcha\RecaptchaV2Field;
use YiiRocks\Recaptcha\RecaptchaV2Size;
use YiiRocks\Recaptcha\RecaptchaV2Theme;
use YiiRocks\Recaptcha\RecaptchaV2Type;

final class RecaptchaV2FieldTest extends AbstractRecaptchaField
{
    public function testPerFieldV2OptionsOverrideRegistryDefaults(): void
    {
        RecaptchaRegistry::setV2Defaults(
            theme: RecaptchaV2Theme::Dark,
            size: RecaptchaV2Size::Compact,
            type: RecaptchaV2Type::Audio,
        );

        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('key-r')
            ->withTheme(RecaptchaV2Theme::Light)
            ->withSize(RecaptchaV2Size::Normal)
            ->withType(RecaptchaV2Type::Image)
            ->render();

        $this->assertStringContainsString('data-theme="light"', $html);
        $this->assertStringContainsString('data-size="normal"', $html);
        $this->assertStringContainsString('data-type="image"', $html);
    }

    public function testRegistryV2DefaultsApplied(): void
    {
        RecaptchaRegistry::setV2Defaults(
            theme: RecaptchaV2Theme::Dark,
            size: RecaptchaV2Size::Compact,
            type: RecaptchaV2Type::Audio,
        );

        $html = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('key-r')
            ->render();

        $this->assertStringContainsString('data-theme="dark"', $html);
        $this->assertStringContainsString('data-size="compact"', $html);
        $this->assertStringContainsString('data-type="audio"', $html);
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

    public function testWithMethodsDoNotMutateOriginalInstance(): void
    {
        $base = RecaptchaV2Field::field($this->createFormModel(), 'captcha')
            ->withSiteKey('base-key');

        $this->assertStringContainsString(' id="g-recaptcha-', $base->render());

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

    protected function fieldClass(): string
    {
        return RecaptchaV2Field::class;
    }
}
