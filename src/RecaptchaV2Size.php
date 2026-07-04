<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

enum RecaptchaV2Size: string
{
    case Compact = 'compact';
    case Invisible = 'invisible';
    case Normal = 'normal';
}
