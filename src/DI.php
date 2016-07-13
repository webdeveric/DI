<?php
/**
 * This ia a dependency injection container with some syntactic sugar thrown in.
 *
 * @author Eric King <eric@webdeveric.com>
 */

namespace webdeveric\DI;

use ArrayAccess;

/**
 * Dependency injection container.
 *
 * This is where the _magic_ happens.
 *
 */
class DI extends BaseContainer implements ArrayAccess
{
    /**
     * Call the container like its a function.
     *
     * @param string $name
     * @return object
     */
    public function __invoke($name)
    {
        return $this->get($name);
    }

    /**
     * @param string $name
     * @return object
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * @param string   $name
     * @param callable $callback
     * @return false|object
     */
    public function __set($name, callable $callback)
    {
        return $this->register($name, $callback);
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->objects[ $name ]);
    }

    /**
     * @param string $name
     * @return void
     */
    public function __unset($name)
    {
        if (isset($this->objects[ $name ])) {
            $this->factories->detach($this->objects[ $name ]);
            unset($this->objects[ $name ]);
        }
    }

    /**
     * @param string   $key
     * @param callable $callback
     * @return false|object
     */
    public function offsetSet($key, $callback)
    {
        if (!is_callable($callback)) {
            return false;
        }

        return $this->register($key, $callback);
    }

    /**
     * @param string $key
     * @return object
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * @param string $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->__unset($key);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->__isset($key);
    }
}
