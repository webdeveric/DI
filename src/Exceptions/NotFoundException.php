<?php
/**
 * Thrown when something not found in the container.
 */

namespace webdeveric\DI\Exceptions;

use Psr\Container\NotFoundExceptionInterface;

class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
}
