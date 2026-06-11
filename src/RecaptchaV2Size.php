<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

enum RecaptchaV2Size: string
{
    case Normal = 'normal';
    case Compact = 'compact';
    case Invisible = 'invisible';
}
