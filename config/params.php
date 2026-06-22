<?php

declare(strict_types=1);

return [
    'yiirocks/recaptcha' => [
        'siteKeyV2' => $_ENV['RECAPTCHA_SITE_KEY_V2'] ?? '',
        'secretV2' => $_ENV['RECAPTCHA_SECRET_V2'] ?? '',
        'siteKeyV3' => $_ENV['RECAPTCHA_SITE_KEY_V3'] ?? '',
        'secretV3' => $_ENV['RECAPTCHA_SECRET_V3'] ?? '',
        'verifyUrl' => 'https://www.google.com/recaptcha/api/siteverify',
        'sendRemoteIp' => false,
        'translation.category' => 'recaptcha',
        'container' => [
            'useContainer' => true,
            'tag' => 'div',
            'attributes' => ['class' => 'mb-3'],
        ],
    ],
];
