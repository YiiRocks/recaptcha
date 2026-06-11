<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

enum RecaptchaV2Type: string
{
    case Image = 'image';
    case Audio = 'audio';
}
