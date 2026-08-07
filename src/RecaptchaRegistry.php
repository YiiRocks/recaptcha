<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Yiisoft\RequestProvider\RequestNotSetException;
use Yiisoft\RequestProvider\RequestProviderInterface;
use Yiisoft\Translator\TranslatorInterface;

final class RecaptchaRegistry
{
    private static ?RecaptchaV3Badge $badge = null;
    private static ?RecaptchaClient $client = null;

    /** @var array<string, string>|null */
    private static ?array $containerAttributes = null;
    private static ?string $containerTag = null;
    private static ?bool $containerUseContainer = null;
    private static ?RequestProviderInterface $requestProvider = null;
    private static ?RecaptchaV2Size $sizeV2 = null;
    private static ?RecaptchaV2Theme $themeV2 = null;
    private static ?TranslatorInterface $translator = null;
    private static ?RecaptchaV2Type $typeV2 = null;

    public static function badge(): ?RecaptchaV3Badge
    {
        return self::$badge;
    }

    public static function client(): ?RecaptchaClient
    {
        return self::$client;
    }

    public static function configure(
        RecaptchaClient $client,
        ?RequestProviderInterface $requestProvider = null,
        ?TranslatorInterface $translator = null,
    ): void {
        self::$client = $client;
        self::$requestProvider = $requestProvider;
        self::$translator = $translator;
    }

    /**
     * @return array<string, string>|null
     */
    public static function containerAttributes(): ?array
    {
        return self::$containerAttributes;
    }

    public static function containerTag(): ?string
    {
        return self::$containerTag;
    }

    public static function containerUseContainer(): ?bool
    {
        return self::$containerUseContainer;
    }

    public static function requestProvider(): ?RequestProviderInterface
    {
        return self::$requestProvider;
    }

    public static function reset(): void
    {
        self::$client = null;
        self::$requestProvider = null;
        self::$translator = null;
        self::$containerUseContainer = null;
        self::$containerTag = null;
        self::$containerAttributes = null;
        self::$badge = null;
        self::$themeV2 = null;
        self::$sizeV2 = null;
        self::$typeV2 = null;
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

    public static function setBadgeDefault(?RecaptchaV3Badge $badge): void
    {
        self::$badge = $badge;
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

    public static function setV2Defaults(
        ?RecaptchaV2Theme $theme = null,
        ?RecaptchaV2Size $size = null,
        ?RecaptchaV2Type $type = null,
    ): void {
        self::$themeV2 = $theme;
        self::$sizeV2 = $size;
        self::$typeV2 = $type;
    }

    public static function sizeV2(): ?RecaptchaV2Size
    {
        return self::$sizeV2;
    }

    public static function themeV2(): ?RecaptchaV2Theme
    {
        return self::$themeV2;
    }

    public static function translator(): ?TranslatorInterface
    {
        return self::$translator;
    }

    public static function typeV2(): ?RecaptchaV2Type
    {
        return self::$typeV2;
    }
}
