<?php

declare(strict_types=1);

namespace YiiRocks\Recaptcha\Exception;

class MissingSiteKeyException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Site key must be set before rendering.');
    }
}
