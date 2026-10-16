<?php

namespace App\Exceptions;

use RuntimeException;

class AiRateLimited extends RuntimeException
{
    public function __construct(string $message = 'Limite de requisições atingido. Tente novamente em instantes.')
    {
        parent::__construct($message);
    }
}
