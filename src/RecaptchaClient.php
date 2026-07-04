<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final readonly class RecaptchaClient
{
    public function __construct(
        private RecaptchaConfig $config,
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private StreamFactoryInterface $streamFactory,
    ) {
    }

    public function getConfig(): RecaptchaConfig
    {
        return $this->config;
    }

    public function verify(string $token, ?string $clientIp = null): VerificationResult
    {
        return $this->doVerify($token, $this->config->secretV2, $clientIp);
    }

    public function verifyV3(string $token, ?string $clientIp = null): VerificationResult
    {
        return $this->doVerify($token, $this->config->secretV3, $clientIp);
    }

    public function verifyWithSecret(string $token, string $secret, ?string $clientIp = null): VerificationResult
    {
        return $this->doVerify($token, $secret, $clientIp);
    }

    private function doVerify(string $token, string $secret, ?string $clientIp = null): VerificationResult
    {
        $body = [
            'secret' => $secret,
            'response' => $token,
        ];

        if ($clientIp !== null && $this->config->sendRemoteIp) {
            $body['remoteip'] = $clientIp;
        }

        $request = $this->requestFactory
            ->createRequest('POST', $this->config->verifyUrl)
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($this->streamFactory->createStream(http_build_query($body)));

        try {
            $response = $this->httpClient->sendRequest($request);
            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
                return new VerificationResult(
                    success: false,
                    errorCodes: ['http-error'],
                );
            }

            /** @var mixed $data */
            $data = json_decode($response->getBody()->__toString(), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($data)) {
                return new VerificationResult(
                    success: false,
                    errorCodes: ['http-error'],
                );
            }

            /** @var array<int, string> $errorCodes */
            $errorCodes = (array) ($data['error-codes'] ?? []);

            return new VerificationResult(
                success: (bool) ($data['success'] ?? false),
                errorCodes: $errorCodes,
                score: isset($data['score']) ? (float) $data['score'] : null,
                action: isset($data['action']) ? (string) $data['action'] : null,
                hostname: isset($data['hostname']) ? (string) $data['hostname'] : null,
                challengeTs: isset($data['challenge_ts']) ? (string) $data['challenge_ts'] : null,
            );
        } catch (ClientExceptionInterface|JsonException) {
            return new VerificationResult(
                success: false,
                errorCodes: ['http-error'],
            );
        }
    }
}
