<?php

namespace Bolt\Tests\Database\Entity;

use Bolt\Storage\Entity\Content;
use Bolt\Tests\BoltUnitTest;
use Doctrine\DBAL\Connection;

/**
 * Abstract entity test.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
abstract class AbstractEntityTest extends BoltUnitTest
{
    /** @var Connection */
    protected static $connection;
    /** @var Content */
    protected static $reference;
    /** @var Content */
    protected static $entity;

    /**
     * @param string $field
     *
     * @dataProvider entityFieldsProvider
     */
    public function testSaveReturnEntity($field)
    {
        if (self::$reference->get($field) instanceof \DateTime) {
            $this->assertSame(
                self::$entity->get($field)->format('Y-m-d'),
                self::$reference->get($field)->format('Y-m-d')
            );
        } else {
            $this->assertSame(self::$entity->get($field), self::$reference->get($field));
        }
    }

    public function entityFieldsProvider()
    {
        return [
            ['title'],
            ['slug'],
            ['html'],
            ['textarea'],
            ['markdown'],
            ['geolocation'],
            ['video'],
            ['image'],
            ['imagelist'],
            ['file'],
            ['filelist'],
            ['checkbox'],
            ['datetime'],
            ['date'],
            ['integerfield'],
            ['floatfield'],
            ['selectfield'],
            ['multiselect'],
            ['selectentry'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();
        if (self::$connection) {
            return;
        }
        $this->configure();
    }

    /**
     * {@inheritdoc}
     */
    public static function tearDownAfterClass()
    {
        if (self::$connection) {
            $schemaManager = self::$connection->getSchemaManager();

            $tables = $schemaManager->listTables();
            /** @var \Doctrine\DBAL\Schema\Table $table */
            foreach ($tables as $table) {
                $schemaManager->dropAndCreateTable($table);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        if (self::$connection) {
            return;
        }

        $app = $this->getApp(false);
        self::$connection = $app['db'];

        $platform = self::$connection->getSchemaManager()->getDatabasePlatform()->getName();
        if ($platform !== $this->getPlatformName()) {
            $this->fail(sprintf('[INVALID %s] This test class targets platform name "%s", given "%s".', $this->getBrand(), $this->getBrand(), $platform));
        }

        self::$connection->connect();
        if (!self::$connection->isConnected()) {
            $this->fail(sprintf('[FAIL %s] Failed to connect to running %s server.', $this->getBrand(), $this->getBrand()));
        }

        try {
            $app['schema']->update();
        } catch (\Exception $e) {
            $this->fail(sprintf('[FAIL %s] Unable to update schema: %s%s%s', $this->getBrand(), $e->getMessage(), PHP_EOL, $e->getTraceAsString()));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getApp($boot = true)
    {
        $app = parent::getApp(false);
        if (self::$connection === null) {
            $app['config']->set('general/database/driver', $this->getDriver());
            $app['config']->set('general/database/dbname', 'bolt_unit_test');
            $app['config']->set('general/database/user', 'bolt_unit_test');
            $app['config']->set('general/database/password', 'bolt_unit_test');
            $app['config']->set('general/database/charset', 'utf8');
            $app['config']->set('general/database/collate', 'utf8_general_ci');
            $app->boot();
            $app['schema']->update();

            $repo = $app['storage']->getRepository('showcases');
            $newEntity = ContentEntityFactory::getTestEntity();
            $repo->save($newEntity);

            self::$connection = $app['db'];
            self::$entity = $repo->find($newEntity->getId());
            self::$reference = ContentEntityFactory::getTestEntity();
        }

        return $app;
    }

    /**
     * @return string
     */
    abstract protected function getPlatformName();

    /**
     * @return string
     */
    abstract protected function getBrand();

    /**
     * @return string
     */
    abstract protected function getDriver();
}
