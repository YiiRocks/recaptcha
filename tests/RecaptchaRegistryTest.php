<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use YiiRocks\Recaptcha\RecaptchaClient;
use YiiRocks\Recaptcha\RecaptchaConfig;
use YiiRocks\Recaptcha\RecaptchaRegistry;

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
}
