Symfony Prometheus Exporter
===========================

![Code Checks](https://github.com/tasko-products/symfony-prometheus-exporter/actions/workflows/checks.yml/badge.svg) ![Tests](https://github.com/tasko-products/symfony-prometheus-exporter/actions/workflows/tests.yml/badge.svg)

> Please note that this package is under **active development**. There may still be occasional errors when collecting metrics. 
>
> Please report bugs as an issue in Github ([new issue](https://github.com/tasko-products/symfony-prometheus-exporter/issues/new)) or send us a pull request

The Symfony Prometheus Exporter is a bundle that includes a bunch of generic Prometheus
metrics for your Symfony application, stored in your Redis repository.

Each metric collector can be enabled and disabled separately, so you can decide for
your app which data to collect and provide to the Prometheus query.

- messenger metrics via middleware
- messenger metrics via events
- (todo) request metrics
- (todo) logging metrics
- (todo) custom metrics via the Collector composition

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

### Open metrics route (optional)

#### 1. Register the route

Add the following yaml to your routes config to register the `open_metrics` route.

```yml
# app/config/routes.yaml

open_metrics:
    path: /metrics
    controller: TaskoProducts\SymfonyPrometheusExporterBundle\Controller\OpenMetricsController::metrics
```

#### 2. Secure the route with basic authentication

> Please use only basic authentication if you communicate with your application via **TLS**, or locally for development purposes. Your password is only base64 encoded.

To secure your routes you need the Symfony Security Bundle.
Make sure it is installed by running:

```bash
$ composer require symfony/security-bundle
```

Next you define a password hasher, if none has been defined yet. Again, make sure it is installed by running:
```bash
$ composer require symfony/password-hasher
```

Then add it to your security config.

```yml
# app/config/packages/security.yaml

security:
    password_hashers:
        Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface:
            algorithm: 'auto'
            cost:      15
```

Generate an encoded password.

Symfony version < 6:
```bash
$ bin/console security:encode-password
```

Symfony version >= 6:
```bash
$ bin/console security:hash-password
```

Store the encoded password and the username in your environments file.

```bash
# app/.env
OPEN_METRICS_BASIC_AUTH_USERNAME='secure-user-name'
OPEN_METRICS_BASIC_AUTH_PASSWORD='$argon2id$v=19$m=65536,t=4,p=1$DbXHchj+n5kTi9CNG7JhFA$HXtY5aSueVGbK3RxkvBlc8U1+d6Y7VJtYGbbV4CbpFw'
```

Add Symfony's [memory user provider](https://symfony.com/doc/5.4/security/user_providers.html#memory-user-provider) to the security providers. Here you use the environment variables you defined before.

```yml
# app/config/packages/security.yaml

security:
    providers:
        open_metrics_basic_auth:
            memory:
                users:
                - identifier: '%env(OPEN_METRICS_BASIC_AUTH_USERNAME)%'
                  password: '%env(OPEN_METRICS_BASIC_AUTH_PASSWORD)%'
                  roles: [ ROLE_OPEN_METRICS_USER ]
```

Finally, you need a firewall to secure your route. Add the following [http_basic](https://symfony.com/doc/5.4/security.html#http-basic) firewall to your security config.

```yml
# app/config/packages/security.yaml

security:
    firewalls:
        open_metrics:
            pattern:  ^/metrics
            http_basic:
                provider: open_metrics_basic_auth
```

Try to retrieve your metrics with the following curl and set the `Authorization` header to your secrets.

Encode your secrets on Linux and Mac:
```bash
# output base64 string:
# c2VjdXJlLXVzZXItbmFtZTpzZWN1cmUtdXNlci1wYXNzd29yZAo=

$ echo 'secure-user-name:secure-user-passwort' | base64
```

On Windows use [Certutil](https://docs.microsoft.com/en-us/previous-versions/windows/it-pro/windows-server-2012-R2-and-2012/cc732443(v=ws.11)?redirectedfrom=MSDN#BKMK_encode) to encode your secrets as base64.
```bash
# Certutils requires you to encode based on files

$ certutil -encode your-secrets.txt tmp.b64 && findstr /v /c:- tmp.b64 > encoded-secrets.b64
```

```bash
$ curl --request GET \
  --url http://localhost/metrics \
  --header 'Authorization: Basic c2VjdXJlLXVzZXItbmFtZTpzZWN1cmUtdXNlci1wYXNzd29yZAo='
```

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
**To overwrite the default labels and texts:**

> Please note that changes to metric names are only valid if they match the following regular 
> expression:
>
> `/^[a-zA-Z_:][a-zA-Z0-9_:]*$/`
>
> Validate your name on [regex101.com](https://regex101.com/r/2d8eqk/1)

Create a new yaml configuration file
`app/config/packages/prometheus_metrics.yaml` if it does not already exist. Add the following 
configuration and now you can adjust the labels and texts via the following configuration.

```yml
# app/config/packages/prometheus_metrics.yaml

tasko_products_symfony_prometheus_exporter:
  middlewares:
    event_middleware:
      # enabled by 'app/config/messenger.yaml'
      namespace: 'middleware'
      metric_name: 'message'
      help_text: 'Executed Messages'
      labels:
        bus: 'bus'
        message: 'message'
        label: 'label'
      error_help_text: 'Failed Messages'
      error_labels:
        bus: 'bus'
        message: 'message'
        label: 'label'

    retry_event_middleware:
      # enabled by 'app/config/messenger.yaml'
      namespace: 'middleware'
      metric_name: 'retry_message'
      help_text: 'Retried Messages'
      labels:
        bus: 'bus'
        message: 'message'
        label: 'label'
        retry: 'retry'
```

Example for the `MessengerEventMiddleware`:
```bash
# HELP middleware_message Executed Messages
# TYPE middleware_message counter
middleware_message{bus="message_bus_commands",message="App\\Message\\FailingFooBarMessage",label="FailingFooBarMessage"} 1337
middleware_message{bus="message_bus_commands",message="App\\Message\\FooBarMessage",label="FooBarMessage"} 2096
# HELP middleware_message_error Failed Messages
# TYPE middleware_message_error counter
middleware_message_error{bus="message_bus_commands",message="App\\Message\\FailingFooBarMessage",label="FailingFooBarMessage"} 1337
middleware_message_error{bus="message_bus_commands",message="App\\Message\\FooBarMessage",label="FooBarMessage"} 0
```

Example for the `RetryMessengerEventMiddleware`:
```bash
# HELP middleware_retry_message Retried Messages
# TYPE middleware_retry_message counter
middleware_retry_message{bus="message_bus_commands",message="App\\Message\\FailingFooBarMessage",label="FailingFooBarMessage",retry="0"} 0
middleware_retry_message{bus="message_bus_commands",message="App\\Message\\FooBarMessage",label="FooBarMessage",retry="0"} 0
middleware_retry_message{bus="message_bus_commands",message="App\\Message\\FooBarMessage",label="FooBarMessage",retry="2"} 666
```

### Enable the messager event subscriber metric collectors (optional)

> Please note that changes to metric names are only valid if they match the following regular 
> expression:
>
> `/^[a-zA-Z_:][a-zA-Z0-9_:]*$/`
> 
> Validate your name on [regex101.com](https://regex101.com/r/2d8eqk/1)

Register the desired event subscribers as necessary. Create a new configuration yaml file
`app/config/packages/prometheus_metrics.yaml`. Add the following configuration and now you can activate/
deactivate the event subscribers and adjust the labels and texts via the following configuration.

```yml
# app/config/packages/prometheus_metrics.yaml

tasko_products_symfony_prometheus_exporter:
  event_subscribers:
    active_workers:
      enabled: false
    #   namespace: 'messenger_events'
    #   metric_name: 'active_workers'
    #   help_text: 'Active Workers'
    #   labels:
    #     queue_names: 'queue_names'
    #     transport_names: 'transport_names'

    messages_in_process:
      enabled: false
    #   namespace: 'messenger_events'
    #   metric_name: 'messages_in_process'
    #   help_text: 'Messages In Process'
    #   labels:
    #     message_path: 'message_path'
    #     message_class: 'message_class'
    #     receiver: 'receiver'
    #     bus: 'bus'

    messages_in_transport:
      enabled: false
    #   namespace: 'messenger_events'
    #   metric_name: 'messages_in_transport'
    #   help_text: 'Messages In Transport'
    #   labels:
    #     message_path: 'message_path'
    #     message_class: 'message_class'
    #     bus: 'bus'
```

Example for the active_workers (`ActiveWorkersMetricEventSubscriber`):
```bash
# HELP messenger_events_active_workers Active Workers
# TYPE messenger_events_active_workers gauge
messenger_events_active_workers{queue_names="default_queue, priority_queue",transport_names="async"} 1
```

Example for the messages_in_process (`MessagesInProcessMetricEventSubscriber`):
```bash
# HELP messenger_events_messages_in_process Messages In Process
# TYPE messenger_events_messages_in_process gauge
messenger_events_messages_in_process{message_path="App\\Message\\FailingFooBarMessage",message_class="FailingFooBarMessage",receiver="async",bus="messenger_bus_default"} 1
messenger_events_messages_in_process{message_path="App\\Message\\FooBarMessage",message_class="FooBarMessage",receiver="async",bus="messenger_bus_default"} 0
```

Example for the messages_in_transport (`MessagesInTransportMetricEventSubscriber`):
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

Copyright (c) 2022 tasko Products GmbH 2022. MIT licence.

For the full copyright and license information, please view the LICENSE file that was distributed with this source code.