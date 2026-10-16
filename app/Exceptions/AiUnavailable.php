<?php

namespace App\Exceptions;

use RuntimeException;

class AiUnavailable extends RuntimeException
{
    public function __construct(string $message = 'Serviço de IA temporariamente indisponível.')
    {
        parent::__construct($message);
    }
}
