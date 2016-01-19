# Dependency Injection

**Example usage:**

```php
use webdeveric\DI\Container;

$container = new Container();

$container->thing = function() {
  return new Thing;
};
```

## Local Development

Please install [composer](http://getcomposer.org/) if you don't have it yet.

Run `composer install` to get dependencies.

### Coding Style: [PSR-2](http://www.php-fig.org/psr/psr-2/)

There is a `pre-commit` hook that will run `phpcs` to check the coding style.
If it fails, you will not be allowed to commit.
