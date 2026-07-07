<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use YiiRocks\Recaptcha\Exception\InvalidRuleException;
use YiiRocks\Recaptcha\Exception\MissingClientException;
use Yiisoft\RequestProvider\RequestProviderInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\RuleHandlerInterface;
use Yiisoft\Validator\RuleInterface;
use Yiisoft\Validator\ValidationContext;

abstract class AbstractRecaptchaRuleHandler implements RuleHandlerInterface
{
    public function __construct(
        private ?RecaptchaClient $client = null,
        private ?RequestProviderInterface $requestProvider = null,
        private ?TranslatorInterface $translator = null,
        private string $translationCategory = 'recaptcha',
    ) {
    }

    #[\Override]
    public function validate(mixed $value, RuleInterface $rule, ValidationContext $context): Result
    {
        if (!$rule instanceof AbstractRecaptchaRule || !$this->supports($rule)) {
            throw new InvalidRuleException($this->ruleClass(), $rule::class);
        }

        $result = new Result();

        if (!is_string($value) || $value === '') {
            $result->addError($this->translate($rule->getMessage()));
        } else {
            $this->verifyToken($value, $rule, $result);
        }

        return $result;
    }

    protected function resolveClient(): RecaptchaClient
    {
        $client = $this->client ?? RecaptchaRegistry::client();
        if ($client === null) {
            throw new MissingClientException();
        }

        return $client;
    }

    protected function resolveClientIp(): ?string
    {
        return RecaptchaRegistry::resolveClientIp($this->requestProvider);
    }

    abstract protected function ruleClass(): string;

    abstract protected function supports(RuleInterface $rule): bool;

    protected function translate(string $message): string
    {
        if ($this->translator === null) {
            $this->translator = RecaptchaRegistry::translator();
        }

        if ($this->translator !== null) {
            return $this->translator->translate($message, [], $this->translationCategory);
        }

        return $message;
    }

    abstract protected function verifyToken(string $value, RuleInterface $rule, Result $result): void;
}
