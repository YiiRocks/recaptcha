<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Closure;
use Yiisoft\Validator\Rule\Trait\SkipOnEmptyTrait;
use Yiisoft\Validator\Rule\Trait\SkipOnErrorTrait;
use Yiisoft\Validator\Rule\Trait\WhenTrait;
use Yiisoft\Validator\RuleInterface;
use Yiisoft\Validator\SkipOnEmptyInterface;
use Yiisoft\Validator\SkipOnErrorInterface;
use Yiisoft\Validator\WhenInterface;

abstract class AbstractRecaptchaRule implements RuleInterface, SkipOnEmptyInterface, SkipOnErrorInterface, WhenInterface
{
    use SkipOnEmptyTrait;
    use SkipOnErrorTrait;
    use WhenTrait;

    /**
     * @param bool|callable(mixed, bool):bool|null $skipOnEmpty
     */
    public function __construct(
        protected readonly string $message = 'The CAPTCHA verification failed.',
        protected readonly ?string $secret = null,
        protected readonly bool $sendRemoteIp = false,
        bool|callable|null $skipOnEmpty = null,
        protected readonly bool $skipOnError = false,
        protected readonly ?Closure $when = null,
    ) {
        $this->skipOnEmpty = $skipOnEmpty;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getSecret(): ?string
    {
        return $this->secret;
    }

    public function getSendRemoteIp(): bool
    {
        return $this->sendRemoteIp;
    }
}
