<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ServerRequestInterface;
use YiiRocks\Recaptcha\RecaptchaClient;
use YiiRocks\Recaptcha\RecaptchaConfig;
use YiiRocks\Recaptcha\RecaptchaRegistry;
use YiiRocks\Recaptcha\RecaptchaV2Size;
use YiiRocks\Recaptcha\RecaptchaV2Theme;
use YiiRocks\Recaptcha\RecaptchaV2Type;
use YiiRocks\Recaptcha\RecaptchaV3Badge;
use Yiisoft\RequestProvider\RequestProviderInterface;

final class RecaptchaRegistryTest extends TestCase
{
    protected function tearDown(): void
    {
        RecaptchaRegistry::reset();
    }

    public function testResetClearsConfiguredState(): void
    {
        RecaptchaRegistry::setContainerDefaults([
            'useContainer' => false,
            'tag' => 'section',
            'attributes' => ['class' => 'global-class'],
        ]);
        RecaptchaRegistry::setBadgeDefault(RecaptchaV3Badge::Hidden);
        RecaptchaRegistry::setV2Defaults(
            theme: RecaptchaV2Theme::Dark,
            size: RecaptchaV2Size::Compact,
            type: RecaptchaV2Type::Audio,
        );
        RecaptchaRegistry::configure(
            new RecaptchaClient(
                new RecaptchaConfig(siteKeyV2: 'test-key'),
                $this->createStub(ClientInterface::class),
                new Psr17Factory(),
                new Psr17Factory(),
            ),
        );

        RecaptchaRegistry::reset();

        $this->assertNull(RecaptchaRegistry::client());
        $this->assertNull(RecaptchaRegistry::requestProvider());
        $this->assertNull(RecaptchaRegistry::translator());
        $this->assertNull(RecaptchaRegistry::containerUseContainer());
        $this->assertNull(RecaptchaRegistry::containerTag());
        $this->assertNull(RecaptchaRegistry::containerAttributes());
        $this->assertNull(RecaptchaRegistry::badge());
        $this->assertNull(RecaptchaRegistry::themeV2());
        $this->assertNull(RecaptchaRegistry::sizeV2());
        $this->assertNull(RecaptchaRegistry::typeV2());
    }

    public function testResolveClientIpReturnsNullForNonStringRemoteAddr(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getServerParams')->willReturn(['REMOTE_ADDR' => ['127.0.0.1']]);

        $provider = $this->createStub(RequestProviderInterface::class);
        $provider->method('get')->willReturn($request);

        RecaptchaRegistry::configure(
            new RecaptchaClient(
                new RecaptchaConfig(siteKeyV2: 'test-key'),
                $this->createStub(ClientInterface::class),
                new Psr17Factory(),
                new Psr17Factory(),
            ),
            $provider,
        );

        $this->assertNull(RecaptchaRegistry::resolveClientIp($provider));
    }

    public function testSetBadgeDefaultStoresBadge(): void
    {
        RecaptchaRegistry::setBadgeDefault(RecaptchaV3Badge::BottomLeft);

        $this->assertSame(RecaptchaV3Badge::BottomLeft, RecaptchaRegistry::badge());
    }

    public function testSetV2DefaultsStoresValues(): void
    {
        RecaptchaRegistry::setV2Defaults(
            theme: RecaptchaV2Theme::Dark,
            size: RecaptchaV2Size::Invisible,
            type: RecaptchaV2Type::Audio,
        );

        $this->assertSame(RecaptchaV2Theme::Dark, RecaptchaRegistry::themeV2());
        $this->assertSame(RecaptchaV2Size::Invisible, RecaptchaRegistry::sizeV2());
        $this->assertSame(RecaptchaV2Type::Audio, RecaptchaRegistry::typeV2());
    }
}
