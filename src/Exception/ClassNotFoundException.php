<?php

declare(strict_types=1);

namespace Laminas\Di\Exception;

use DomainException;
use Throwable;

class ClassNotFoundException extends DomainException implements ExceptionInterface
{
    /**
     * @param string         $classname
     * @param int|null       $code
     * @param Throwable|null $previous
     */
    public function __construct(string $classname, int $code = null, Throwable $previous = null)
    {
        parent::__construct("The class '$classname' does not exist.", $code ?? 0, $previous);
    }
}
