<?php

namespace webdeveric\DI\Tests;

use PHPUnit_Framework_TestCase;
use webdeveric\DI\Container;
use webdeveric\DI\Exceptions\UnresolvableClassException;

class ContainerTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->container = new Container();
    }

    public function tearDown()
    {
        unset($this->container);
    }

    public function testConstructor()
    {
        $this->assertTrue($this->container instanceof Container);
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
}
