<?php

declare(strict_types=1);

use YiiRocks\Recaptcha\RecaptchaV2Size;
use YiiRocks\Recaptcha\RecaptchaV2Theme;
use YiiRocks\Recaptcha\RecaptchaV2Type;
use YiiRocks\Recaptcha\RecaptchaV3Badge;

return [
    'yiirocks/recaptcha' => [
        'siteKeyV2' => $_ENV['RECAPTCHA_SITE_KEY_V2'] ?? '',
        'secretV2' => $_ENV['RECAPTCHA_SECRET_V2'] ?? '',
        'siteKeyV3' => $_ENV['RECAPTCHA_SITE_KEY_V3'] ?? '',
        'secretV3' => $_ENV['RECAPTCHA_SECRET_V3'] ?? '',
        'verifyUrl' => 'https://www.google.com/recaptcha/api/siteverify',
        'sendRemoteIp' => false,
        'themeV2' => RecaptchaV2Theme::Light,
        'sizeV2' => RecaptchaV2Size::Normal,
        'typeV2' => RecaptchaV2Type::Image,
        'badgeV3' => RecaptchaV3Badge::BottomRight,
        'container' => [
            'useContainer' => true,
            'tag' => 'div',
            'attributes' => ['class' => 'mb-3'],
        ],
    ],
];
