<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use YiiRocks\Recaptcha\RecaptchaClient;
use YiiRocks\Recaptcha\RecaptchaConfig;
use YiiRocks\Recaptcha\RecaptchaRegistry;

abstract class AbstractRecaptchaRuleHandler extends TestCase
{
    protected function setUp(): void
    {
        RecaptchaRegistry::configure(
            $this->createClient(['success' => true, 'score' => 0.9, 'action' => 'submit']),
        );
    }

    protected function tearDown(): void
    {
        RecaptchaRegistry::reset();
    }

    /**
     * @return array{0: RecaptchaClient, 1: object{request: ?RequestInterface}}
     */
    protected function createCapturingClient(array $responseData, bool $sendRemoteIp = false): array
    {
        $capture = new class() {
            public ?RequestInterface $request = null;
        };

        $httpClient = $this->createStub(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturnCallback(
            function (RequestInterface $request) use ($capture, $responseData) {
                $capture->request = $request;
                $factory = new Psr17Factory();

                return new \Nyholm\Psr7\Response(200, [], $factory->createStream(json_encode($responseData)));
            },
        );

        $factory = new Psr17Factory();
        $config = $this->createConfig('test-secret', $sendRemoteIp);
        $client = new RecaptchaClient($config, $httpClient, $factory, $factory);

        return [$client, $capture];
    }

    protected function createClient(array $responseData, ?RecaptchaConfig $config = null): RecaptchaClient
    {
        $factory = new Psr17Factory();
        $httpClient = $this->createStub(ClientInterface::class);
        $httpClient->method('sendRequest')->willReturn(
            new \Nyholm\Psr7\Response(200, [], $factory->createStream(json_encode($responseData))),
        );

        return new RecaptchaClient($config ?? $this->createConfig('test-secret'), $httpClient, $factory, $factory);
    }

    abstract protected function createConfig(string $secret, bool $sendRemoteIp = false): RecaptchaConfig;
}
