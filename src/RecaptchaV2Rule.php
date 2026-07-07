<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
final class RecaptchaV2Rule extends AbstractRecaptchaRule
{
    #[\Override]
    public function getHandler(): string
    {
        return RecaptchaV2RuleHandler::class;
    }
}
