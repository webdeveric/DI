<?php

namespace webdeveric\DI\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use webdeveric\DI\Exceptions\NotFoundException;
use webdeveric\DI\Exceptions\ContainerException;
use webdeveric\DI\Exceptions\UnresolvableAliasException;
use webdeveric\DI\Exceptions\UnresolvableClassException;
use webdeveric\DI\Exceptions\UnresolvableParameterException;

class ExceptionsTest extends TestCase
{
    public function testInheritance()
    {
        $this->assertTrue(new NotFoundException instanceof Exception);

        $this->assertTrue(new ContainerException instanceof Exception);

        $this->assertTrue(new UnresolvableAliasException instanceof ContainerException);

        $this->assertTrue(new UnresolvableClassException instanceof ContainerException);

        $this->assertTrue(new UnresolvableParameterException instanceof ContainerException);
    }

    /**
     * @expectedException \webdeveric\DI\Exceptions\ContainerException
     * @expectedExceptionMessage Message
     */
    public function testExceptionHasRightMessage()
    {
        throw new ContainerException('Message');
    }

    /**
     * @expectedException \webdeveric\DI\Exceptions\ContainerException
     * @expectedExceptionCode 1
     */
    public function testExceptionHasRightCode()
    {
        throw new ContainerException('Message', 1);
    }
}
