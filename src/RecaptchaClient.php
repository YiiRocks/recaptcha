<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Verifies reCAPTCHA tokens against Google's siteverify endpoint.
 */
final class RecaptchaClient
{
    public function __construct(
        private readonly ClientInterface         $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface  $streamFactory,
        private readonly RecaptchaConfig         $config,
    ) {}

    /**
     * Verify a token returned by the reCAPTCHA widget/JS.
     *
     * @param string      $token    The g-recaptcha-response value.
     * @param string|null $remoteIp Optional: end-user's IP for extra validation.
     */
    public function verify(string $token, ?string $remoteIp = null): RecaptchaVerifyResponse
    {
        /** @var array<string, string> $params */
        $params = [
            'secret'   => $this->config->getSecretKey(),
            'response' => $token,
        ];

        if ($remoteIp !== null) {
            $params['remoteip'] = $remoteIp;
        }

        $body = http_build_query($params);

        $request = $this->requestFactory
            ->createRequest('POST', $this->config->getVerifyUrl())
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withHeader('Accept', 'application/json')
            ->withBody($this->streamFactory->createStream($body));

        $response = $this->httpClient->sendRequest($request);

        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        return RecaptchaVerifyResponse::fromApiPayload($payload);
    }
}
