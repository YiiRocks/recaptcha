<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Yiisoft\Validator\Result;
use Yiisoft\Validator\RuleInterface;

final class RecaptchaV3RuleHandler extends AbstractRecaptchaRuleHandler
{
    #[\Override]
    protected function ruleClass(): string
    {
        return RecaptchaV3Rule::class;
    }

    #[\Override]
    protected function supports(RuleInterface $rule): bool
    {
        return $rule instanceof RecaptchaV3Rule;
    }

    /**
     * @param RecaptchaV3Rule $rule
     */
    #[\Override]
    protected function verifyToken(string $value, RuleInterface $rule, Result $result): void
    {
        $clientIp = $rule->getSendRemoteIp() ? $this->resolveClientIp() : null;
        $client = $this->resolveClient();

        $secret = $rule->getSecret();
        $verificationResult = $secret !== null
            ? $client->verifyWithSecret($value, $secret, $clientIp)
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
}
