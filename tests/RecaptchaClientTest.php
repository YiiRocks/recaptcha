<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use YiiRocks\Recaptcha\RecaptchaClient;
use YiiRocks\Recaptcha\RecaptchaConfig;

final class RecaptchaClientTest extends TestCase
{
    public static function remoteIpProvider(): array
    {
        return [
            'enabled with ip' => [true, '1.2.3.4', true],
            'disabled with ip' => [false, '1.2.3.4', false],
            'enabled with null ip' => [true, null, false],
            'enabled with empty string ip' => [true, '', true],
        ];
    }

    public function testHttpClientExceptionReturnsFailure(): void
    {
        $client = $this->createStub(\Psr\Http\Client\ClientInterface::class);
        $exception = new class('Connection error') extends \RuntimeException implements \Psr\Http\Client\ClientExceptionInterface {};
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

    public function testJsonExceedingMaxDepthReturnsFailure(): void
    {
        $body = '{"success":true,"deep":' . str_repeat('[', 511) . '1' . str_repeat(']', 511) . '}';
        $client = $this->_createMockClientBody($body);
        $recaptchaClient = $this->_createClient($client);

        $result = $recaptchaClient->verify('token');

        $this->assertFalse($result->success);
        $this->assertContains('http-error', $result->errorCodes);
    }

    public function testJsonWithinMaxDepthDecodesSuccessfully(): void
    {
        $body = '{"success":true,"deep":' . str_repeat('[', 510) . '1' . str_repeat(']', 510) . '}';
        $client = $this->_createMockClientBody($body);
        $recaptchaClient = $this->_createClient($client);

        $result = $recaptchaClient->verify('token');

        $this->assertTrue($result->success);
    }

    public function testNonSuccessStatusReturnsFailure(): void
    {
        $client = $this->_createMockClientBody('{"success":true}', 500);
        $recaptchaClient = $this->_createClient($client);

        $result = $recaptchaClient->verify('token');

        $this->assertFalse($result->success);
        $this->assertContains('http-error', $result->errorCodes);
    }

    #[DataProvider('remoteIpProvider')]
    public function testRemoteIpInclusion(bool $sendRemoteIp, ?string $clientIp, bool $expectIncluded): void
    {
        [$client, $capture] = $this->_createCapturingClient(['success' => true]);

        $factory = new Psr17Factory();
        $config = new RecaptchaConfig(secretV2: 'test-secret-v2', sendRemoteIp: $sendRemoteIp);
        $recaptchaClient = new RecaptchaClient(
            config: $config,
            httpClient: $client,
            requestFactory: $factory,
            streamFactory: $factory,
        );

        $recaptchaClient->verify('token', $clientIp);

        parse_str((string) $capture->request->getBody(), $params);

        if ($expectIncluded) {
            $this->assertSame($clientIp, $params['remoteip']);
        } else {
            $this->assertArrayNotHasKey('remoteip', $params);
        }
    }

    public function testStatusCode300IsTreatedAsFailure(): void
    {
        $client = $this->_createMockClientBody('{"success":true}', 300);
        $recaptchaClient = $this->_createClient($client);

        $result = $recaptchaClient->verify('token');

        $this->assertFalse($result->success);
        $this->assertContains('http-error', $result->errorCodes);
    }

    public function testVerifyCastsNonArrayErrorCodesToArray(): void
    {
        $client = $this->_createMockClient([
            'success' => false,
            'error-codes' => 'invalid-input-response',
        ]);
        $recaptchaClient = $this->_createClient($client);

        $result = $recaptchaClient->verify('token');

        $this->assertSame(['invalid-input-response'], $result->errorCodes);
    }

    public function testVerifyCastsResponseFieldsToExpectedTypes(): void
    {
        $client = $this->_createMockClient([
            'success' => true,
            'score' => '0.75',
            'action' => 12345,
            'hostname' => true,
            'challenge_ts' => 987,
        ]);
        $recaptchaClient = $this->_createClient($client);

        $result = $recaptchaClient->verifyV3('token');

        $this->assertSame(0.75, $result->score);
        $this->assertSame('12345', $result->action);
        $this->assertSame('1', $result->hostname);
        $this->assertSame('987', $result->challengeTs);
    }

    public function testVerifyDefaultsSuccessToFalseWhenMissing(): void
    {
        $client = $this->_createMockClient([]);
        $recaptchaClient = $this->_createClient($client);

        $result = $recaptchaClient->verify('token');

        $this->assertFalse($result->success);
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

    public function testVerifySendsSecretAndResponseInBody(): void
    {
        [$client, $capture] = $this->_createCapturingClient(['success' => true]);

        $recaptchaClient = $this->_createClient($client);
        $recaptchaClient->verify('my-token');

        parse_str((string) $capture->request->getBody(), $params);

        $this->assertSame('test-secret-v2', $params['secret']);
        $this->assertSame('my-token', $params['response']);
    }

    public function testVerifySuccess(): void
    {
        $client = $this->_createMockClient(['success' => true]);
        $recaptchaClient = $this->_createClient($client);

        $result = $recaptchaClient->verify('valid-token');

        $this->assertTrue($result->success);
        $this->assertEmpty($result->errorCodes);
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

    public function testVerifyWithNonArrayResponseBodyReturnsFailure(): void
    {
        $client = $this->_createMockClientBody('true');
        $recaptchaClient = $this->_createClient($client);

        $result = $recaptchaClient->verify('token');

        $this->assertFalse($result->success);
        $this->assertContains('http-error', $result->errorCodes);
    }

    public function testVerifyWithSecret(): void
    {
        $client = $this->_createMockClient(['success' => true]);
        $recaptchaClient = $this->_createClient($client);

        $result = $recaptchaClient->verifyWithSecret('token', 'custom-secret');

        $this->assertTrue($result->success);
    }

    /**
     * @return array{0: ClientInterface, 1: object{request: ?RequestInterface}}
     */
    private function _createCapturingClient(array $responseData): array
    {
        $capture = new class() {
            public ?RequestInterface $request = null;
        };

        $client = $this->createStub(ClientInterface::class);
        $client->method('sendRequest')->willReturnCallback(
            function (RequestInterface $request) use ($capture, $responseData) {
                $capture->request = $request;
                $factory = new Psr17Factory();

                return new Response(200, [], $factory->createStream(json_encode($responseData)));
            },
        );

        return [$client, $capture];
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
}
