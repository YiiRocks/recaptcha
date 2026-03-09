<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

/**
 * Holds reCAPTCHA configuration for v2 and v3.
 */
final class RecaptchaConfig
{
    public const VERSION_V2_CHECKBOX  = 'v2_checkbox';
    public const VERSION_V2_INVISIBLE = 'v2_invisible';
    public const VERSION_V3           = 'v3';

    public function __construct(
        private readonly string $siteKey,
        private readonly string $secretKey,
        private readonly string $version = self::VERSION_V2_CHECKBOX,
        private readonly float  $v3ScoreThreshold = 0.5,
        private readonly string $v3Action = 'submit',
        private readonly string $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify',
        private readonly int    $timeout = 10,
    ) {}

    public function getSiteKey(): string
    {
        return $this->siteKey;
    }

    public function getSecretKey(): string
    {
        return $this->secretKey;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getV3ScoreThreshold(): float
    {
        return $this->v3ScoreThreshold;
    }

    public function getV3Action(): string
    {
        return $this->v3Action;
    }

    public function getVerifyUrl(): string
    {
        return $this->verifyUrl;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function isV3(): bool
    {
        return $this->version === self::VERSION_V3;
    }

    public function isV2Invisible(): bool
    {
        return $this->version === self::VERSION_V2_INVISIBLE;
    }
}
