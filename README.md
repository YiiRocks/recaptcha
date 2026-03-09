# Extension Template

This extension does or provides something that will be described here.

[![Packagist Version](https://img.shields.io/packagist/v/yiirocks/recaptcha.svg)](https://packagist.org/packages/yiirocks/recaptcha)
[![PHP from Packagist](https://img.shields.io/packagist/php-v/yiirocks/recaptcha.svg)](https://php.net/)
[![Packagist](https://img.shields.io/packagist/dt/yiirocks/recaptcha.svg)](https://packagist.org/packages/yiirocks/recaptcha)
[![GitHub](https://img.shields.io/github/license/yiirocks/recaptcha.svg)](https://github.com/yiirocks/recaptcha/blob/master/LICENSE)

## Installation

The package could be installed via composer:

```bash
composer require yiirocks/recaptcha
```

## Usage

Copy `config/params.php` into your app's params and fill in your Google keys:

```php
// config/params.php
return [
    'recaptcha' => [
        'siteKey'   => $_ENV['RECAPTCHA_SITE_KEY'],
        'secretKey' => $_ENV['RECAPTCHA_SECRET_KEY'],
        'version'   => \YiiRocks\Recaptcha\RecaptchaConfig::VERSION_V2_CHECKBOX,
        // v3 only:
        // 'v3ScoreThreshold' => 0.5,
        // 'v3Action'         => 'submit',
    ],
];
```

Available versions:
- `RecaptchaConfig::VERSION_V2_CHECKBOX` *(default)*
- `RecaptchaConfig::VERSION_V2_INVISIBLE`
- `RecaptchaConfig::VERSION_V3`

---

## Usage

### 1. Rendering the widget in a view

```php
use YiiRocks\Recaptcha\Widget\RecaptchaWidget;

// Injected via DI or fetched from container
echo RecaptchaWidget::create($config)->render();

// With options
echo RecaptchaWidget::create($config)
    ->withTheme('dark')          // 'light' (default) | 'dark'
    ->withSize('compact')        // 'normal' (default) | 'compact'
    ->withCallback('myCallback') // JS function name called on success
    ->render();
```

### 2. Validating via the form rule

```php
use Yiisoft\Validator\Rule\Required;
use YiiRocks\Recaptcha\Form\RecaptchaRule;

final class ContactForm
{
    #[Required]
    public string $name = '';

    #[RecaptchaRule(message: 'Please complete the CAPTCHA.')]
    public string $recaptchaToken = '';
}
```

In your action:

```php
public function contact(
    ServerRequestInterface $request,
    ValidatorInterface $validator,
): ResponseInterface {
    $form = new ContactForm();
    $body = $request->getParsedBody();
    $form->recaptchaToken = $body['g-recaptcha-response'] ?? '';

    $result = $validator->validate($form);
    if (!$result->isValid()) {
        // handle errors …
    }
    // …
}
```

### 3. PSR-15 middleware (route-level)

```php
use YiiRocks\Recaptcha\Middleware\RecaptchaMiddleware;

// In your route configuration:
$router->post('/contact', ContactAction::class)
       ->middleware(RecaptchaMiddleware::class);
```

The middleware returns a `400 JSON` response on failure and attaches a
`RecaptchaVerifyResponse` instance to `$request->getAttribute('recaptcha_response')`
on success.

### 4. Direct client usage

```php
use YiiRocks\Recaptcha\RecaptchaClient;

class ContactAction
{
    public function __construct(private readonly RecaptchaClient $recaptcha) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $token    = $request->getParsedBody()['g-recaptcha-response'] ?? '';
        $response = $this->recaptcha->verify($token, $request->getServerParams()['REMOTE_ADDR'] ?? null);

        if (!$response->isSuccess()) {
            // handle failure
        }
        // …
    }
}
```

## Unit testing

The package is tested with [PHPUnit](https://phpunit.de/). To run tests:

```bash
./vendor/bin/phpunit
```

[![Code Climate maintainability](https://img.shields.io/codeclimate/maintainability/YiiRocks/recaptcha.svg)](https://codeclimate.com/github/YiiRocks/recaptcha/maintainability)
[![Codacy branch grade](https://img.shields.io/codacy/grade/<uniqid>/master.svg)](https://app.codacy.com/gh/YiiRocks/recaptcha)
[![Scrutinizer code quality (GitHub/Bitbucket)](https://img.shields.io/scrutinizer/quality/g/yiirocks/recaptcha/master.svg)](https://scrutinizer-ci.com/g/yiirocks/recaptcha/?branch=master)
![GitHub Workflow Status](https://img.shields.io/github/workflow/status/yiirocks/recaptcha/analysis)
