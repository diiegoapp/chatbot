<?php

namespace App\Exceptions;

use RuntimeException;

class AiBadResponse extends RuntimeException
{
    public function __construct(string $message = 'Resposta inesperada do serviço de IA.')
    {
        parent::__construct($message);
    }
}
