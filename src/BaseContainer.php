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
use ReflectionClass;
use ReflectionMethod;
use ReflectionException;
use ReflectionParameter;
use Interop\Container\ContainerInterface;
use webdeveric\DI\Exceptions\NotFoundException;
use webdeveric\DI\Exceptions\ContainerException;
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
 */
class BaseContainer implements ContainerInterface
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
    const ALIAS_RESOLVE_LIMIT = 50;

    /**
     * Create a new instance.
     */
    public function __construct()
    {
        $this->objects   = [];
        $this->callbacks = [];
        $this->aliases   = [];
        $this->arguments = [];
        $this->factories = new SplObjectStorage();

        $this->instance(static::class, $this);
        $this->alias(ContainerInterface::class, static::class);
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
        return $this->callbacks[ $name ] = $this->makeClosure($callback);
    }

    /**
     * Remove any objects, callbacks, factories, aliases, and arguments that are keyed by $name.
     * @param string $name
     * @return void
     */
    public function unregister($name)
    {
        if (isset($this->callbacks[ $name ])) {
            $callback = $this->callbacks[ $name ];

            if ($this->factories->contains($callback)) {
                $this->factories->detach($callback);
            }
        }

        unset(
            $this->objects[ $name ],
            $this->callbacks[ $name ],
            $this->aliases[ $name ],
            $this->arguments[ $name ]
        );
    }

    /**
     * Define an alias.
     *
     * @param  string $alias
     * @param  string $original
     * @return void
     */
    public function alias($alias, $original)
    {
        $this->aliases[ $alias ] = $original;
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
        if (isset($this->aliases[ $alias ])) {
            $counter = 0;
            $initialAlias = $alias;

            do {
                $alias = $this->aliases[ $alias ];

                if (++$counter > self::ALIAS_RESOLVE_LIMIT) {
                    throw new UnresolvableAliasException(
                        sprintf('Alias resolve limit reached for \'%1$s\' at alias \'%2$s\'', $initialAlias, $alias)
                    );
                }
            } while (isset($this->aliases[ $alias ])); // Do it again if there is another alias for the current $alias
        }

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
            throw new ContainerException('$object must be an object');
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
     * Determine if $name is a factory.
     * @param string $name
     * @return bool
     */
    public function isFactory($name)
    {
        if (isset($this->callbacks[ $name ])) {
            return $this->factories->contains($this->callbacks[ $name ]);
        }

        return false;
    }

    /**
     * Determine if a name has been registered with the container.
     *
     * @param  string $name
     * @return bool
     */
    public function has($name)
    {
        $fields = [ 'callbacks', 'objects', 'aliases', 'arguments' ];

        foreach ($fields as $field) {
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
        $thrownException = null;

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

                // Do we have a registered way to build the instance?
                if (isset($this->callbacks[ $name ])) {
                    $callback = $this->callbacks[ $name ];
                    $object = $callback($this);

                    // If the callback is a factory, always get an instance from it.
                    if ($this->factories->contains($callback)) {
                        return $object;
                    }

                    return $this->objects[ $name ] = $object;
                }

                // Figure it out with Reflection.
                return $this->resolve($name);
            } catch (Exception $e) {
                $thrownException = $e;
            }
        }

        throw $this->has($name) ? $thrownException : new NotFoundException("{$name} not found");
    }

    /**
     * Convert a callable into a Closure, if needed.
     *
     * @param  callable $callback
     * @return Closure
     */
    protected function makeClosure(callable $callback)
    {
        if (! ($callback instanceof Closure)) {
            $callback = function (ContainerInterface $container) use ($callback) {
                return call_user_func($callback, $container);
            };
        }

        return $callback;
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
            $refClass = new ReflectionClass($name);

            if ($refClass->isInstantiable()) {
                $constructor = $refClass->getConstructor();

                $parameters = $constructor instanceof ReflectionMethod ? $constructor->getParameters() : null;

                if (empty($parameters)) {
                    return new $name;
                }

                foreach ($parameters as &$param) {
                    $param = $this->resolveParameter($param, $refClass);
                }

                return $refClass->newInstanceArgs($parameters);
            }

            switch (true) {
                case $refClass->isAbstract():
                    throw new UnresolvableClassException("Unresolvable Abstract Class '{$name}'");
                case $refClass->isInterface():
                    throw new UnresolvableClassException("Unresolvable Interface '{$name}'");
                case $refClass->isTrait():
                    throw new UnresolvableClassException("Unresolvable Trait '{$name}'");
                default:
                    throw new UnresolvableClassException("Unresolvable Class '{$name}'");
            }
        } catch (ReflectionException $e) { // Class does not exist
            throw new UnresolvableClassException($e->getMessage());
        }
    }

    /**
     * Resolve a parameter.
     *
     * @param  ReflectionParameter $param
     * @param  ReflectionClass     $refClass
     * @throws UnresolvableParameterException
     * @return mixed
     */
    protected function resolveParameter(ReflectionParameter $param, ReflectionClass $refClass)
    {
        $paramClass = $param->getClass();

        if (is_null($paramClass)) {
            if (array_key_exists($param->name, $this->arguments)) {
                return $this->arguments[ $param->name ];
            }

            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }

            throw new UnresolvableParameterException(sprintf('Unresolvable %2$s - %1$s', $param, $refClass->getName()));
        }

        return $this->get($paramClass->name);
    }
}
