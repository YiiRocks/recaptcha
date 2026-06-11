<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Psr\Container\ContainerInterface;
use Yiisoft\RequestProvider\RequestProviderInterface;
use Yiisoft\Translator\TranslatorInterface;

return [
    static function (ContainerInterface $container): void {
        RecaptchaRegistry::configure(
            client: $container->has(RecaptchaClient::class)
                ? $container->get(RecaptchaClient::class)
                : throw new \RuntimeException('RecaptchaClient must be registered in DI.'),
            requestProvider: $container->has(RequestProviderInterface::class)
                ? $container->get(RequestProviderInterface::class)
                : null,
            translator: $container->has(TranslatorInterface::class)
                ? $container->get(TranslatorInterface::class)
                : null,
        );
    },
];
