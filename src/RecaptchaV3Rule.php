<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Attribute;
use Closure;
use InvalidArgumentException;
use Override;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class RecaptchaV3Rule extends AbstractRecaptchaRule
{
    /**
     * @param bool|callable(mixed, bool):bool|null $skipOnEmpty
     */
    public function __construct(
        string $message = 'The CAPTCHA verification failed.',
        private readonly string $scoreTooLowMessage = 'The CAPTCHA score is too low.',
        private readonly string $actionMismatchMessage = 'The CAPTCHA action does not match.',
        ?string $secret = null,
        private readonly float $threshold = 0.5,
        private readonly ?string $action = null,
        bool $sendRemoteIp = false,
        bool|callable|null $skipOnEmpty = null,
        bool $skipOnError = false,
        ?Closure $when = null,
    ) {
        parent::__construct(
            message: $message,
            secret: $secret,
            sendRemoteIp: $sendRemoteIp,
            skipOnEmpty: $skipOnEmpty,
            skipOnError: $skipOnError,
            when: $when,
        );

        if ($this->threshold < 0.0 || $this->threshold > 1.0) {
            throw new InvalidArgumentException('Threshold must be between 0.0 and 1.0.');
        }
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function getActionMismatchMessage(): string
    {
        return $this->actionMismatchMessage;
    }

    #[Override]
    public function getHandler(): string
    {
        return RecaptchaV3RuleHandler::class;
    }

    public function getScoreTooLowMessage(): string
    {
        return $this->scoreTooLowMessage;
    }

    public function getThreshold(): float
    {
        return $this->threshold;
    }
}
