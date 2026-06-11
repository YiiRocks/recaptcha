# Yii3 reCAPTCHA

[![Packagist Version](https://img.shields.io/packagist/v/yiirocks/recaptcha.svg)](https://packagist.org/packages/yiirocks/recaptcha)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/yiirocks/recaptcha.svg)](https://php.net/)
[![Packagist Downloads](https://img.shields.io/packagist/dt/yiirocks/recaptcha.svg)](https://packagist.org/packages/yiirocks/recaptcha)
[![GitHub License](https://img.shields.io/github/license/yiirocks/recaptcha.svg)](https://github.com/yiirocks/recaptcha/blob/master/LICENSE)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/YiiRocks/recaptcha/build.yml?branch=master)](https://github.com/YiiRocks/recaptcha/actions)

Google reCAPTCHA v2 and v3 widget and server-side validator for Yii3.

## Requirements

- PHP 8.3+
- PSR-18 HTTP client + PSR-17 factories

## Installation

```bash
composer require yiirocks/recaptcha
```

You also need a PSR-18 client and PSR-17 factories:

```bash
composer require guzzlehttp/guzzle nyholm/psr7
```

## Usage

### reCAPTCHA v2

```php
use YiiRocks\Recaptcha\RecaptchaV2;
use YiiRocks\Recaptcha\RecaptchaV2Theme;
use YiiRocks\Recaptcha\RecaptchaV2Size;

echo RecaptchaV2::widget()
    ->withSiteKey($siteKey)
    ->withTheme(RecaptchaV2Theme::Dark)
    ->withSize(RecaptchaV2Size::Normal);
```

### reCAPTCHA v3

```php
use YiiRocks\Recaptcha\RecaptchaV3;

echo RecaptchaV3::widget()
    ->withSiteKey($siteKey)
    ->withAction('login')
    ->withFormId('login-form');
```

### Validation

```php
use YiiRocks\Recaptcha\RecaptchaV2Rule;

class LoginForm
{
    #[RecaptchaV2Rule]
    public string $gRecaptchaResponse = '';
}
```

## License

MIT. See [LICENSE.md](LICENSE.md).
