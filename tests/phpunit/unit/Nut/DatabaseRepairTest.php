<?php

namespace Bolt\Tests\Nut;

use Bolt\Nut\DatabaseRepair;
use Bolt\Storage\Database\Schema\Table;
use Bolt\Tests\BoltUnitTest;
use Doctrine\DBAL;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/DatabaseRepair.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class DatabaseRepairTest extends BoltUnitTest
{
    public function testSchemaUpToDate()
    {
        /** @deprecated Drop when minimum PHP version is 7.1 or greater. */
        if (DBAL\Version::compare('2.6.3') >= 0) {
            $this->markTestSkipped();
        }

        $app = $this->getApp();
        $command = new DatabaseRepair($app);
        $tester = new CommandTester($command);

        $tester->execute([], ['interactive' => false]);
        $result = $tester->getDisplay();
        $this->assertRegExp('/Your database is already up to date/', $result);
    }

    public function testUpdateSchema()
    {
        $tester = $this->getTester();
        $tester->execute([], ['interactive' => false]);
        $result = $tester->getDisplay();
        $this->assertRegExp('/Created table `bolt_newcontent`/', $result);
    }

    public function testUpdateSchemaDumpSql()
    {
        $this->resetDb();
        $tester = $this->getTester();
        $tester->execute(['--dump-sql' => true]);
        $result = $tester->getDisplay();
        $this->assertRegExp('/CREATE TABLE bolt_newcontent/', $result);
    }

    /**
     * @return CommandTester
     */
    private function getTester()
    {
        $app = $this->getApp(false);
        $app['config']->set('contenttypes/newcontent', [
            'tablename' => 'newcontent',
            'fields'    => ['title' => ['type' => 'text']],
        ]);
        /** @var \Doctrine\DBAL\Platforms\AbstractPlatform $platform */
        $platform = $app['db']->getDatabasePlatform();
        $prefix = $app['schema.prefix'];
        $app['schema.content_tables']['newcontent'] = $app->share(
            function () use ($platform, $prefix) {
                return new Table\ContentType($platform, $prefix);
            }
        );

        $command = new DatabaseRepair($app);
        $tester = new CommandTester($command);

        return $tester;
    }
}
