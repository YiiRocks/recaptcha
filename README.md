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

### 1. Rendering the widget in a view

Inject `RecaptchaWidget` into your action — no config needed in the view:

```php
use YiiRocks\Recaptcha\Widget\RecaptchaWidget;

class ContactAction
{
    public function __construct(private readonly RecaptchaWidget $recaptcha) {}

    public function __invoke(): ResponseInterface
    {
        // pass $this->recaptcha to the view
    }
}
```

Then in the view:

```php
echo $recaptcha->render();

// With options:
echo $recaptcha
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

The package is tested with [Psalm](https://psalm.dev/) and [PHPUnit](https://phpunit.de/). To run tests:

```bash
composer psalm
composer phpunit
```

[![Code Climate maintainability](https://img.shields.io/codeclimate/maintainability/YiiRocks/recaptcha.svg)](https://codeclimate.com/github/YiiRocks/recaptcha/maintainability)
[![Codacy branch grade](https://img.shields.io/codacy/grade/<uniqid>/master.svg)](https://app.codacy.com/gh/YiiRocks/recaptcha)
![GitHub Workflow Status](https://img.shields.io/github/workflow/status/yiirocks/recaptcha/analysis)
