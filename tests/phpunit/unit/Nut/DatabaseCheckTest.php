<?php

namespace Bolt\Tests\Nut;

use Bolt\Nut\DatabaseCheck;
use Bolt\Storage\Database\Schema\Table;
use Bolt\Tests\BoltUnitTest;
use Doctrine\DBAL;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class to test src/Nut/DatabaseCheck.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class DatabaseCheckTest extends BoltUnitTest
{
    public function testRunNormal()
    {
        /** @deprecated Drop when DBAL 2.6.3 is released. */
        if (DBAL\Version::compare('2.6.0') <= 0 && DBAL\Version::compare('2.6.3') === 1) {
            $this->markTestSkipped('DBAL 2.6.0 to 2.6.2 incorrectly detect column comments');
        }

        $app = $this->getApp();
        $command = new DatabaseCheck($app);
        $tester = new CommandTester($command);

        $tester->execute([]);
        $result = $tester->getDisplay();
        $this->assertRegExp('/The database is OK/', $result);
    }

    public function testRunChanged()
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

        $command = new DatabaseCheck($app);
        $tester = new CommandTester($command);
        $tester->execute([]);
        $result = $tester->getDisplay();

        $this->assertRegExp('/Table `bolt_newcontent` is not present/', $result);
    }

    public function testShowChanged()
    {
        $app = $this->getApp(false);
        $app['config']->set('contenttypes/newcontent', [
            'tablename' => 'newcontent',
            'fields'    => ['title' => ['type' => 'text']],
        ]);
        $app['config']->set('contenttypes/entries/fields/title/type', 'date');
        $app['config']->set('contenttypes/pages/fields/title/type', 'html');
        /** @var \Doctrine\DBAL\Platforms\AbstractPlatform $platform */
        $platform = $app['db']->getDatabasePlatform();
        $prefix = $app['schema.prefix'];
        $app['schema.content_tables']['newcontent'] = $app->share(
            function () use ($platform, $prefix) {
                return new Table\ContentType($platform, $prefix);
            }
        );

        $command = new DatabaseCheck();
        $command->setApplication($app['nut']);
        $tester = new CommandTester($command);
        $tester->execute(['--show-changes' => true, '--no-ansi' => true]);
        $result = $tester->getDisplay();

        $this->assertRegExp('/Tables to be created/', $result);
        $this->assertRegExp('/(CREATE).+(TABLE).+(bolt_newcontent)/', $result);

        $this->assertRegExp('/Tables to be altered/', $result);
        $this->assertRegExp('/(INSERT).+(INTO).+(bolt_pages)*+(title)/', $result);
    }
}
