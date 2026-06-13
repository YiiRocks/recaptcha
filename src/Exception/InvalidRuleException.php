<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Exception;

class InvalidRuleException extends \RuntimeException
{
    public function __construct(string $expected, string $actual)
    {
        parent::__construct(sprintf('Expected %s, got %s.', $expected, $actual));
    }
}
