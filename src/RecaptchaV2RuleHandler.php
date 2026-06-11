<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Yiisoft\RequestProvider\RequestProviderInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\RuleHandlerInterface;
use Yiisoft\Validator\RuleInterface;
use Yiisoft\Validator\ValidationContext;

final class RecaptchaV2RuleHandler implements RuleHandlerInterface
{
    public function __construct(
        private ?RecaptchaClient $client = null,
        private ?RequestProviderInterface $requestProvider = null,
        private ?TranslatorInterface $translator = null,
        private string $translationCategory = 'yii3-recaptcha',
    ) {}

    public function validate(mixed $value, RuleInterface $rule, ValidationContext $context): Result
    {
        if (!$rule instanceof RecaptchaV2Rule) {
            throw new \RuntimeException(sprintf(
                'Expected %s, got %s.',
                RecaptchaV2Rule::class,
                $rule::class,
            ));
        }

        $result = new Result();

        if (!is_string($value) || $value === '') {
            return $result->addError($this->translate($rule->getMessage()));
        }

        $clientIp = null;
        if ($rule->getSendRemoteIp()) {
            $clientIp = RecaptchaRegistry::resolveClientIp($this->requestProvider);
        }

        $client = $this->client ?? RecaptchaRegistry::client();
        if ($client === null) {
            throw new \RuntimeException('RecaptchaClient is not configured.');
        }

        $verificationResult = $rule->getSecret() !== null
            ? $client->verifyWithSecret($value, $rule->getSecret(), $clientIp)
            : $client->verify($value, $clientIp);

        if (!$verificationResult->success) {
            $result->addError(
                $this->translate($rule->getMessage()),
                ['errorCodes' => implode(', ', $verificationResult->errorCodes)],
            );
        }

        return $result;
    }

    private function translate(string $message): string
    {
        if ($this->translator === null) {
            $this->translator = RecaptchaRegistry::translator();
        }

        if ($this->translator !== null) {
            return $this->translator->translate($message, [], $this->translationCategory);
        }

        return $message;
    }
}
