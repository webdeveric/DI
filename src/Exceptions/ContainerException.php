<?php
/**
 * Thrown when something is unresolvable.
 */

namespace webdeveric\DI\Exceptions;

use Psr\Container\ContainerExceptionInterface;

class ContainerException extends \Exception implements ContainerExceptionInterface
{
}
