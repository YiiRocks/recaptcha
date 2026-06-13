<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Yiisoft\RequestProvider\RequestProviderInterface;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\RuleHandlerInterface;
use Yiisoft\Validator\RuleInterface;
use Yiisoft\Validator\ValidationContext;
use YiiRocks\Recaptcha\Exception\InvalidRuleException;
use YiiRocks\Recaptcha\Exception\MissingClientException;

final class RecaptchaV3RuleHandler implements RuleHandlerInterface
{
    public function __construct(
        private ?RecaptchaClient $client = null,
        private ?RequestProviderInterface $requestProvider = null,
        private ?TranslatorInterface $translator = null,
        private string $translationCategory = 'yii3-recaptcha',
    ) {}

    public function validate(mixed $value, RuleInterface $rule, ValidationContext $context): Result
    {
        if (!$rule instanceof RecaptchaV3Rule) {
            throw new InvalidRuleException(RecaptchaV3Rule::class, $rule::class);
        }

        $result = new Result();

        if (!is_string($value) || $value === '') {
            $result->addError($this->translate($rule->getMessage()));
        } else {
            $this->verifyToken($value, $rule, $result);
        }

        return $result;
    }

    private function verifyToken(string $value, RecaptchaV3Rule $rule, Result $result): void
    {
        $clientIp = null;
        if ($rule->getSendRemoteIp()) {
            $clientIp = $this->resolveClientIp();
        }

        $client = $this->client ?? RecaptchaRegistry::client();
        if ($client === null) {
            throw new MissingClientException();
        }

        $verificationResult = $rule->getSecret() !== null
            ? $client->verifyWithSecret($value, $rule->getSecret(), $clientIp)
            : $client->verifyV3($value, $clientIp);

        if (!$verificationResult->success) {
            $result->addError(
                $this->translate($rule->getMessage()),
                ['errorCodes' => implode(', ', $verificationResult->errorCodes)],
            );
        } elseif ($verificationResult->score === null || $verificationResult->score < $rule->getThreshold()) {
            $result->addError(
                $this->translate($rule->getScoreTooLowMessage()),
                [
                    'score' => (string) ($verificationResult->score ?? 0.0),
                    'threshold' => (string) $rule->getThreshold(),
                ],
            );
        } elseif ($rule->getAction() !== null && $verificationResult->action !== $rule->getAction()) {
            $result->addError(
                $this->translate($rule->getActionMismatchMessage()),
                [
                    'expected' => $rule->getAction(),
                    'actual' => $verificationResult->action ?? '',
                ],
            );
        }
    }

    private function resolveClientIp(): ?string
    {
        return RecaptchaRegistry::resolveClientIp($this->requestProvider);
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
