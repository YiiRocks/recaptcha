<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Exception;

use RuntimeException;

final class MissingClientException extends RuntimeException
{
    public function __construct(string $message = 'RecaptchaClient is not configured.')
    {
        parent::__construct($message);
    }
}
