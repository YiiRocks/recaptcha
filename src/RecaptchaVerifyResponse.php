<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

/**
 * Represents the parsed response from Google's reCAPTCHA verification API.
 */
final class RecaptchaVerifyResponse
{
    /**
     * @param bool        $success    Whether the challenge was passed.
     * @param float|null  $score      v3 only: score from 0.0 (bot) to 1.0 (human).
     * @param string|null $action     v3 only: action name submitted with the token.
     * @param string|null $hostname   Hostname of the site where the challenge was solved.
     * @param string[]    $errorCodes List of error codes from Google.
     */
    public function __construct(
        private readonly bool    $success,
        private readonly ?float  $score = null,
        private readonly ?string $action = null,
        private readonly ?string $hostname = null,
        private readonly array   $errorCodes = [],
    ) {}

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromApiPayload(array $payload): self
    {
        /** @var string[] $errorCodes */
        $errorCodes = isset($payload['error-codes']) && is_array($payload['error-codes'])
            ? $payload['error-codes']
            : [];

        return new self(
            success: (bool) ($payload['success'] ?? false),
            score: isset($payload['score']) ? (float) $payload['score'] : null,
            action: isset($payload['action']) && is_string($payload['action']) ? $payload['action'] : null,
            hostname: isset($payload['hostname']) && is_string($payload['hostname']) ? $payload['hostname'] : null,
            errorCodes: $errorCodes,
        );
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getScore(): ?float
    {
        return $this->score;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function getHostname(): ?string
    {
        return $this->hostname;
    }

    /** @return string[] */
    public function getErrorCodes(): array
    {
        return $this->errorCodes;
    }

    public function isAboveThreshold(float $threshold): bool
    {
        return $this->score !== null && $this->score >= $threshold;
    }
}
