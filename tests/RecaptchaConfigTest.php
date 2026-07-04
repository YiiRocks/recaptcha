<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use PHPUnit\Framework\TestCase;
use YiiRocks\Recaptcha\RecaptchaConfig;

final class RecaptchaConfigTest extends TestCase
{
    public function testCustomValues(): void
    {
        $config = new RecaptchaConfig(
            siteKeyV2: 'v2-site-key',
            secretV2: 'v2-secret',
            siteKeyV3: 'v3-site-key',
            secretV3: 'v3-secret',
            verifyUrl: 'https://custom.example.com/verify',
            sendRemoteIp: true,
        );

        $this->assertSame('v2-site-key', $config->siteKeyV2);
        $this->assertSame('v2-secret', $config->secretV2);
        $this->assertSame('v3-site-key', $config->siteKeyV3);
        $this->assertSame('v3-secret', $config->secretV3);
        $this->assertSame('https://custom.example.com/verify', $config->verifyUrl);
        $this->assertTrue($config->sendRemoteIp);
    }

    public function testDefaultValues(): void
    {
        $config = new RecaptchaConfig();

        $this->assertSame('', $config->siteKeyV2);
        $this->assertSame('', $config->secretV2);
        $this->assertSame('', $config->siteKeyV3);
        $this->assertSame('', $config->secretV3);
        $this->assertSame('https://www.google.com/recaptcha/api/siteverify', $config->verifyUrl);
        $this->assertFalse($config->sendRemoteIp);
    }
}
