<?php

namespace App\Exceptions;

abstract class DomainException extends \Exception
{
    abstract public function userMessage(): string;
}
