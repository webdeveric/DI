<?php

namespace webdeveric\DI\Tests;

use stdClass;
use ArrayIterator;
use PHPUnit_Framework_TestCase;
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
        $this->assertTrue($this->container instanceof DI);
    }

    public function testInvoke()
    {
        $DI = $this->container;
        $this->assertTrue($DI('stdClass') instanceof stdClass);
    }

    public function testGetter()
    {
        $this->assertTrue($this->container->stdClass instanceof stdClass);
    }

    public function testSetter()
    {
        $this->container->obj = function () {
            return new stdClass;
        };

        $this->assertTrue($this->container->get('obj') instanceof stdClass);
    }

    public function testIsset()
    {
        $this->container->instance('obj', new stdClass);

        $this->assertTrue(isset($this->container->obj));
    }

    public function testIteratorAggregate()
    {
        $this->assertTrue($this->container->getIterator() instanceof ArrayIterator);

        $this->container->instance('obj1', new stdClass);

        $this->container->instance('obj2', new stdClass);

        foreach ($this->container as $obj) {
            $this->assertTrue($obj instanceof stdClass);
        }
    }
}
