<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use PHPUnit\Framework\TestCase;
use YiiRocks\Recaptcha\RecaptchaVerifyResponse;

final class RecaptchaTest extends TestCase
{
    public function testSuccessfulV2Response(): void
    {
        $response = RecaptchaVerifyResponse::fromApiPayload([
            'success'  => true,
            'hostname' => 'example.com',
        ]);

        self::assertTrue($response->isSuccess());
        self::assertSame('example.com', $response->getHostname());
        self::assertNull($response->getScore());
        self::assertEmpty($response->getErrorCodes());
    }

    public function testFailedResponseWithErrorCodes(): void
    {
        $response = RecaptchaVerifyResponse::fromApiPayload([
            'success'     => false,
            'error-codes' => ['invalid-input-response'],
        ]);

        self::assertFalse($response->isSuccess());
        self::assertContains('invalid-input-response', $response->getErrorCodes());
    }

    public function testV3ScoreThreshold(): void
    {
        $response = RecaptchaVerifyResponse::fromApiPayload([
            'success' => true,
            'score'   => 0.3,
            'action'  => 'submit',
        ]);

        self::assertSame(0.3, $response->getScore());
        self::assertFalse($response->isAboveThreshold(0.5));
        self::assertTrue($response->isAboveThreshold(0.2));
    }
}
