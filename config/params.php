<?php

declare(strict_types=1);

/**
 * Copy this file into your application's config/params.php (or a dedicated
 * config/recaptcha.php that is merged into params) and fill in your keys.
 *
 * All values can also be provided via environment variables.
 */

use YiiRocks\Recaptcha\RecaptchaConfig;

return [
    'yiirocks/recaptcha' => [
        // ── Required ────────────────────────────────────────────────────────
        'siteKey'   => $_ENV['RECAPTCHA_SITE_KEY']   ?? 'YOUR_SITE_KEY',
        'secretKey' => $_ENV['RECAPTCHA_SECRET_KEY'] ?? 'YOUR_SECRET_KEY',

        // ── Version: v2_checkbox | v2_invisible | v3  (default: v2_checkbox)
        'version'   => $_ENV['RECAPTCHA_VERSION'] ?? RecaptchaConfig::VERSION_V2_CHECKBOX,

        // ── v3 only: minimum score to consider human (0.0 – 1.0) ──────────
        'v3ScoreThreshold' => 0.5,

        // ── v3 only: action name sent with each token ─────────────────────
        'v3Action' => 'submit',

        // ── Advanced ────────────────────────────────────────────────────────
        'verifyUrl' => 'https://www.google.com/recaptcha/api/siteverify',
        'timeout'   => 10,
    ],
];
