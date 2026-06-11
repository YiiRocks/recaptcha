<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\RequestProvider\RequestProviderInterface;
use Yiisoft\RequestProvider\RequestNotSetException;
use Yiisoft\Translator\TranslatorInterface;

final class RecaptchaRegistry
{
    private static ?RecaptchaClient $client = null;
    private static ?RequestProviderInterface $requestProvider = null;
    private static ?TranslatorInterface $translator = null;

    public static function configure(
        RecaptchaClient $client,
        ?RequestProviderInterface $requestProvider = null,
        ?TranslatorInterface $translator = null,
    ): void {
        self::$client = $client;
        self::$requestProvider = $requestProvider;
        self::$translator = $translator;
    }

    public static function client(): ?RecaptchaClient
    {
        return self::$client;
    }

    public static function requestProvider(): ?RequestProviderInterface
    {
        return self::$requestProvider;
    }

    public static function translator(): ?TranslatorInterface
    {
        return self::$translator;
    }

    public static function resolveClientIp(?RequestProviderInterface $provider): ?string
    {
        $provider ??= self::$requestProvider;

        if ($provider === null) {
            return null;
        }

        try {
            $request = $provider->getRequest();
            $serverParams = $request->getServerParams();

            return $serverParams['REMOTE_ADDR'] ?? null;
        } catch (RequestNotSetException) {
            return null;
        }
    }
}
