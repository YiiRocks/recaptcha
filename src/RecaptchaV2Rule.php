<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Attribute;
use Closure;
use Yiisoft\Validator\Rule\Trait\SkipOnEmptyTrait;
use Yiisoft\Validator\Rule\Trait\SkipOnErrorTrait;
use Yiisoft\Validator\Rule\Trait\WhenTrait;
use Yiisoft\Validator\RuleInterface;
use Yiisoft\Validator\SkipOnEmptyInterface;
use Yiisoft\Validator\SkipOnErrorInterface;
use Yiisoft\Validator\WhenInterface;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class RecaptchaV2Rule implements RuleInterface, SkipOnEmptyInterface, SkipOnErrorInterface, WhenInterface
{
    use SkipOnEmptyTrait;
    use SkipOnErrorTrait;
    use WhenTrait;

    /**
     * @param bool|callable(mixed, bool):bool|null $skipOnEmpty
     */
    public function __construct(
        private readonly string $message = 'The CAPTCHA verification failed.',
        private readonly ?string $secret = null,
        private readonly bool $sendRemoteIp = false,
        bool|callable|null $skipOnEmpty = null,
        private readonly bool $skipOnError = false,
        private readonly ?Closure $when = null,
    ) {
        $this->skipOnEmpty = $skipOnEmpty;
    }

    #[\Override]
    public function getHandler(): string
    {
        return RecaptchaV2RuleHandler::class;
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
