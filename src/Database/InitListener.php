<?php
namespace Bolt\Database;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use Silex\Application;
use Silex\ServiceProviderInterface;

class InitListener implements ServiceProviderInterface, EventSubscriber
{
    public function register(Application $app)
    {
        $self = $this;
        $app['dbs.event_manager'] = $app->share(
            $app->extend(
                'dbs.event_manager',
                function($managers) use ($self) {
                    foreach ($managers as $name => $manager) {
                        /** @var \Doctrine\Common\EventManager $manager */
                        $manager->addEventSubscriber($self);
                    }
                    return $managers;
                }
            )
        );
    }

    public function postConnect(ConnectionEventArgs $args)
    {
        $db = $args->getConnection();
        $driver = $args->getDriver()->getName();

        if ($driver == 'pdo_sqlite') {
            $db->query('PRAGMA synchronous = OFF');
        } elseif ($driver == 'pdo_mysql') {
            /**
             * @link https://groups.google.com/forum/?fromgroups=#!topic/silex-php/AR3lpouqsgs
             */
            $db->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');

            // set utf8 on names and connection as all tables has this charset
            $db->executeQuery('SET NAMES utf8');
            $db->executeQuery('SET CHARACTER_SET_CONNECTION = utf8');
            $db->executeQuery('SET CHARACTER SET utf8');
        }
    }

    public function getSubscribedEvents()
    {
        return array(Events::postConnect);
    }

    public function boot(Application $app)
    {
    }
}
