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
    private static ?bool $containerUseContainer = null;
    private static ?string $containerTag = null;
    private static ?array $containerAttributes = null;

    public static function configure(
        RecaptchaClient $client,
        ?RequestProviderInterface $requestProvider = null,
        ?TranslatorInterface $translator = null,
    ): void {
        self::$client = $client;
        self::$requestProvider = $requestProvider;
        self::$translator = $translator;
    }

    public static function reset(): void
    {
        self::$client = null;
        self::$requestProvider = null;
        self::$translator = null;
        self::$containerUseContainer = null;
        self::$containerTag = null;
        self::$containerAttributes = null;
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

    /**
     * @param array{useContainer?: bool, tag?: string, attributes?: array<string, string>} $config
     */
    public static function setContainerDefaults(array $config): void
    {
        self::$containerUseContainer = $config['useContainer'] ?? null;
        self::$containerTag = $config['tag'] ?? null;
        self::$containerAttributes = $config['attributes'] ?? null;
    }

    public static function containerUseContainer(): ?bool
    {
        return self::$containerUseContainer;
    }

    public static function containerTag(): ?string
    {
        return self::$containerTag;
    }

    /**
     * @return array<string, string>|null
     */
    public static function containerAttributes(): ?array
    {
        return self::$containerAttributes;
    }

    public static function resolveClientIp(?RequestProviderInterface $provider): ?string
    {
        $provider ??= self::$requestProvider;

        if ($provider === null) {
            return null;
        }

        try {
            $request = $provider->get();
            $serverParams = $request->getServerParams();
            $remoteAddr = $serverParams['REMOTE_ADDR'] ?? null;

            if (!is_string($remoteAddr)) {
                return null;
            }

            return $remoteAddr;
        } catch (RequestNotSetException) {
            return null;
        }
    }
}
