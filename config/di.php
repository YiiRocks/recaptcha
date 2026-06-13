<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Yiisoft\RequestProvider\RequestProviderInterface;
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\SimpleMessageFormatter;
use Yiisoft\Translator\IdMessageReader;

return [
    RecaptchaConfig::class => [
        '__construct()' => [
            'siteKeyV2' => $params['yiirocks/recaptcha']['siteKeyV2'],
            'secretV2' => $params['yiirocks/recaptcha']['secretV2'],
            'siteKeyV3' => $params['yiirocks/recaptcha']['siteKeyV3'],
            'secretV3' => $params['yiirocks/recaptcha']['secretV3'],
            'verifyUrl' => $params['yiirocks/recaptcha']['verifyUrl'],
            'sendRemoteIp' => $params['yiirocks/recaptcha']['sendRemoteIp'],
        ],
    ],

    RecaptchaClient::class => static function (ContainerInterface $container): RecaptchaClient {
        return new RecaptchaClient(
            config: $container->get(RecaptchaConfig::class),
            httpClient: $container->get(ClientInterface::class),
            requestFactory: $container->get(RequestFactoryInterface::class),
            streamFactory: $container->get(StreamFactoryInterface::class),
        );
    },

    RecaptchaV2RuleHandler::class => static function (ContainerInterface $container) use ($params): RecaptchaV2RuleHandler {
        return new RecaptchaV2RuleHandler(
            client: $container->has(RecaptchaClient::class) ? $container->get(RecaptchaClient::class) : null,
            requestProvider: $container->has(RequestProviderInterface::class)
                ? $container->get(RequestProviderInterface::class)
                : null,
            translationCategory: $params['yiirocks/recaptcha']['translation.category'],
        );
    },

    RecaptchaV3RuleHandler::class => static function (ContainerInterface $container) use ($params): RecaptchaV3RuleHandler {
        return new RecaptchaV3RuleHandler(
            client: $container->has(RecaptchaClient::class) ? $container->get(RecaptchaClient::class) : null,
            requestProvider: $container->has(RequestProviderInterface::class)
                ? $container->get(RequestProviderInterface::class)
                : null,
            translationCategory: $params['yiirocks/recaptcha']['translation.category'],
        );
    },

    'yii3-recaptcha.categorySource' => [
        'definition' => static function () use ($params): CategorySource {
            $reader = class_exists(MessageSource::class)
                ? new MessageSource(dirname(__DIR__) . '/messages')
                : new IdMessageReader();

            $formatter = extension_loaded('intl')
                ? new IntlMessageFormatter()
                : new SimpleMessageFormatter();

            return new CategorySource(
                $params['yiirocks/recaptcha']['translation.category'],
                $reader,
                $formatter,
            );
        },
        'tags' => ['translation.categorySource'],
    ],
];
