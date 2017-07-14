<?php

namespace Bolt\Tests\Translation;

use Bolt\Tests\BoltUnitTest;
use Bolt\Translation\TranslationFile;
use PHPUnit\Framework\Assert;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Class to test src/Translation/TranslationFile.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class TranslationFileTest extends BoltUnitTest
{
    public static function setUpBeforeClass()
    {
        $fs = new Filesystem();
        $fs->mirror(
            TEST_ROOT . '/app/resources/translations/en_GB/',
            PHPUNIT_WEBROOT . '/app/resources/translations/en_GB/',
            null,
            ['override' => true]
        );
    }

    public function testSetup()
    {
        $app = $this->getApp();
        $tr = new TranslationFile($app, 'messages', 'en_GB');
        $this->assertEquals('messages', Assert::readAttribute($tr, 'domain'));
    }

    public function testPath()
    {
        $app = $this->getApp();
        $tr = new TranslationFile($app, 'messages', 'en_GB');
        $path = $tr->path();
        $this->assertEquals(PHPUNIT_WEBROOT . '/app/resources/translations/en_GB/messages.en_GB.yml', $path[0]);
    }

    public function testContentInfos()
    {
        $app = $this->getApp();
        $tr = new TranslationFile($app, 'infos', 'en_GB');
        $content = $tr->content();
        $parsed = Yaml::parse($content);
        $this->assertArrayHasKey('info', $parsed);
    }

    public function testMessages()
    {
        $app = $this->getApp();
        $tr = new TranslationFile($app, 'messages', 'en_GB');
        $content = $tr->content();
        $parsed = Yaml::parse($content);
        $this->assertInternalType('array', $parsed);
    }

    public function testIsWriteAllowed()
    {
        $app = $this->getApp();
        $tr = new TranslationFile($app, 'messages', 'en_GB');
        $this->assertTrue($tr->isWriteAllowed());
    }

    public function testFallbackLocale()
    {
        $app = $this->getApp();
        $tr1 = new TranslationFile($app, 'infos', 'en_GB');
        $tr2 = new TranslationFile($app, 'infos', 'en_CO');
        $content1 = $tr1->content();
        $content2 = $tr2->content();
        $this->assertSame($content1, $content2);
    }
}
