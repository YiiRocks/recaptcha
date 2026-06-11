<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

enum RecaptchaV3Badge: string
{
    case BottomRight = 'bottomright';
    case BottomLeft = 'bottomleft';
    case Hidden = 'hidden';
}
