<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use PHPUnit\Framework\TestCase;
use YiiRocks\Recaptcha\RecaptchaV2Rule;
use YiiRocks\Recaptcha\RecaptchaV2RuleHandler;

final class RecaptchaV2RuleTest extends TestCase
{
    public function testCustomValues(): void
    {
        $rule = new RecaptchaV2Rule(
            message: 'Custom message',
            secret: 'custom-secret',
            sendRemoteIp: true,
            skipOnError: true,
        );

        $this->assertSame('Custom message', $rule->getMessage());
        $this->assertSame('custom-secret', $rule->getSecret());
        $this->assertTrue($rule->getSendRemoteIp());
        $this->assertTrue($rule->shouldSkipOnError());
    }

    public function testDefaults(): void
    {
        $rule = new RecaptchaV2Rule();

        $this->assertSame('The CAPTCHA verification failed.', $rule->getMessage());
        $this->assertNull($rule->getSecret());
        $this->assertFalse($rule->getSendRemoteIp());
        $this->assertFalse($rule->shouldSkipOnError());
        $this->assertSame(RecaptchaV2RuleHandler::class, $rule->getHandler());
    }
}
