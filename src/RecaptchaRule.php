<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Form;

use Attribute;
use Yiisoft\Validator\Rule\Trait\SkipOnEmptyTrait;
use Yiisoft\Validator\Rule\Trait\SkipOnErrorTrait;
use Yiisoft\Validator\RuleInterface;
use Yiisoft\Validator\SkipOnEmptyInterface;
use Yiisoft\Validator\SkipOnErrorInterface;

/**
 * Validation rule attribute to apply reCAPTCHA verification.
 *
 * Usage on a form model:
 *
 * ```php
 * #[RecaptchaRule(message: 'Please complete the CAPTCHA.')]
 * public string $recaptchaToken = '';
 * ```
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class RecaptchaRule implements RuleInterface, SkipOnEmptyInterface, SkipOnErrorInterface
{
    use SkipOnEmptyTrait;
    use SkipOnErrorTrait;

    public function __construct(
        private readonly string  $message = 'reCAPTCHA verification failed. Please try again.',
        private readonly string  $emptyMessage = 'reCAPTCHA token is required.',
        private readonly ?string $remoteIp = null,
        private readonly bool    $skipOnEmpty = false,
        private readonly bool    $skipOnError = false,
    ) {}

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getEmptyMessage(): string
    {
        return $this->emptyMessage;
    }

    public function getRemoteIp(): ?string
    {
        return $this->remoteIp;
    }

    #[\Override]
    public function getHandler(): string
    {
        return RecaptchaRuleHandler::class;
    }

    public function getName(): string
    {
        return 'recaptcha';
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return [
            'message'      => $this->message,
            'emptyMessage' => $this->emptyMessage,
            'skipOnEmpty'  => $this->skipOnEmpty,
            'skipOnError'  => $this->skipOnError,
        ];
    }
}
