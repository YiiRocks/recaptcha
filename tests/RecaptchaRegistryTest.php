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
}
