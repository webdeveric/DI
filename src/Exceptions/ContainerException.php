<?php
/**
 * Thrown when something is unresolvable.
 */

namespace webdeveric\DI\Exceptions;

use Interop\Container\Exception\ContainerException as InteropContainerException;

class ContainerException extends \Exception implements InteropContainerException
{
}
