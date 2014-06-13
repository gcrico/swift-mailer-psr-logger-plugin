<?php
namespace gcrico\SwiftMailerPsrLoggerPlugin;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;


/**
 * Logs swiftmailer activity with a (PSR-3) logger.
 *
 * Warning! The logger must not sends emails with the same mailer, or you will run in a dead loop!
 *
 * Message sending success are logged with the INFO level.
 * Message sending failure are logged with the ERROR level.
 * TransportException are logged with ERROR level, before been rethrown.
 * Plubing events (Commands, Responses, Transport) are logged with DEBUG level.
 *
 */
class SwiftMailerPsrLoggerPlugin implements
    \Swift_Events_SendListener,
    \Swift_Events_CommandListener,
    \Swift_Events_ResponseListener,
    \Swift_Events_TransportChangeListener,
    \Swift_Events_TransportExceptionListener
{
    /**
     * The PSR-3 logger.
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Prefix for messages.
     *
     * @var string
     */
    private $prefix = '[MAILER] ';

    /**
     * Map of events to log-levels.
     *
     * @var array
     */
    private $levels = array(
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
    );

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     * @param array $levels
     */
    public function __construct(LoggerInterface $logger, $levels=array())
    {
        $this->logger = $logger;
        foreach ($levels as $event => $level) {
            $this->level[$event] = $level;
        }
    }

    /**
     * Adds the message prefix and invokes the logger->log() method.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     */
    private function log($level, $message, array $context = array())
    {
        // Using a falsy level disables logging
        if ($level) {
            $this->logger->log($level, $this->prefix . $message, $context);
        }
    }

    /**
     * Invoked immediately before the Message is sent.
     *
     * @param \Swift_Events_SendEvent $evt
     */
    public function beforeSendPerformed(\Swift_Events_SendEvent $evt)
    {
        $level = $this->levels['beforeSendPerformed'];
        $this->log($level, 'MESSAGE (beforeSend): ', array(
                             'message' => $evt->getMessage()->toString(),
                         ));
    }

    /**
     * Invoked immediately after the Message is sent.
     *
     * @param \Swift_Events_SendEvent $evt
     */
    public function sendPerformed(\Swift_Events_SendEvent $evt)
    {
        $result = $evt->getResult();
        $failed_recipients = $evt->getFailedRecipients();
        $message = $evt->getMessage();

        if ($result === \Swift_Events_SendEvent::RESULT_SUCCESS) {
            $level = $this->levels['sendPerformed.SUCCESS'];
        } else {
            $level = $this->levels['sendPerformed.NOT_SUCCESS'];
        }

        $this->log($level, 'MESSAGE (sendPerformed): ', array(
                             'result'            => $result,
                             'failed_recipients' => $failed_recipients,
                             'message'           => $message->toString(),
                         ));
    }

    /**
     * Invoked immediately following a command being sent.
     *
     * @param \Swift_Events_CommandEvent $evt
     */
    public function commandSent(\Swift_Events_CommandEvent $evt)
    {
        $level = $this->levels['commandSent'];
        $command = $evt->getCommand();
        $this->log($level, sprintf(">> %s", $command));
    }

    /**
     * Invoked immediately following a response coming back.
     *
     * @param \Swift_Events_ResponseEvent $evt
     */
    public function responseReceived(\Swift_Events_ResponseEvent $evt)
    {
        $level = $this->levels['responseReceived'];
        $response = $evt->getResponse();
        $this->log($level, sprintf("<< %s", $response));
    }

    /**
     * Invoked just before a Transport is started.
     *
     * @param \Swift_Events_TransportChangeEvent $evt
     */
    public function beforeTransportStarted(\Swift_Events_TransportChangeEvent $evt)
    {
        $level = $this->levels['beforeTransportStarted'];
        $transportName = get_class($evt->getSource());
        $this->log($level, sprintf("++ Starting %s", $transportName));
    }

    /**
     * Invoked immediately after the Transport is started.
     *
     * @param \Swift_Events_TransportChangeEvent $evt
     */
    public function transportStarted(\Swift_Events_TransportChangeEvent $evt)
    {
        $level = $this->levels['transportStarted'];
        $transportName = get_class($evt->getSource());
        $this->log($level, sprintf("++ %s started", $transportName));
    }

    /**
     * Invoked just before a Transport is stopped.
     *
     * @param \Swift_Events_TransportChangeEvent $evt
     */
    public function beforeTransportStopped(\Swift_Events_TransportChangeEvent $evt)
    {
        $level = $this->levels['beforeTransportStopped'];
        $transportName = get_class($evt->getSource());
        $this->log($level, sprintf("++ Stopping %s", $transportName));
    }

    /**
     * Invoked immediately after the Transport is stopped.
     *
     * @param \Swift_Events_TransportChangeEvent $evt
     */
    public function transportStopped(\Swift_Events_TransportChangeEvent $evt)
    {
        $level = $this->levels['transportStopped'];
        $transportName = get_class($evt->getSource());
        $this->log($level, sprintf("++ %s stopped", $transportName));
    }

    /**
     * Invoked as a TransportException is thrown in the Transport system.
     *
     * @param \Swift_Events_TransportExceptionEvent $evt
     * @throws \Swift_TransportException
     */
    public function exceptionThrown(\Swift_Events_TransportExceptionEvent $evt)
    {
        $e = $evt->getException();
        $message = $e->getMessage();

        $level = $this->levels['exceptionThrown'];
        $this->log($level, sprintf("!! %s", $message));

        $evt->cancelBubble();
        throw new \Swift_TransportException($message);
    }
}
