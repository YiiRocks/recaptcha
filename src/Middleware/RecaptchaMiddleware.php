<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use YiiRocks\Recaptcha\RecaptchaClient;
use YiiRocks\Recaptcha\RecaptchaConfig;

/**
 * PSR-15 middleware that verifies a reCAPTCHA token from the incoming request body.
 *
 * Add to a route or route group that processes form submissions:
 *
 * ```php
 * $router->post('/contact', ContactAction::class)
 *        ->middleware(RecaptchaMiddleware::class);
 * ```
 */
final class RecaptchaMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly RecaptchaClient          $client,
        private readonly RecaptchaConfig          $config,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly string                   $tokenField = 'g-recaptcha-response',
        private readonly int                      $failureStatus = 400,
    ) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        /** @var array<string, mixed> $body */
        $body  = is_array($parsedBody) ? $parsedBody : [];
        $raw   = $body[$this->tokenField] ?? '';
        $token = is_string($raw) ? $raw : '';

        if ($token === '') {
            return $this->failureResponse('reCAPTCHA token is missing.');
        }

        $ip       = $this->resolveIp($request);
        $response = $this->client->verify($token, $ip);

        if (!$response->isSuccess()) {
            return $this->failureResponse('reCAPTCHA verification failed.');
        }

        if ($this->config->isV3() && !$response->isAboveThreshold($this->config->getV3ScoreThreshold())) {
            return $this->failureResponse('reCAPTCHA score too low.');
        }

        $request = $request->withAttribute('recaptcha_response', $response);

        return $handler->handle($request);
    }

    private function failureResponse(string $message): ResponseInterface
    {
        $response = $this->responseFactory->createResponse($this->failureStatus);
        $response->getBody()->write(json_encode(['error' => $message], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json');
    }

    private function resolveIp(ServerRequestInterface $request): ?string
    {
        $forwarded = $request->getHeaderLine('X-Forwarded-For');
        if ($forwarded !== '') {
            return trim(explode(',', $forwarded)[0]);
        }

        $serverParams = $request->getServerParams();
        $remoteAddr   = $serverParams['REMOTE_ADDR'] ?? null;

        return is_string($remoteAddr) ? $remoteAddr : null;
    }
}
