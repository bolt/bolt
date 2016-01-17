<?php

namespace Bolt\EventListener;

use Bolt\Events\FailedConnectionEvent;
use Bolt\Exception\LowLevelDatabaseException;
use Bolt\Helpers\Str;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Listener for Doctrine events.
 *
 * @author Carson Full <carsonfull@gmail.com>
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DoctrineListener implements EventSubscriber
{
    use LoggerAwareTrait;

    public function __construct(LoggerInterface $logger)
    {
        $this->setLogger($logger);
    }

    /**
     * Event fired on database connection failure.
     *
     * @param FailedConnectionEvent $args
     *
     * @throws LowLevelDatabaseException
     */
    public function failConnect(FailedConnectionEvent $args)
    {
        $e = $args->getException();
        $this->logger->debug($e->getMessage(), ['event' => 'exception', 'exception' => $e]);

        // Trap double exceptions
        set_exception_handler(function () {});

        /*
         * Using Driver here since Platform may try to connect
         * to the database, which has failed since we are here.
         */
        $platform = $args->getDriver()->getName();
        $platform = Str::replaceFirst('pdo_', '', $platform);

        throw LowLevelDatabaseException::failedConnect($platform, $e);
    }

    /**
     * After connecting, update this connection's database settings.
     *
     * Note: Doctrine expects this method to be called postConnect
     *
     * @param ConnectionEventArgs $args
     */
    public function postConnect(ConnectionEventArgs $args)
    {
        $db = $args->getConnection();
        $platform = $args->getDatabasePlatform()->getName();

        if ($platform === 'sqlite') {
            $db->query('PRAGMA synchronous = OFF');
        } elseif ($platform === 'mysql') {
            /**
             * @link https://groups.google.com/forum/?fromgroups=#!topic/silex-php/AR3lpouqsgs
             */
            $db->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

            // Set utf8 on names and connection, as all tables have this charset. We don't
            // also do 'SET CHARACTER SET utf8', because it will actually reset the
            // character_set_connection and collation_connection to @@character_set_database
            // and @@collation_database respectively.
            // see: http://stackoverflow.com/questions/1566602/is-set-character-set-utf8-necessary
            $db->executeQuery('SET NAMES utf8');
            $db->executeQuery('SET CHARACTER_SET_CONNECTION = utf8');
        } elseif ($platform === 'postgresql') {
            /**
             * @link https://github.com/doctrine/dbal/pull/828
             */
            $db->executeQuery("SET NAMES 'utf8'");
        }
    }

    public function getSubscribedEvents()
    {
        return [
            Events::postConnect,
            'failConnect',
        ];
    }
}
