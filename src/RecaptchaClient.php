<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

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
    ) {}

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
            $data = json_decode($response->getBody()->__toString(), true);

            return new VerificationResult(
                success: (bool) ($data['success'] ?? false),
                errorCodes: (array) ($data['error-codes'] ?? []),
                score: isset($data['score']) ? (float) $data['score'] : null,
                action: $data['action'] ?? null,
                hostname: $data['hostname'] ?? null,
                challengeTs: $data['challenge_ts'] ?? null,
            );
        } catch (ClientExceptionInterface) {
            return new VerificationResult(
                success: false,
                errorCodes: ['http-error'],
            );
        }
    }
}
