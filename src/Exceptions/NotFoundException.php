<?php
/**
 * Thrown when something not found in the container.
 */

namespace webdeveric\DI\Exceptions;

use Interop\Container\Exception\NotFoundException as InteropContainerNotFoundException;

class NotFoundException extends \Exception implements InteropContainerNotFoundException
{
}
