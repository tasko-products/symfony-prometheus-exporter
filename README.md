Symfony Prometheus Exporter
===========================

> Note this bundle is in **active development (it is not ready for the moment)**.

The Symfony Prometheus Exporter is a bundle that includes a bunch of generic Prometheus
metrics for your Symfony application, stored in your Redis repository.

Each metric collector can be enabled and disabled separately, so you can decide for
your app which data to collect and provide to the Prometheus query.

- messenger event metrics via middleware
- request metrics
- logging metrics
- custom metrics via the Collector composition

This Symfony bundle is based on the unoffical Prometheus PHP client [PHP library](https://github.com/PromPHP/prometheus_client_php).

Installation for applications that use Symfony Flex
---------------------------------------------------

Open a command console, enter your project directory and execute:

```bash
$ composer require tasko-products/symfony-prometheus-exporter
```

Installation for applications that don't use Symfony Flex
---------------------------------------------------------

### Step 1: Download SymfonyPrometheusExporter using composer

Require the `tasko-products/symfony-prometheus-exporter` with composer [Composer](http://getcomposer.org/).

```bash
$ composer require tasko-products/symfony-prometheus-exporter
```

### Step 2: Enable the bundle

Then, enable the bundle by adding it to the list of registered bundles
in the `config/bundles.php` file of your project:

```php
// config/bundles.php

return [
    // ...
    TaskoProducts\SymfonyPrometheusExporterBundle\TaskoProductsSymfonyPrometheusExporterBundle::class => ['all' => true],
];
```

Configuration
-------------

### Enable the message bus metrics collector (optional)

To enable the message bus metrics collector, add the `Prometheus` configuration to your services.

```yml
# app/config/services.yml

services:
    # For redis use this:
    # Prometheus\Storage\Redis:
    #     arguments:
    #         - host: '%env(PROMETHEUS_REDIS_HOST)%'
    #           port: '%env(PROMETHEUS_REDIS_PORT)%'
    #           password: '%env(PROMETHEUS_REDIS_PASSWORD)%'
    #           timeout: '%env(PROMETHEUS_REDIS_TIMEOUT)%'
    #           read_timeout: '%env(PROMETHEUS_REDIS_READ_TIMEOUT)%'
    #           persistent_connections: '%env(PROMETHEUS_REDIS_PERSISTENT_CONNECTIONS)%'
    # Prometheus\CollectorRegistry: ['@Prometheus\Storage\Redis']

    Prometheus\Storage\InMemory: ~
    Prometheus\CollectorRegistry: ['@Prometheus\Storage\InMemory']
    Prometheus\RegistryInterface: '@Prometheus\CollectorRegistry'
```

Next, register the Symfony Messenger middlewares as necessary.

```yml
# app/config/services.yml

services: 
    TaskoProducts\SymfonyPrometheusExporterBundle\Middleware\MessengerEventMiddleware: ~
    TaskoProducts\SymfonyPrometheusExporterBundle\Middleware\RetryMessengerEventMiddleware: ~
```

Now register the desired middlewares for your message bus(es).

```yml
# app/config/messenger.yml

framework:
    messenger:
        buses:
            message.bus.commands:
                middleware:
                    - 'TaskoProducts\SymfonyPrometheusExporterBundle\Middleware\MessengerEventMiddleware'
                    - 'TaskoProducts\SymfonyPrometheusExporterBundle\Middleware\RetryMessengerEventMiddleware'
```

Example for the `MessengerEventMiddleware`:
```bash
# HELP message_bus_commands_message Executed Messages
# TYPE message_bus_commands_message counter
message_bus_commands_message{message="App\\Message\\FailingFooBarMessage",label="FailingFooBarMessage"} 1337
message_bus_commands_message{message="App\\Message\\FooBarMessage",label="FooBarMessage"} 2096
# HELP message_bus_commands_message_error Failed Messages
# TYPE message_bus_commands_message_error counter
message_bus_commands_message_error{message="App\\Message\\FailingFooBarMessage",label="FailingFooBarMessage"} 1337
message_bus_commands_message_error{message="App\\Message\\FooBarMessage",label="FooBarMessage"} 0
```

Example for the `RetryMessengerEventMiddleware`:
```bash
# HELP message_bus_commands_retry_message Retried Messages
# TYPE message_bus_commands_retry_message counter
message_bus_commands_retry_message{message="App\\Message\\FailingFooBarMessage",label="FailingFooBarMessage",retry="0"} 0
message_bus_commands_retry_message{message="App\\Message\\FooBarMessage",label="FooBarMessage",retry="0"} 0
message_bus_commands_retry_message{message="App\\Message\\FooBarMessage",label="FooBarMessage",retry="2"} 666
```

Testing
-------

### PHPUnit

Start a Redis instance:
```bash
$ docker-compose up Redis
```

Install dependencies:
```bash
$ composer install
```

Run the tests:
```bash
# when Redis is not listening on localhost:
# export REDIS_HOST=...
$ ./vendor/bin/phpunit
```

### PHPUnit via docker-compose

Just start the nginx, fpm & Redis setup with docker-compose:
```bash
$ docker-compose up -d
```

Then run phpunit with docker-compose::
```bash
$ docker-compose run phpunit vendor/bin/phpunit
```

Static code analysis
--------------------

### PHPStan

Install dependencies:
```bash
$ composer install
```

And just run the analysis:
```bash
$ vendor/bin/phpstan analyse src tests
```

Code cleanup
------------

### php-cs-fixer

Install dependencies:
```bash
$ composer install
```

And just run the cleanup:
```bash
$ vendor/bin/php-cs-fixer fix
```
