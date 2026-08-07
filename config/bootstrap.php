<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Psr\Container\ContainerInterface;
use YiiRocks\Recaptcha\Exception\MissingClientException;
use Yiisoft\RequestProvider\RequestProviderInterface;
use Yiisoft\Translator\TranslatorInterface;

return [
    static function (ContainerInterface $container) use ($params): void {
        RecaptchaRegistry::setContainerDefaults(
            $params['yiirocks/recaptcha']['container'] ?? [],
        );
        RecaptchaRegistry::setBadgeDefault(
            $params['yiirocks/recaptcha']['badgeV3'] ?? null,
        );
        RecaptchaRegistry::setV2Defaults(
            theme: $params['yiirocks/recaptcha']['themeV2'] ?? null,
            size: $params['yiirocks/recaptcha']['sizeV2'] ?? null,
            type: $params['yiirocks/recaptcha']['typeV2'] ?? null,
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
