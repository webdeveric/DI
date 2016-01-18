<?php
/**
 * This ia a dependency injection container.
 *
 * @author Eric King <eric@webdeveric.com>
 */

namespace webdeveric\DI;

use SplObjectStorage;
use Closure;
use Exception;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use webdeveric\DI\Exceptions\UnresolvableAliasException;
use webdeveric\DI\Exceptions\UnresolvableClassException;
use webdeveric\DI\Exceptions\UnresolvableParameterException;

/**
 * Dependency injection container.
 *
 * This container should do the following:
 *
 * 1. Associate an object instance to an alias. There will only be one instance returned when the alias is requested.
 * 2. Generate new instances of classes either by
 *   - Calling a factory to generate an instance.
 *   - Using PHP Reflection classes to figure it out.
 * 3. The objects are not instantiated until requested.
 * 4. Create aliases for classes/interfaces.
 *    This is so you can associate an abstract class or interface to a concrete class instance (1) or factory (2)
 * 5. The default behavior when assigning a property to the container is to treat that like (1)
 *    The `$name` passed to `__set` will become the `$alias` and the `$value` is
 *    the callable used to instantiate the singleton.
 */
class Container implements ArrayAccess, IteratorAggregate
{
    /**
     * @var array
     */
    protected $objects;

    /**
     * @var array
     */
    protected $callbacks;

    /**
     * @var array
     */
    protected $aliases;

    /**
     * @var array
     */
    protected $arguments;

    /**
     * @var SplObjectStorage
     */
    protected $factories;

    /**
     * @var int
     */
    protected $alias_resolve_limit;

    /**
     * Create a new Container instance.
     */
    public function __construct()
    {
        $this->objects   = [];
        $this->callbacks = [];
        $this->aliases   = [];
        $this->arguments = [];
        $this->factories = new SplObjectStorage();
        $this->alias_resolve_limit = 50;
    }

    /**
     * Set the value of an argument.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return void
     */
    public function arg($key, $value)
    {
        $this->arguments[ $key ] = $value;
    }

    /**
     * Register a name with a callable that is able to create an object.
     *
     * @param  string   $name
     * @param  callable $callback
     * @return false|object
     */
    public function register($name, callable $callback)
    {
        $callback = $this->makeClosure($callback);

        if ($callback !== false) {
            return $this->callbacks[ $name ] = $callback;
        }

        return false;
    }

    /**
     * Define an alias.
     *
     * @param  string $from
     * @param  string $to
     * @return void
     */
    public function alias($from, $to)
    {
        $this->aliases[ $from ] = $to;
    }

    /**
     * Resolve an alias to its original name.
     *
     * @param  string $alias
     * @throws UnresolvableAliasException Thrown if alias has not been resolved within the resolve limit.
     * @return string
     */
    protected function resolveAlias($alias)
    {
        if (! isset($this->aliases[ $alias ])) {
            return $alias;
        }

        // We know the $alias index already exists because of the previous statement.
        $counter = 0;

        do {
            $alias = $this->aliases[ $alias ];

            if (++$counter > $this->alias_resolve_limit) {
                throw new UnresolvableAliasException(
                    sprintf('Alias resolve limit (50) reached for %1$s at alias %1$s', func_get_arg(0), $alias)
                );
            }

        } while (isset($this->aliases[ $alias ])); // Do it again if there is another alias for the current $alias

        return $alias;
    }

    /**
     * Register an object to a name.
     *
     * @param  string $name
     * @param  object $object
     * @return false|object
     */
    public function instance($name, $object)
    {
        if (! is_object($object)) {
            return false;
        }

        return $this->objects[ $name ] = $object;
    }

    /**
     * Register a factory to a name.
     *
     * @param  string   $name
     * @param  callable $callback
     * @return callable
     */
    public function factory($name, callable $callback)
    {
        $callback = $this->register($name, $callback);
        $this->factories->attach($callback);
        return $callback;
    }

    /**
     * Determine if a name has been registered with the container.
     *
     * @param  string $name
     * @return bool
     */
    public function has($name)
    {
        foreach ([ 'callbacks', 'objects', 'aliases', 'arguments' ] as &$field) {
            if (array_key_exists($name, $this->$field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get an object from the container based on its name.
     *
     * @param  string $name
     * @return object
     */
    public function get($name)
    {
        foreach ([ false, true ] as $lowercase) {

            try {

                if ($lowercase) {
                    $name = strtolower($name);
                }

                $name = $this->resolveAlias($name);

                // Get instance
                if (isset($this->objects[ $name ])) {
                    return $this->objects[ $name ];
                }

                // First initialization OR call factory
                if (isset($this->callbacks[ $name ])) {

                    if ($this->factories->contains($this->callbacks[ $name ])) {
                        return $this->callbacks[ $name ]( $this );
                    }

                    return $this->objects[ $name ] = $this->callbacks[ $name ]( $this );
                }

                return $this->resolve($name);

            } catch (Exception $e) {

                throw new UnresolvableClassException($e->getMessage());

            }
        }
    }

    /**
     * Convert a callable into a Closure, if needed.
     *
     * @param  callable $callback
     * @return callable
     */
    protected function makeClosure(callable $callback)
    {
        if ($callback instanceof Closure) {
            return $callback;
        }

        return function (Container $container) use (&$callback) {
            return call_user_func($callback, $container);
        };
    }

    /**
     * Resolve a name to an object.
     *
     * @param  string $name
     * @return object
     */
    public function resolve($name)
    {
        try {

            $ref = new ReflectionClass($name);

            if ($ref->isInstantiable()) {

                $constructor = $ref->getConstructor();

                if (is_null($constructor)) {
                    // Nothing to construct so no arguments are needed
                    return new $name;
                }

                $params = $constructor->getParameters();

                if (empty($params)) {
                    // Constructor doesn't take any parameters so just construct it and send it back.
                    return new $name;
                }

                $parameters = [];

                foreach ($params as &$param) {
                    $parameters[] = $this->resolveParameter($param, $ref);
                }

                return $ref->newInstanceArgs($parameters);
            }

            switch (true) {
                case $ref->isAbstract():
                    throw new UnresolvableClassException("Unresolvable Abstract Class [ $name ]");
                case $ref->isInterface():
                    throw new UnresolvableClassException("Unresolvable Interface [ $name ]");
                case $ref->isTrait():
                    throw new UnresolvableClassException("Unresolvable Trait [ $name ]");
                default:
                    throw new UnresolvableClassException("Unresolvable Class [ $name ]");
            }

        } catch (ReflectionException $e) { // Class does not exist

            throw new UnresolvableClassException($e->getMessage());

        }
    }

    /**
     * Resolve a parameter.
     *
     * @param  ReflectionParameter $param
     * @param  ReflectionClass     $ref
     * @throws UnresolvableParameterException
     * @return mixed
     */
    protected function resolveParameter(ReflectionParameter $param, ReflectionClass $ref)
    {
        $ref_class = $param->getClass();

        if (is_null($ref_class)) {

            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }

            if (array_key_exists($param->name, $this->arguments)) {
                return $this->arguments[ $param->name ];
            }

            throw new UnresolvableParameterException(sprintf('Unresolvable %2$s - %1$s', $param, $ref->getName()));

        } else {

            return $this->get($ref_class->name);

        }
    }

    /**
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
            // $this->factories->detach( $this->objects[ $name ] );
            unset($this->objects[ $name ]);
        }
    }

    /**
     * @param string   $key
     * @param callable $callback
     * @return false|object
     */
    public function offsetSet($key, callable $callback)
    {
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

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->objects);
    }
}
