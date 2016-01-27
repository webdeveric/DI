<?php

namespace webdeveric\DI\Tests;

use stdClass;
use PHPUnit_Framework_TestCase;
use webdeveric\DI\BaseContainer;
use webdeveric\DI\Exceptions\UnresolvableClassException;

class BaseContainerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->container = new BaseContainer();
    }

    public function tearDown()
    {
        unset($this->container);
    }

    public function createObject()
    {
        return new stdClass;
    }

    public function testConstructor()
    {
        $this->assertInstanceOf('webdeveric\DI\BaseContainer', $this->container);
    }

    public function testClassNotFound()
    {
        $this->setExpectedException('\webdeveric\DI\Exceptions\UnresolvableClassException');

        $this->container->get('SomeFakeClassName');
    }

    public function testFactory()
    {
        $this->container->factory('name', function () {
            $obj = new stdClass;
            $obj->name = 'Eric';
            $obj->id = uniqid();
            return $obj;
        });

        $this->assertTrue($this->container->has('name'));

        $this->assertTrue($this->container->isFactory('name'));

        $this->assertFalse($this->container->isFactory('notAFactory'));

        $this->assertNotEquals($this->container->get('name'), $this->container->get('name'));
    }

    public function testAlias()
    {
        $this->container->register('eric', function () {
            $obj = new stdClass;
            $obj->name = 'Eric';
            return $obj;
        });

        $this->container->alias('me', 'eric');

        $this->assertTrue($this->container->has('eric'));

        $this->assertTrue($this->container->has('me'));

        $me = $this->container->get('me');

        $this->assertObjectHasAttribute('name', $me);

        $this->assertTrue($me->name === 'Eric');
    }

    public function testInstance()
    {
        $name = new stdClass;

        $this->container->instance('name', $name);

        $this->assertTrue($this->container->has('name'));

        $this->assertEquals($this->container->get('name'), $name);

        $this->assertFalse($this->container->instance('false', false));
    }

    public function testRegister()
    {
        $this->container->register('test', function () {
            return new stdClass;
        });

        $this->assertTrue($this->container->has('test'));

        $this->container->register('test2', [$this, 'createObject']);

        $this->assertTrue($this->container->has('test2'));
    }

    public function testUnregister()
    {
        $this->container->arg('name', 'Eric');

        $this->assertTrue($this->container->has('name'));

        $this->container->unregister('name');

        $this->assertFalse($this->container->has('name'));

        $this->container->instance('name', new stdClass);

        $this->assertTrue($this->container->has('name'));

        $this->assertInstanceOf('stdClass', $this->container->get('name'));

        $this->container->unregister('name');

        $this->setExpectedException('\webdeveric\DI\Exceptions\UnresolvableClassException');

        $this->container->get('name');
    }

    public function testUnregisterFactory()
    {
        $this->container->factory('test', function () {
            return new stdClass;
        });

        $this->assertInstanceOf('stdClass', $this->container->get('test'));

        $this->container->unregister('test');

        $this->assertFalse($this->container->has('test'));
    }

    public function testUnresolvableAlias()
    {
        $this->container->alias('test1', 'test2');

        $this->container->alias('test2', 'test1');

        $this->setExpectedException('\webdeveric\DI\Exceptions\UnresolvableAliasException');

        $this->container->get('test1');
    }

    public function testGet()
    {
        $this->container->instance('test', new stdClass);

        $this->assertTrue($this->container->get('test') === $this->container->get('TEST'));
    }
}
