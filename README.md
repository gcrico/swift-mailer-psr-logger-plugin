swift-mailer-psr-logger-plugin
==============================

Logs swift mailer activity with a (PSR-3) logger.

There are [several psr/log implementations](https://packagist.org/search/?q=psr/log) out there.

**Warning!** The logger must NOT send emails with a mailer that it is logging, or you will run in a dead loop!


## Installation

```bash
php composer.phar require gcrico/swift-mailer-psr-logger-plugin @stable
```


## Example Usage

This will log all the mailer activity:

```php
use gcrico\SwiftMailerPsrLoggerPlugin\SwiftMailerPsrLoggerPlugin;

$transport = /*...*/;
$mailer = Swift_Mailer::newInstance($transport);

$logger = new YourFavoritePsr3Logger();
$mailer_logger = new SwiftMailerPsrLoggerPlugin($logger);
$mailer->registerPlugin($mailer_logger);
```

The default log levels are:

```php
    'sendPerformed.SUCCESS'     => LogLevel::INFO,
    'sendPerformed.NOT_SUCCESS' => LogLevel::ERROR,
    'exceptionThrown'           => LogLevel::ERROR,
    'beforeSendPerformed'       => LogLevel::DEBUG,
    'commandSent'               => LogLevel::DEBUG,
    'responseReceived'          => LogLevel::DEBUG,
    'beforeTransportStarted'    => LogLevel::DEBUG,
    'transportStarted'          => LogLevel::DEBUG,
    'beforeTransportStopped'    => LogLevel::DEBUG,
    'transportStopped'          => LogLevel::DEBUG,
```

You can change the default log levels:

```php
$mailer_log_levels =  array(
    'sendPerformed.SUCCESS'     => LogLevel::DEBUG,
    'sendPerformed.NOT_SUCCESS' => LogLevel::WARNING,
    'exceptionThrown'           => LogLevel::WARNING,
);
$mailer_logger = new SwiftMailerPsrLoggerPlugin($logger, $mailer_log_levels);
```

You can disable logging of some events, using a falsy value for the level.

```php
$mailer_log_levels =  array(
    'commandSent'               => 0,
    'sendPerformed.NOT_SUCCESS' => false,
    'exceptionThrown'           => null,
    'beforeTransportStopped'    => '',
);
$mailer_logger = new SwiftMailerPsrLoggerPlugin($logger, $mailer_log_levels);
```

## Example Usage with Silex

This workaround is needed for Silex <= 1.2.0 (https://github.com/silexphp/Silex/issues/959 ; https://github.com/silexphp/Silex/commit/8d2140c001807d96f4438d0f9efb2f794c4200ed).

```php
$app['swiftmailer.spooltransport'] = $app->share(function ($app) {
    return new \Swift_Transport_SpoolTransport($app['swiftmailer.transport.eventdispatcher'], $app['swiftmailer.spool']);
});
```

Let's extends the mailer service:

```php
use gcrico\SwiftMailerPsrLoggerPlugin\SwiftMailerPsrLoggerPlugin;

$app->extend('mailer', function ($mailer) use ($app) {
    $app_logger = $app['logger'];
    $mailer_logger = new SwiftMailerPsrLoggerPlugin($app_logger);
    $mailer->registerPlugin($mailer_logger);
    return $mailer;
});
```



