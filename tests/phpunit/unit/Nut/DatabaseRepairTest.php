<?php
namespace Bolt\Tests\Nut;

use Bolt\Nut\DatabaseRepair;
use Bolt\Storage\Database\Schema\Table;
use Bolt\Tests\BoltUnitTest;
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
        $app = $this->getApp();
        $command = new DatabaseRepair($app);
        $tester = new CommandTester($command);

        $tester->execute([]);
        $result = $tester->getDisplay();
        $this->assertEquals('Your database is already up to date.', trim($result));
    }

    public function testUpdateSchema()
    {
        $tester = $this->executeChanged([]);
        $result = $tester->getDisplay();
        $this->assertRegExp('/Created table `bolt_newcontent`/', $result);
    }

    public function testUpdateSchemaDumpSql()
    {
        $tester = $this->executeChanged(['--dump-sql']);
        $result = $tester->getDisplay();
        $this->assertRegExp('/CREATE TABLE bolt_newcontent/', $result);
    }

    private function executeChanged(array $arguments)
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

        $app->boot();
        $command = new DatabaseRepair($app);
        $tester = new CommandTester($command);

        return $tester;
    }
}
