<?php

namespace webdeveric\DI\Tests;

use stdClass;
use PHPUnit_Framework_TestCase;
use webdeveric\DI\BaseContainer;
use webdeveric\DI\DI;

class DITest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->container = new DI();
    }

    public function tearDown()
    {
        unset($this->container);
    }

    public function testConstructor()
    {
        $this->assertInstanceOf(BaseContainer::class, $this->container);
        $this->assertInstanceOf(DI::class, $this->container);
    }

    public function testInvoke()
    {
        $DI = $this->container;
        $this->assertTrue($DI('stdClass') instanceof stdClass);
    }

    public function testGetter()
    {
        $this->assertInstanceOf(stdClass::class, $this->container->stdClass);
    }

    public function testSetter()
    {
        $this->container->obj = function () {
            return new stdClass;
        };

        $this->assertInstanceOf(stdClass::class, $this->container->get('obj'));
    }

    public function testIsset()
    {
        $this->container->instance('obj', new stdClass);

        $this->assertTrue(isset($this->container->obj));
    }
}
