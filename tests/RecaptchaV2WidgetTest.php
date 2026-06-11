<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use PHPUnit\Framework\TestCase;
use YiiRocks\Recaptcha\RecaptchaV2;
use YiiRocks\Recaptcha\RecaptchaV2Size;
use YiiRocks\Recaptcha\RecaptchaV2Theme;
use YiiRocks\Recaptcha\RecaptchaV2Type;

final class RecaptchaV2WidgetTest extends TestCase
{
    public function testRenderWithSiteKey(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI')
            ->render();

        $this->assertStringContainsString('data-sitekey="6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI"', $html);
        $this->assertStringContainsString('class="g-recaptcha"', $html);
        $this->assertStringContainsString('async defer', $html);
    }

    public function testRenderWithDarkTheme(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('test-key')
            ->withTheme(RecaptchaV2Theme::Dark)
            ->render();

        $this->assertStringContainsString('data-theme="dark"', $html);
    }

    public function testRenderWithCompactSize(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('test-key')
            ->withSize(RecaptchaV2Size::Compact)
            ->render();

        $this->assertStringContainsString('data-size="compact"', $html);
    }

    public function testRenderWithAudioType(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('test-key')
            ->withType(RecaptchaV2Type::Audio)
            ->render();

        $this->assertStringContainsString('data-type="audio"', $html);
    }

    public function testRenderWithCustomId(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('test-key')
            ->withId('my-captcha')
            ->render();

        $this->assertStringContainsString('id="my-captcha"', $html);
    }

    public function testRenderWithCallbacks(): void
    {
        $html = RecaptchaV2::widget()
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
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Site key must be set');

        RecaptchaV2::widget()->render();
    }

    public function testRenderWithCustomJsApiUrl(): void
    {
        $html = RecaptchaV2::widget()
            ->withSiteKey('test-key')
            ->withJsApiUrl('https://custom.example.com/api.js')
            ->render();

        $this->assertStringContainsString('https://custom.example.com/api.js', $html);
    }

    public function testMultipleWidgetsHaveUniqueIds(): void
    {
        $html1 = RecaptchaV2::widget()->withSiteKey('key1')->render();
        $html2 = RecaptchaV2::widget()->withSiteKey('key2')->render();

        preg_match('/id="([^"]+)"/', $html1, $m1);
        preg_match('/id="([^"]+)"/', $html2, $m2);

        $this->assertNotNull($m1[1] ?? null);
        $this->assertNotNull($m2[1] ?? null);
        $this->assertNotSame($m1[1], $m2[1]);
    }
}
