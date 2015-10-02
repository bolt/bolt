<?php
namespace Bolt\Tests\Extensions;

use Bolt\Tests\BoltUnitTest;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Base class for extension specific testing.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 *
 * @runTestsInSeparateProcesses
 */
abstract class AbstractExtensionsUnitTest extends BoltUnitTest
{
    public function setup()
    {
        $this->php = \PHPUnit_Extension_FunctionMocker::start($this, 'Bolt')
            ->mockFunction('file_exists')
            ->mockFunction('is_readable')
            ->mockFunction('is_dir')
            ->mockFunction('copy')
            ->mockFunction('file_get_contents')
            ->getMock();

        $this->php2 = \PHPUnit_Extension_FunctionMocker::start($this, 'Bolt\Tests\Extensions\Mock')
            ->mockFunction('file_get_contents')
            ->getMock();
    }

    public function tearDown()
    {
        \PHPUnit_Extension_FunctionMocker::tearDown();

        $fs = new Filesystem();
        if ($fs->exists(PHPUNIT_WEBROOT . '/app/cache/config_cache.php')) {
            $fs->remove(PHPUNIT_WEBROOT . '/app/cache/config_cache.php');
        }
        if ($fs->exists(PHPUNIT_WEBROOT . '/extensions/local/')) {
            $fs->remove(PHPUNIT_WEBROOT . '/extensions/local/');
        }
    }

    public function localExtensionInstall()
    {
        $fs = new Filesystem();
        $fs->mirror(PHPUNIT_ROOT . '/resources/extensions/local/', PHPUNIT_WEBROOT . '/extensions/local/', null, ['delete' => true]);
    }
}
