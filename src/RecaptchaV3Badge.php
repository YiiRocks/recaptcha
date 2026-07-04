<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

enum RecaptchaV3Badge: string
{
    case BottomLeft = 'bottomleft';
    case BottomRight = 'bottomright';
    case Hidden = 'hidden';
}
