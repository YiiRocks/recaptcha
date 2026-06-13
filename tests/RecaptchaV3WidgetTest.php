<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use YiiRocks\Recaptcha\RecaptchaClient;
use YiiRocks\Recaptcha\RecaptchaConfig;
use YiiRocks\Recaptcha\RecaptchaRegistry;
use YiiRocks\Recaptcha\RecaptchaV3;
use YiiRocks\Recaptcha\RecaptchaV3Badge;

final class RecaptchaV3WidgetTest extends TestCase
{
    public function testRenderWithSiteKey(): void
    {
        $html = RecaptchaV3::widget()
            ->withSiteKey('6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI')
            ->render();

        $this->assertStringContainsString('render=6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI', $html);
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('grecaptcha.execute', $html);
    }

    public function testRenderWithCustomAction(): void
    {
        $html = RecaptchaV3::widget()
            ->withSiteKey('test-key')
            ->withAction('login')
            ->render();

        $this->assertStringContainsString('"action":"login"', $html);
    }

    public function testRenderWithFormId(): void
    {
        $html = RecaptchaV3::widget()
            ->withSiteKey('test-key')
            ->withFormId('login-form')
            ->render();

        $this->assertStringContainsString('"formId":"login-form"', $html);
        $this->assertStringContainsString('addEventListener("submit"', $html);
    }

    public function testRenderWithHiddenBadge(): void
    {
        $html = RecaptchaV3::widget()
            ->withSiteKey('test-key')
            ->withBadge(RecaptchaV3Badge::Hidden)
            ->render();

        $this->assertStringContainsString('style="display:none;"', $html);
        $this->assertStringContainsString('Privacy Policy', $html);
        $this->assertStringContainsString('Terms of Service', $html);
    }

    public function testRenderWithCustomFieldName(): void
    {
        $html = RecaptchaV3::widget()
            ->withSiteKey('test-key')
            ->withFieldName('my_recaptcha_token')
            ->render();

        $this->assertStringContainsString('name="my_recaptcha_token"', $html);
    }

    public function testRenderWithCustomFieldId(): void
    {
        $html = RecaptchaV3::widget()
            ->withSiteKey('test-key')
            ->withFieldName('token')
            ->withFieldId('token-field')
            ->render();

        $this->assertStringContainsString('id="token-field"', $html);
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

        RecaptchaV3::widget()->render();
    }

    public function testRenderUsesSiteKeyFromRegistry(): void
    {
        RecaptchaRegistry::configure(
            new RecaptchaClient(
                new RecaptchaConfig(
                    siteKeyV3: 'registry-key-v3',
                ),
                $this->createStub(ClientInterface::class),
                new Psr17Factory(),
                new Psr17Factory(),
            ),
        );

        $html = RecaptchaV3::widget()->render();

        $this->assertStringContainsString('render=registry-key-v3', $html);
    }

    protected function tearDown(): void
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
}
