<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Yiisoft\Validator\Result;
use Yiisoft\Validator\RuleInterface;
use Override;

final class RecaptchaV2RuleHandler extends AbstractRecaptchaRuleHandler
{
    #[Override]
    protected function isConfigured(): bool
    {
        $config = $this->resolveClient()->getConfig();

        return $config->siteKeyV2 !== '' && $config->secretV2 !== '';
    }

    #[Override]
    protected function ruleClass(): string
    {
        return RecaptchaV2Rule::class;
    }

    #[Override]
    protected function supports(RuleInterface $rule): bool
    {
        return $rule instanceof RecaptchaV2Rule;
    }

    /**
     * @param RecaptchaV2Rule $rule
     */
    #[Override]
    protected function verifyToken(string $value, RuleInterface $rule, Result $result): void
    {
        $clientIp = $rule->getSendRemoteIp() ? $this->resolveClientIp() : null;
        $client = $this->resolveClient();

        $secret = $rule->getSecret();
        $verificationResult = $secret !== null
            ? $client->verifyWithSecret($value, $secret, $clientIp)
            : $client->verify($value, $clientIp);

        if (!$verificationResult->success) {
            $result->addError(
                $this->translate($rule->getMessage()),
                ['errorCodes' => implode(', ', $verificationResult->errorCodes)],
            );
        }
    }
}
