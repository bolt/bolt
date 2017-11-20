<?php

namespace Bolt\EventListener;

use Bolt\Config;
use Bolt\Events\FailedConnectionEvent;
use Bolt\Exception\Database\DatabaseConnectionException;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use PDO;
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

    /** @var Config */
    private $config;

    /**
     * Constructor.
     *
     * @param Config          $config
     * @param LoggerInterface $logger
     */
    public function __construct(Config $config, LoggerInterface $logger)
    {
        $this->config = $config;
        $this->setLogger($logger);
    }

    /**
     * Event fired on database connection failure.
     *
     * @param FailedConnectionEvent $args
     *
     * @throws DatabaseConnectionException
     */
    public function failConnect(FailedConnectionEvent $args)
    {
        $e = $args->getException();
        $this->logger->debug($e->getMessage(), ['event' => 'exception', 'exception' => $e]);

        throw new DatabaseConnectionException($args->getDriver()->getName(), $e->getMessage(), $e);
    }

    /**
     * After connecting, update this connection's database settings.
     *
     * Note: Doctrine expects this method to be called postConnect
     *
     * @param ConnectionEventArgs $args
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function postConnect(ConnectionEventArgs $args)
    {
        $db = $args->getConnection();
        $platform = $args->getDatabasePlatform();
        $platformName = $platform->getName();

        if ($platformName === 'sqlite') {
            $db->query('PRAGMA synchronous = OFF');
        } elseif ($platformName === 'mysql') {
            /** @see http://docs.doctrine-project.org/en/latest/cookbook/mysql-enums.html */
            $platform->registerDoctrineTypeMapping('enum', 'string');

            // Set database character set & collation as configured
            $charset = $this->config->get('general/database/charset');
            $collation = $this->config->get('general/database/collate');
            $db->executeQuery('SET NAMES ? COLLATE ?', [$charset, $collation], [PDO::PARAM_STR]);

            // Increase group_concat_max_len to 100000. By default, MySQL
            // sets this to a low value – 1024 – which causes issues with
            // certain Bolt content types – particularly repeaters – where
            // the outcome of a GROUP_CONCAT() query will be more than 1024 bytes.
            // See also: http://dev.mysql.com/doc/refman/5.7/en/server-system-variables.html#sysvar_group_concat_max_len
            $groupConcatMaxLen = $this->config->get('general/database/group_concat_max_len', 100000);
            $db->executeQuery('SET SESSION group_concat_max_len = ?', [$groupConcatMaxLen], [PDO::PARAM_INT]);
        } elseif ($platformName === 'postgresql') {
            /** @see https://github.com/doctrine/dbal/pull/828 */
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
