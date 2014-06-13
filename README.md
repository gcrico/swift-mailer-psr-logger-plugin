swift-mailer-psr-logger-plugin
==============================

Logs swift mailer activity with a (PSR-3) logger.

There are [https://packagist.org/search/?q=psr/log](several psr/log implementations) out there.



## Installation

    php composer.phar require gcrico/swift-mailer-psr-logger-plugin @stable


## Example Usage

This will log all the mailer activity:

    use gcrico\SwiftMailerPsrLoggerPlugin\SwiftMailerPsrLoggerPlugin;

    $transport = /*...*/;
    $mailer = Swift_Mailer::newInstance($transport);

    $logger = new YourFavoritePsr3Logger();
    $mailer_logger = new SwiftMailerPsrLoggerPlugin($logger);
    $mailer->registerPlugin($mailer_logger);


The default log levels are:

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


You can change the default log levels:

    $mailer_log_levels =  array(
        'sendPerformed.SUCCESS'     => LogLevel::DEBUG,
        'sendPerformed.NOT_SUCCESS' => LogLevel::WARNING,
        'exceptionThrown'           => LogLevel::WARNING,
    );
    $mailer_logger = new SwiftMailerPsrLoggerPlugin($logger, $mailer_log_levels);


You can disable logging of some events, using a falsy value for the level.

    $mailer_log_levels =  array(
        'commandSent'               => 0,
        'sendPerformed.NOT_SUCCESS' => false,
        'exceptionThrown'           => null,
        'beforeTransportStopped'    => '',
    );
    $mailer_logger = new SwiftMailerPsrLoggerPlugin($logger, $mailer_log_levels);


## Example Usage with Silex

First, this workaround is needed (https://github.com/silexphp/Silex/issues/959).

    $app['swiftmailer.spooltransport'] = $app->share(function ($app) {
        return new \Swift_Transport_SpoolTransport($app['swiftmailer.transport.eventdispatcher'], $app['swiftmailer.spool']);
    });


Let's extends the mailer service:

    use gcrico\SwiftMailerPsrLoggerPlugin\SwiftMailerPsrLoggerPlugin;

    $app->extend('mailer', function ($mailer) use ($app) {
        $app_logger = $app['logger'];
        $mailer_logger = new SwiftMailerPsrLoggerPlugin($app_logger);
        $mailer->registerPlugin($mailer_logger);
        return $mailer;
    });


**Warning!** The logger provider must not sends emails with the mailer it is logging, or you will run in a dead loop!


