<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Psr\Container\ContainerInterface;
use Yiisoft\RequestProvider\RequestProviderInterface;
use Yiisoft\Translator\TranslatorInterface;
use YiiRocks\Recaptcha\Exception\MissingClientException;

return [
    static function (ContainerInterface $container) use ($params): void {
        RecaptchaRegistry::setContainerDefaults(
            $params['yiirocks/recaptcha']['container'] ?? [],
        );
        RecaptchaRegistry::configure(
            client: $container->has(RecaptchaClient::class)
                ? $container->get(RecaptchaClient::class)
                : throw new MissingClientException('RecaptchaClient must be registered in DI.'),
            requestProvider: $container->has(RequestProviderInterface::class)
                ? $container->get(RequestProviderInterface::class)
                : null,
            translator: $container->has(TranslatorInterface::class)
                ? $container->get(TranslatorInterface::class)
                : null,
        );
    },
];
