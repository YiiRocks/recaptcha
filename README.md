# Yii3 reCAPTCHA

Google reCAPTCHA v2 and v3 form field + server-side validator for Yii3.

[![Packagist Version](https://img.shields.io/packagist/v/yiirocks/recaptcha.svg)](https://packagist.org/packages/yiirocks/recaptcha)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/yiirocks/recaptcha.svg)](https://php.net/)
[![Packagist Downloads](https://img.shields.io/packagist/dt/yiirocks/recaptcha.svg)](https://packagist.org/packages/yiirocks/recaptcha)
[![GitHub License](https://img.shields.io/github/license/yiirocks/recaptcha.svg)](https://github.com/yiirocks/recaptcha/blob/main/LICENSE.md)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/YiiRocks/recaptcha/build.yml?branch=main)](https://github.com/YiiRocks/recaptcha/actions)

Stats for Nerds

[![Coverage](https://img.shields.io/endpoint?url=https%3A%2F%2Fraw.githubusercontent.com%2Fyiirocks%2Frecaptcha%2Fbadges%2Fcoverage.json)](https://github.com/yiirocks/recaptcha/tree/badges)
[![MSI](https://img.shields.io/endpoint?url=https%3A%2F%2Fraw.githubusercontent.com%2Fyiirocks%2Frecaptcha%2Fbadges%2Fmsi.json)](https://github.com/yiirocks/recaptcha/tree/badges)
[![Tests](https://img.shields.io/endpoint?url=https%3A%2F%2Fraw.githubusercontent.com%2Fyiirocks%2Frecaptcha%2Fbadges%2Ftests.json)](https://github.com/yiirocks/recaptcha/tree/badges)
[![Assertions](https://img.shields.io/endpoint?url=https%3A%2F%2Fraw.githubusercontent.com%2Fyiirocks%2Frecaptcha%2Fbadges%2Fassertions.json)](https://github.com/yiirocks/recaptcha/tree/badges)

---

## Features

- **reCAPTCHA v2** — checkbox/invisible widget field with theme, size, and type options
- **reCAPTCHA v3** — score-based field that fetches its token on form submit (not page load), avoiding surprise challenge popups
- **Server-side validation** — PHP attribute rules (`RecaptchaV2Rule` / `RecaptchaV3Rule`) verified against Google's siteverify endpoint
- **Score threshold + action matching** — v3 rules can enforce a minimum score and an expected action name
- **Zero-config ergonomics** — once site keys are set, fields and rules work out of the box via a static registry, no explicit DI wiring needed in views
- **i18n** — validation and legal-notice messages are translated through Yii Translator

## Requirements

- PHP 8.3+
- PSR-17 request + stream factories
- PSR-18 HTTP client

## Installation

```bash
composer require yiirocks/recaptcha
```

A PSR-18 client and PSR-17 factories are required. If your application already
has them configured (e.g. via Guzzle, Symfony HTTP Client, or any other
implementation), no further setup is needed. If not, install any compatible
library, for example:

```bash
composer require guzzlehttp/guzzle nyholm/psr7
```

## Documentation

The complete reference guide is available at [Yii.Rocks](https://www.yii.rocks/recaptcha/).
