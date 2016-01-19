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

    public function testConstructor()
    {
        $this->assertTrue($this->container instanceof BaseContainer);
    }

    public function testClassNotFound()
    {
        try {
            $this->container->get('SomeFakeClassName');
        } catch (UnresolvableClassException $e) {
            return;
        }

        $this->fail('An expected exception has not been raised.');
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
    }

    public function testUnregister()
    {
        $this->container->arg('name', 'Eric');

        $this->assertTrue($this->container->has('name'));

        $this->container->unregister('name');

        $this->assertFalse($this->container->has('name'));

        $this->container->instance('name', new stdClass);

        $this->assertTrue($this->container->has('name'));

        $this->assertTrue($this->container->get('name') instanceof stdClass);

        $this->container->unregister('name');

        $this->setExpectedException('\webdeveric\DI\Exceptions\UnresolvableClassException');

        $this->container->get('name');
    }
}
