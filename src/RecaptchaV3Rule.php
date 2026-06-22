<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Attribute;
use Closure;
use Yiisoft\Validator\RuleInterface;
use Yiisoft\Validator\Rule\Trait\SkipOnEmptyTrait;
use Yiisoft\Validator\Rule\Trait\SkipOnErrorTrait;
use Yiisoft\Validator\Rule\Trait\WhenTrait;
use Yiisoft\Validator\SkipOnEmptyInterface;
use Yiisoft\Validator\SkipOnErrorInterface;
use Yiisoft\Validator\WhenInterface;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class RecaptchaV3Rule implements RuleInterface, SkipOnEmptyInterface, SkipOnErrorInterface, WhenInterface
{
    use SkipOnEmptyTrait;
    use SkipOnErrorTrait;
    use WhenTrait;

    /**
     * @param bool|callable(mixed, bool):bool|null $skipOnEmpty
     */
    public function __construct(
        private readonly string $message = 'The CAPTCHA verification failed.',
        private readonly string $scoreTooLowMessage = 'The CAPTCHA score is too low.',
        private readonly string $actionMismatchMessage = 'The CAPTCHA action does not match.',
        private readonly ?string $secret = null,
        private readonly float $threshold = 0.5,
        private readonly ?string $action = null,
        private readonly bool $sendRemoteIp = false,
        bool|callable|null $skipOnEmpty = null,
        private readonly bool $skipOnError = false,
        private readonly ?Closure $when = null,
    ) {
        if ($this->threshold < 0.0 || $this->threshold > 1.0) {
            throw new \InvalidArgumentException('Threshold must be between 0.0 and 1.0.');
        }

        $this->skipOnEmpty = $skipOnEmpty;
    }

    public function getHandler(): string
    {
        return RecaptchaV3RuleHandler::class;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getScoreTooLowMessage(): string
    {
        return $this->scoreTooLowMessage;
    }

    public function getActionMismatchMessage(): string
    {
        return $this->actionMismatchMessage;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function getThreshold(): float
    {
        return $this->threshold;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function getSendRemoteIp(): bool
    {
        return $this->sendRemoteIp;
    }
}
