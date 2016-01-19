<?php

namespace webdeveric\DI\Tests;

use PHPUnit_Framework_TestCase;
use Exception;
use webdeveric\DI\Exceptions\UnresolvableException;
use webdeveric\DI\Exceptions\UnresolvableAliasException;
use webdeveric\DI\Exceptions\UnresolvableClassException;
use webdeveric\DI\Exceptions\UnresolvableParameterException;

class ExceptionsTest extends PHPUnit_Framework_TestCase
{
    public function testInheritance()
    {
        $this->assertTrue(new UnresolvableException instanceof Exception);

        $this->assertTrue(new UnresolvableAliasException instanceof Exception);
        $this->assertTrue(new UnresolvableAliasException instanceof UnresolvableException);

        $this->assertTrue(new UnresolvableClassException instanceof Exception);
        $this->assertTrue(new UnresolvableClassException instanceof UnresolvableException);

        $this->assertTrue(new UnresolvableParameterException instanceof Exception);
        $this->assertTrue(new UnresolvableParameterException instanceof UnresolvableException);
    }

    /**
     * @expectedException \webdeveric\DI\Exceptions\UnresolvableException
     * @expectedExceptionMessage Message
     */
    public function testExceptionHasRightMessage()
    {
        throw new UnresolvableException('Message');
    }

    /**
     * @expectedException \webdeveric\DI\Exceptions\UnresolvableException
     * @expectedExceptionCode 1
     */
    public function testExceptionHasRightCode()
    {
        throw new UnresolvableException('Message', 1);
    }
}
