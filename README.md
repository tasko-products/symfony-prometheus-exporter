Symfony Prometheus Exporter
===========================

![Code Checks](https://github.com/tasko-products/symfony-prometheus-exporter/actions/workflows/checks.yml/badge.svg) ![Tests](https://github.com/tasko-products/symfony-prometheus-exporter/actions/workflows/tests.yml/badge.svg)

> Note this bundle is in **active development (it is not ready for the moment)**.

The Symfony Prometheus Exporter is a bundle that includes a bunch of generic Prometheus
metrics for your Symfony application, stored in your Redis repository.

Each metric collector can be enabled and disabled separately, so you can decide for
your app which data to collect and provide to the Prometheus query.

- messenger metrics via middleware
- messenger metrics via events
- request metrics
- logging metrics
- custom metrics via the Collector composition

This Symfony bundle is based on the unoffical Prometheus PHP client [PHP library](https://github.com/PromPHP/prometheus_client_php).

Messenger metrics via middleware
--------------------------------

The counters will be incremented when a message is dispatched, as well as when it is received from a worker.

| Middleware | Description | Metric |
| -----------| ----------- | ------ |
| MessengerEventMiddleware | This middleware increases a counter for every step a message makes. | [Counter](https://github.com/OpenObservability/OpenMetrics/blob/main/specification/OpenMetrics.md#counter) |
| RetryMessengerEventMiddleware | This middleware increases a counter for every step a retry message makes. | [Counter](https://github.com/OpenObservability/OpenMetrics/blob/main/specification/OpenMetrics.md#counter) |

Messenger metrics via events
----------------------------

| Subscriber | Events | Description | Metric |
| ---------- | ------ | ----------- | ------ |
| ActiveWorkersMetricEventSubscriber | [WorkerStartedEvent](https://github.com/symfony/symfony/blob/5.4/src/Symfony/Component/Messenger/Event/WorkerStartedEvent.php), [WorkerStoppedEvent](https://github.com/symfony/symfony/blob/5.4/src/Symfony/Component/Messenger/Event/WorkerStoppedEvent.php) | This subscriber keeps track of currently active workers. | [Gauge](https://github.com/OpenObservability/OpenMetrics/blob/main/specification/OpenMetrics.md#gauge) |
| MessagesInProcessMetricEventSubscriber | [WorkerMessageReceivedEvent](https://github.com/symfony/symfony/blob/5.4/src/Symfony/Component/Messenger/Event/WorkerMessageReceivedEvent.php), [WorkerMessageHandledEvent](https://github.com/symfony/symfony/blob/5.4/src/Symfony/Component/Messenger/Event/WorkerMessageHandledEvent.php), [WorkerMessageFailedEvent](https://github.com/symfony/symfony/blob/5.4/src/Symfony/Component/Messenger/Event/WorkerMessageFailedEvent.php) | This subscriber keeps track of messages that are currently being processed. | [Gauge](https://github.com/OpenObservability/OpenMetrics/blob/main/specification/OpenMetrics.md#gauge) |
| MessagesInTransportMetricEventSubscriber | [SendMessageToTransportsEvent](https://github.com/symfony/symfony/blob/5.4/src/Symfony/Component/Messenger/Event/SendMessageToTransportsEvent.php), [WorkerMessageReceivedEvent](https://github.com/symfony/symfony/blob/5.4/src/Symfony/Component/Messenger/Event/WorkerMessageReceivedEvent.php) | This subscriber keeps track of messages that are currently being transfered. | [Gauge](https://github.com/OpenObservability/OpenMetrics/blob/main/specification/OpenMetrics.md#gauge) |

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

### Prometheus Redis configuration (optional)

Add the `Prometheus` Redis configuration to your services. 

```yml
# app/config/services.yaml

services:
    Prometheus\Storage\Redis:
        arguments:
            - host: '%env(PROMETHEUS_REDIS_HOST)%'
              port: '%env(PROMETHEUS_REDIS_PORT)%'
              password: '%env(PROMETHEUS_REDIS_PASSWORD)%'
              # timeout: '%env(PROMETHEUS_REDIS_TIMEOUT)%'
              # read_timeout: '%env(PROMETHEUS_REDIS_READ_TIMEOUT)%'
              # persistent_connections: '%env(PROMETHEUS_REDIS_PERSISTENT_CONNECTIONS)%'
    Prometheus\CollectorRegistry: ['@Prometheus\Storage\Redis']
```

By default, the bundle comes with an in-memory configuration:

```yml
# Bundle: services.yaml
Prometheus\Storage\InMemory: ~
Prometheus\CollectorRegistry: ['@Prometheus\Storage\InMemory']
Prometheus\RegistryInterface: '@Prometheus\CollectorRegistry'
```

### Enable the symfony/messenger middleware metrics collector (optional)

Register the desired middlewares for your message bus(es).

```yml
# app/config/messenger.yaml

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

### Enable the messager event subscriber metric collectors (optional)

Register the desired event subscribers as necessary.

> todo: enable/ disable by service config

Example for the `ActiveWorkersMetricEventSubscriber`:
```bash
# HELP messenger_events_active_workers Active Workers
# TYPE messenger_events_active_workers gauge
messenger_events_active_workers{queue_names="default_queue, priority_queue",transport_names="async"} 1
```

Example for the `MessagesInProcessMetricEventSubscriber`:
```bash
# HELP messenger_events_messages_in_process Messages In Process
# TYPE messenger_events_messages_in_process gauge
messenger_events_messages_in_process{message_path="App\\Message\\FailingFooBarMessage",message_class="FailingFooBarMessage",receiver="async",bus="messenger_bus_default"} 1
messenger_events_messages_in_process{message_path="App\\Message\\FooBarMessage",message_class="FooBarMessage",receiver="async",bus="messenger_bus_default"} 0
```

Example for the `MessagesInTransportMetricEventSubscriber`:
```bash
# HELP messenger_events_messages_in_transport Messages In Transport
# TYPE messenger_events_messages_in_transport gauge
messenger_events_messages_in_transport{message_path="App\\Message\\FailingFooBarMessage",message_class="FailingFooBarMessage",bus="messenger_bus_default"} 0
messenger_events_messages_in_transport{message_path="App\\Message\\FooBarMessage",message_class="FooBarMessage",bus="messenger_bus_default"} 1412
```

Testing
-------

### PHPUnit

Install dependencies:
```bash
$ composer install
```

Run the tests:
```bash
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
