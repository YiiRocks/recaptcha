<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use YiiRocks\Recaptcha\RecaptchaClient;
use YiiRocks\Recaptcha\RecaptchaConfig;

final class RecaptchaClientTest extends TestCase
{
    public function testVerifySuccess(): void
    {
        $client = $this->_createMockClient(['success' => true]);
        $recaptchaClient = $this->_createClient($client);

        $result = $recaptchaClient->verify('valid-token');

        $this->assertTrue($result->success);
        $this->assertEmpty($result->errorCodes);
    }

    public function testVerifyFailure(): void
    {
        $client = $this->_createMockClient([
            'success' => false,
            'error-codes' => ['invalid-input-response'],
        ]);
        $recaptchaClient = $this->_createClient($client);

        $result = $recaptchaClient->verify('invalid-token');

        $this->assertFalse($result->success);
        $this->assertContains('invalid-input-response', $result->errorCodes);
    }

    public function testVerifyV3WithScoreAndAction(): void
    {
        $client = $this->_createMockClient([
            'success' => true,
            'score' => 0.9,
            'action' => 'login',
        ]);
        $recaptchaClient = $this->_createClient($client);

        $result = $recaptchaClient->verifyV3('valid-token');

        $this->assertTrue($result->success);
        $this->assertSame(0.9, $result->score);
        $this->assertSame('login', $result->action);
    }

    public function testVerifyWithSecret(): void
    {
        $client = $this->_createMockClient(['success' => true]);
        $recaptchaClient = $this->_createClient($client);

        $result = $recaptchaClient->verifyWithSecret('token', 'custom-secret');

        $this->assertTrue($result->success);
    }

    public function testHttpClientExceptionReturnsFailure(): void
    {
        $client = $this->createStub(\Psr\Http\Client\ClientInterface::class);
        $exception = new class ('Connection error') extends \RuntimeException
            implements \Psr\Http\Client\ClientExceptionInterface {};
        $client->method('sendRequest')->willThrowException($exception);

        $recaptchaClient = $this->_createClient($client);

        $result = $recaptchaClient->verify('token');

        $this->assertFalse($result->success);
        $this->assertContains('http-error', $result->errorCodes);
    }

    public function testInvalidJsonReturnsFailure(): void
    {
        $client = $this->_createMockClientBody('not-json');
        $recaptchaClient = $this->_createClient($client);

        $result = $recaptchaClient->verify('token');

        $this->assertFalse($result->success);
        $this->assertContains('http-error', $result->errorCodes);
    }

    public function testNonSuccessStatusReturnsFailure(): void
    {
        $client = $this->_createMockClientBody('{"success":true}', 500);
        $recaptchaClient = $this->_createClient($client);

        $result = $recaptchaClient->verify('token');

        $this->assertFalse($result->success);
        $this->assertContains('http-error', $result->errorCodes);
    }

    private function _createMockClient(array $responseData): ClientInterface
    {
        return $this->_createMockClientBody(json_encode($responseData));
    }

    private function _createMockClientBody(string $bodyContent, int $statusCode = 200): ClientInterface
    {
        $factory = new Psr17Factory();
        $body = $factory->createStream($bodyContent);

        $response = new Response($statusCode, [], $body);

        $client = $this->createStub(ClientInterface::class);
        $client->method('sendRequest')->willReturn($response);

        return $client;
    }

    private function _createClient(ClientInterface $client): RecaptchaClient
    {
        $factory = new Psr17Factory();
        $config = new RecaptchaConfig(
            secretV2: 'test-secret-v2',
            secretV3: 'test-secret-v3',
            sendRemoteIp: false,
        );

        return new RecaptchaClient(
            config: $config,
            httpClient: $client,
            requestFactory: $factory,
            streamFactory: $factory,
        );
    }
}
