<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use YiiRocks\Recaptcha\RecaptchaV3Rule;
use YiiRocks\Recaptcha\RecaptchaV3RuleHandler;

final class RecaptchaV3RuleTest extends TestCase
{
    public function testAllowsThresholdBoundaries(): void
    {
        $this->assertSame(0.0, (new RecaptchaV3Rule(threshold: 0.0))->getThreshold());
        $this->assertSame(1.0, (new RecaptchaV3Rule(threshold: 1.0))->getThreshold());
    }

    public function testCustomValues(): void
    {
        $rule = new RecaptchaV3Rule(
            message: 'Custom message',
            scoreTooLowMessage: 'Custom score message',
            actionMismatchMessage: 'Custom action message',
            secret: 'custom-secret',
            threshold: 0.7,
            action: 'login',
            sendRemoteIp: true,
            skipOnError: true,
        );

        $this->assertSame('Custom message', $rule->getMessage());
        $this->assertSame('Custom score message', $rule->getScoreTooLowMessage());
        $this->assertSame('Custom action message', $rule->getActionMismatchMessage());
        $this->assertSame('custom-secret', $rule->getSecret());
        $this->assertSame(0.7, $rule->getThreshold());
        $this->assertSame('login', $rule->getAction());
        $this->assertTrue($rule->getSendRemoteIp());
        $this->assertTrue($rule->shouldSkipOnError());
    }

    public function testDefaults(): void
    {
        $rule = new RecaptchaV3Rule();

        $this->assertSame('The CAPTCHA verification failed.', $rule->getMessage());
        $this->assertSame('The CAPTCHA score is too low.', $rule->getScoreTooLowMessage());
        $this->assertSame('The CAPTCHA action does not match.', $rule->getActionMismatchMessage());
        $this->assertNull($rule->getSecret());
        $this->assertSame(0.5, $rule->getThreshold());
        $this->assertNull($rule->getAction());
        $this->assertFalse($rule->getSendRemoteIp());
        $this->assertFalse($rule->shouldSkipOnError());
        $this->assertSame(RecaptchaV3RuleHandler::class, $rule->getHandler());
    }

    public function testThrowsWhenThresholdAboveOne(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RecaptchaV3Rule(threshold: 1.1);
    }

    public function testThrowsWhenThresholdBelowZero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RecaptchaV3Rule(threshold: -0.1);
    }
}
