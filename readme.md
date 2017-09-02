# Dependency Injection

[![Build Status](https://travis-ci.org/webdeveric/DI.svg?branch=master)](https://travis-ci.org/webdeveric/DI)
[![Test Coverage](https://codeclimate.com/github/webdeveric/DI/badges/coverage.svg)](https://codeclimate.com/github/webdeveric/DI/coverage)
[![Code Climate](https://codeclimate.com/github/webdeveric/DI/badges/gpa.svg)](https://codeclimate.com/github/webdeveric/DI)
[![Issue Count](https://codeclimate.com/github/webdeveric/DI/badges/issue_count.svg)](https://codeclimate.com/github/webdeveric/DI/issues)

**Example usage:**

```php
use webdeveric\DI\DI;

$container = new DI();

$container->person = function() {
  $person = new stdClass;
  $person->name = "Test Testerson";
  return $person;
};

var_dump( $container->person->name );
```

## Local Development

Please install [composer](http://getcomposer.org/) if you don't have it yet.

Run `composer install` to get dependencies.

### Coding Style: [PSR-2](http://www.php-fig.org/psr/psr-2/)

Run `composer setup-hooks` to setup the git `pre-commit` hook.

There is a `pre-commit` hook that will run `phpcs` to check the coding style.
If it fails, you will not be allowed to commit.

### Tests

If you add a feature, please create a test for it too.
