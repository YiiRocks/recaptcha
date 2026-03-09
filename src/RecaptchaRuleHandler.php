<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Form;

use YiiRocks\Recaptcha\RecaptchaClient;
use YiiRocks\Recaptcha\RecaptchaConfig;
use Yiisoft\Validator\Exception\UnexpectedRuleException;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\RuleHandlerInterface;
use Yiisoft\Validator\RuleInterface;
use Yiisoft\Validator\ValidationContext;

final class RecaptchaRuleHandler implements RuleHandlerInterface
{
    public function __construct(
        private readonly RecaptchaClient $client,
        private readonly RecaptchaConfig $config,
    ) {}

    #[\Override]
    public function validate(mixed $value, RuleInterface $rule, ValidationContext $context): Result
    {
        if (!$rule instanceof RecaptchaRule) {
            throw new UnexpectedRuleException(RecaptchaRule::class, $rule);
        }

        $result = new Result();

        if (!is_string($value) || $value === '') {
            return $result->addError($rule->getEmptyMessage());
        }

        $verifyResponse = $this->client->verify($value, $rule->getRemoteIp());

        if (!$verifyResponse->isSuccess()) {
            return $result->addError($rule->getMessage());
        }

        if ($this->config->isV3() && !$verifyResponse->isAboveThreshold($this->config->getV3ScoreThreshold())) {
            return $result->addError($rule->getMessage());
        }

        return $result;
    }
}
