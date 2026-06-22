# Yii3 reCAPTCHA

[![Packagist Version](https://img.shields.io/packagist/v/yiirocks/recaptcha.svg)](https://packagist.org/packages/yiirocks/recaptcha)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/yiirocks/recaptcha.svg)](https://php.net/)
[![Packagist Downloads](https://img.shields.io/packagist/dt/yiirocks/recaptcha.svg)](https://packagist.org/packages/yiirocks/recaptcha)
[![GitHub License](https://img.shields.io/github/license/yiirocks/recaptcha.svg)](https://github.com/yiirocks/recaptcha/blob/master/LICENSE)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/YiiRocks/recaptcha/build.yml?branch=master)](https://github.com/YiiRocks/recaptcha/actions)

Google reCAPTCHA v2 and v3 field + server-side validator for Yii3.

## Requirements

- PHP 8.3+
- PSR-18 HTTP client
- PSR-17 request + stream factories

## Installation

```bash
composer require yiirocks/recaptcha
```

A PSR-18 client and PSR-17 factories are required. If your application already
has them configured (e.g. via Guzzle, Symfony HTTP Client, or any other
implementation), no further setup is needed.

If not, install any compatible library, for example:

```bash
composer require guzzlehttp/guzzle nyholm/psr7
```

## Configuration

Set your site keys and secrets either via environment variables or directly in
your application's `config/params.php`:

### Environment variables (recommended)

```php
// config/params.php
return [
    'yiirocks/recaptcha' => [
        'siteKeyV2' => $_ENV['RECAPTCHA_SITE_KEY_V2'] ?? '',
        'secretV2'  => $_ENV['RECAPTCHA_SECRET_V2'] ?? '',
        'siteKeyV3' => $_ENV['RECAPTCHA_SITE_KEY_V3'] ?? '',
        'secretV3'  => $_ENV['RECAPTCHA_SECRET_V3'] ?? '',
    ],
];
```

If the env vars are not set the values fall back to empty strings — override
them in your `config/params.php` as needed (the config-plugin merges them).

### All parameters

| Parameter | Default | Description |
|---|---|---|
| `siteKeyV2` | `''` (or `$_ENV`) | reCAPTCHA v2 site key |
| `secretV2` | `''` (or `$_ENV`) | reCAPTCHA v2 secret |
| `siteKeyV3` | `''` (or `$_ENV`) | reCAPTCHA v3 site key |
| `secretV3` | `''` (or `$_ENV`) | reCAPTCHA v3 secret |
| `verifyUrl` | `https://www.google.com/recaptcha/api/siteverify` | Google verification endpoint |
| `sendRemoteIp` | `false` | Send the user's IP to Google for abuse analysis |
| `translation.category` | `recaptcha` | Translation category used by message sources |

> **Tip:** Once your site keys are set, fields will pull them from the
> registry automatically — no need to call `withSiteKey()` in your view code.

### DI Configuration

The config-plugin (`config-plugin` in `composer.json`) wires everything
automatically:

- `RecaptchaClient` receives `RecaptchaConfig` plus PSR services from the container
- `RecaptchaV2RuleHandler` and `RecaptchaV3RuleHandler` are wired with optional
  client, request provider, and translator
- The translation category source is registered with the `translation.categorySource` tag
- The bootstrap callback populates `RecaptchaRegistry` with the client, request
  provider, and translator. This means fields and handlers work out of the box
  — no explicit `withSiteKey()` needed.

## Usage

All fields are rendered with a form model via the `::field()` static method —
the standard Yii3 form field pattern. Validation errors are shown automatically.

### reCAPTCHA v2

```php
use Yiisoft\FormModel\FormModel;
use YiiRocks\Recaptcha\RecaptchaV2Field;
use YiiRocks\Recaptcha\RecaptchaV2Theme;
use YiiRocks\Recaptcha\RecaptchaV2Size;
use YiiRocks\Recaptcha\RecaptchaV2Type;

echo RecaptchaV2Field::field($form, 'captcha')
    ->withTheme(RecaptchaV2Theme::Dark)
    ->withSize(RecaptchaV2Size::Compact)
    ->withType(RecaptchaV2Type::Audio)
    ->withId('my-captcha')
    ->withCallback('onSuccess')
    ->render();
```

Available options:

| Method | Default | Description |
|---|---|---|
| `withSiteKey(string)` | from config | Google reCAPTCHA v2 site key |
| `withId(string)` | `'g-recaptcha-{uniqid}'` | Widget element ID |
| `withTheme(RecaptchaV2Theme)` | `Light` | `Light` or `Dark` |
| `withType(RecaptchaV2Type)` | `Image` | `Image` or `Audio` |
| `withSize(RecaptchaV2Size)` | `Normal` | `Normal`, `Compact`, or `Invisible` |
| `withJsApiUrl(string)` | Google CDN | Custom JS API URL |
| `withCallback(string)` | — | JavaScript callback on success |
| `withExpiredCallback(string)` | — | JavaScript callback on expiry |
| `withErrorCallback(string)` | — | JavaScript callback on error |

### reCAPTCHA v3

The reCAPTCHA token is fetched on form submit (not on page load), preventing
unexpected challenge popups:

```php
use Yiisoft\FormModel\FormModel;
use YiiRocks\Recaptcha\RecaptchaV3Field;
use YiiRocks\Recaptcha\RecaptchaV3Badge;

echo RecaptchaV3Field::field($form, 'captcha')
    ->withAction('login')
    ->withFormId('login-form')
    ->withBadge(RecaptchaV3Badge::Hidden)
    ->render();
```

Available options:

| Method | Default | Description |
|---|---|---|
| `withSiteKey(string)` | from config | Google reCAPTCHA v3 site key |
| `withAction(string)` | `''` | Action name sent to Google (must match the rule's `action` if set) |
| `withFormId(string)` | — | Explicit form ID (auto-resolved via `closest("form")` if omitted) |
| `withBadge(RecaptchaV3Badge)` | `BottomRight` | `BottomRight`, `BottomLeft`, or `Hidden` |
| `withJsApiUrl(string)` | Google CDN | Custom JS API URL |
| `withTranslator(?TranslatorInterface)` | from registry | Translator for the hidden badge legal notice |
| `withExecuteTimeout(?int)` | `15000` (ms) | Fallback form submission timeout (null = disabled) |

**Inherited from `InputField`:**
- `->name(string)` — override the hidden input name (default: auto-derived from form model as `FormName[attribute]`)
- `->inputId(?string)` — override the hidden input ID (default: auto-generated unique ID)

**Container (inherited from `BaseField`):**
- `->containerTag(string)` — wrapper tag (default: `div`)
- `->containerClass(string ...)` — wrapper CSS class(es) (default: `mb-3`)
- `->useContainer(bool)` — enable/disable wrapper (default: `true`)
- `->containerAttributes(array)` — set all wrapper attributes
- `->addContainerAttributes(array)` — merge additional wrapper attributes

> **Hidden badge:** When `Badge::Hidden` is selected, the legal notice text
> ("This site is protected by reCAPTCHA…") is displayed automatically and
> translated when a translator is available (either via `withTranslator()` or
> through `RecaptchaRegistry`).

### Server-side validation

#### v2

```php
use YiiRocks\Recaptcha\RecaptchaV2Rule;

final class ContactForm
{
    #[RecaptchaV2Rule]
    public string $gRecaptchaResponse = '';
}
```

#### v3

```php
use YiiRocks\Recaptcha\RecaptchaV3Rule;

final class LoginForm
{
    #[RecaptchaV3Rule(
        threshold: 0.5,
        action: 'login',
    )]
    public string $gRecaptchaResponse = '';
}
```

> **Important:** If you set `->withAction('...')` on the field, you must also set
> `action: '...'` on the rule with the same value. Otherwise Google will return a
> different action and validation will fail with "The CAPTCHA action does not
> match." If neither is set, no action is sent and the check is skipped entirely.

| v3 rule parameter | Default | Description |
|---|---|---|
| `threshold` | `0.5` | Minimum score (0.0 – 1.0) |
| `action` | `null` | Expected action name (skipped if `null`) |
| `message` | `'The CAPTCHA verification failed.'` | Error message (translatable) |
| `scoreTooLowMessage` | `'The CAPTCHA score is too low.'` | Error when score is below threshold |
| `actionMismatchMessage` | `'The CAPTCHA action does not match.'` | Error when action doesn't match |
| `secret` | `null` | Custom secret (uses config default if `null`) |
| `sendRemoteIp` | `false` | Whether to include the user's IP in verification |

## License

MIT. See [LICENSE.md](LICENSE.md).
