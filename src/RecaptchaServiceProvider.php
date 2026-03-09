<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use YiiRocks\Recaptcha\Form\RecaptchaRule;
use YiiRocks\Recaptcha\Form\RecaptchaRuleHandler;
use YiiRocks\Recaptcha\Middleware\RecaptchaMiddleware;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Yiisoft\Di\Container;
use Yiisoft\Di\ServiceProviderInterface;

/**
 * Registers reCAPTCHA services in the Yii3 DI container.
 *
 * Alternatively, copy config/recaptcha.php into your app's params and
 * define bindings there directly.
 */
final class RecaptchaServiceProvider implements ServiceProviderInterface
{
    #[\Override]
    public function getDefinitions(): array
    {
        return [
            RecaptchaConfig::class => static function (Container $c): RecaptchaConfig {
                /** @var array<string, array<string, mixed>> $params */
                $params = $c->get('params');
                /** @var array<string, mixed> $cfg */
                $cfg = $params['yiirocks/recaptcha'] ?? [];

                $siteKey = isset($cfg['siteKey']) && is_string($cfg['siteKey'])
                    ? $cfg['siteKey']
                    : throw new \InvalidArgumentException('recaptcha.siteKey is required');

                $secretKey = isset($cfg['secretKey']) && is_string($cfg['secretKey'])
                    ? $cfg['secretKey']
                    : throw new \InvalidArgumentException('recaptcha.secretKey is required');

                return new RecaptchaConfig(
                    siteKey:          $siteKey,
                    secretKey:        $secretKey,
                    version:          isset($cfg['version']) && is_string($cfg['version']) ? $cfg['version'] : RecaptchaConfig::VERSION_V2_CHECKBOX,
                    v3ScoreThreshold: isset($cfg['v3ScoreThreshold']) ? (float) $cfg['v3ScoreThreshold'] : 0.5,
                    v3Action:         isset($cfg['v3Action']) && is_string($cfg['v3Action']) ? $cfg['v3Action'] : 'submit',
                    verifyUrl:        isset($cfg['verifyUrl']) && is_string($cfg['verifyUrl']) ? $cfg['verifyUrl'] : 'https://www.google.com/recaptcha/api/siteverify',
                    timeout:          isset($cfg['timeout']) ? (int) $cfg['timeout'] : 10,
                );
            },

            RecaptchaClient::class => static function (Container $c): RecaptchaClient {
                return new RecaptchaClient(
                    httpClient:     $c->get(ClientInterface::class),
                    requestFactory: $c->get(RequestFactoryInterface::class),
                    streamFactory:  $c->get(StreamFactoryInterface::class),
                    config:         $c->get(RecaptchaConfig::class),
                );
            },

            RecaptchaRuleHandler::class => static function (Container $c): RecaptchaRuleHandler {
                return new RecaptchaRuleHandler(
                    client: $c->get(RecaptchaClient::class),
                    config: $c->get(RecaptchaConfig::class),
                );
            },

            RecaptchaMiddleware::class => static function (Container $c): RecaptchaMiddleware {
                return new RecaptchaMiddleware(
                    client:          $c->get(RecaptchaClient::class),
                    config:          $c->get(RecaptchaConfig::class),
                    responseFactory: $c->get(ResponseFactoryInterface::class),
                );
            },
        ];
    }

    #[\Override]
    public function getExtensions(): array
    {
        return [
            'yiisoft/validator/handlers' => static function (Container $c, array $handlers): array {
                $handlers[RecaptchaRule::class] = $c->get(RecaptchaRuleHandler::class);
                return $handlers;
            },
        ];
    }
}
