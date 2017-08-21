<?php

namespace Bolt\Tests\Composer\Script;

use Bolt\Collection\MutableBag;
use Bolt\Composer\Script\BootstrapYamlUpdater;
use Bolt\Version;
use Composer\IO\BufferIO;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class BootstrapYamlUpdaterTest extends TestCase
{
    public function providePaths()
    {
        return [
            '3.2 default' => [
                [
                    'cache'     => 'app/cache',
                    'config'    => 'app/config',
                    'database'  => 'app/database',
                    'web'       => 'public',
                    'themebase' => 'public/theme',
                    'files'     => 'public/files',
                    'view'      => 'public/bolt-public/view',
                ],
                [],
            ],

            'custom web' => [
                [
                    'web'       => 'web',
                    'themebase' => 'web/theme',
                    'files'     => 'web/files',
                    'view'      => 'web/bolt-public/view',
                ],
                [
                    'web' => '%site%/web',
                ],
            ],

            'custom app (same base folder)' => [
                [
                    'cache'    => 'myapp/cache',
                    'config'   => 'myapp/config',
                    'database' => 'myapp/database',
                ],
                [
                    'app' => '%site%/myapp',
                ],
            ],

            'custom app (one folder different)' => [
                [
                    'cache'    => 'var/cache',
                    'config'   => 'myapp/config',
                    'database' => 'myapp/database',
                ],
                [
                    'app'   => '%site%/myapp',
                    'cache' => '%var%/cache',
                ],
            ],

            'custom app (one folder different) #2' => [
                [
                    'cache'    => 'var/cache',
                    'config'   => '../config',
                    'database' => '../database',
                ],
                [
                    'app'   => '%site%/..',
                    'cache' => '%var%/cache',
                ],
            ],

            'custom app (all folders different)' => [
                [
                    'cache'    => 'var/cache',
                    'config'   => 'myapp/config',
                    'database' => '../database',
                ],
                [
                    'cache'    => '%var%/cache',
                    'config'   => 'myapp/config',
                    'database' => '../database',
                ],
            ],

            'custom leaves' => [
                [
                    'cache'     => 'app/cache2',
                    'config'    => 'app/config2',
                    'database'  => 'app/database2',
                    'web'       => 'public',
                    'themebase' => 'public/theme2',
                    'files'     => 'public/files2',
                    'view'      => 'public/bolt-public/view2',
                ],
                [
                    'cache'       => '%app%/cache2',
                    'config'      => '%app%/config2',
                    'database'    => '%app%/database2',
                    'themes'      => '%web%/theme2',
                    'files'       => '%web%/files2',
                    'bolt_assets' => '%web%/bolt-public/view2',
                ],
            ],
        ];
    }

    /**
     * @dataProvider providePaths
     *
     * @param $previous
     * @param $result
     */
    public function testPaths($previous, $result)
    {
        $updater = new BootstrapYamlUpdater(new BufferIO());

        $actual = $updater->updatePaths(new MutableBag($previous));
        $this->assertEquals($result, $actual->toArray());

        // Ensure code is idempotent.
        $actual = $updater->updatePaths($actual);
        $this->assertEquals($result, $actual->toArray());
    }

    public function testSaveCustomPaths()
    {
        $config = [
            'paths' => [
                'app' => '%site%/app2',
            ],
        ];

        $io = new BufferIO();
        $filesystem = $this->getMockBuilder(Filesystem::class)
            ->setMethods(['dumpFile', 'remove'])
            ->getMock()
        ;
        $filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('.bolt.yml', Yaml::dump($config))
        ;
        $filesystem->expects($this->never())
            ->method('remove')
        ;

        $updater = new BootstrapYamlUpdater($io, $filesystem);

        $updater->save($config);

        $output = $io->getOutput();
        $this->assertContains(sprintf("Bolt has updated the paths in your .bolt.yml file for %s.\n", Version::VERSION), $output);
    }

    public function testSaveCustomPathsError()
    {
        $config = [
            'paths' => [
                'app' => '%site%/app2',
            ],
        ];

        $io = new BufferIO();
        $filesystem = $this->getMockBuilder(Filesystem::class)
            ->setMethods(['dumpFile', 'remove'])
            ->getMock()
        ;
        $filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('.bolt.yml', Yaml::dump($config))
            ->willThrowException(new IOException(''))
        ;
        $filesystem->expects($this->never())
            ->method('remove')
        ;

        $updater = new BootstrapYamlUpdater($io, $filesystem);

        $updater->save($config);

        $output = $io->getOutput();
        $expected = <<<OUT
The paths in your .bolt.yml file can be simplified.
You should update your .bolt.yml file to this:

paths:
    app: '%site%/app2'

OUT;
        $this->assertContains($expected, $output);
    }

    public function testSaveEmptyPathsAndNoOtherOptions()
    {
        $io = new BufferIO();
        $filesystem = $this->getMockBuilder(Filesystem::class)
            ->setMethods(['dumpFile', 'remove'])
            ->getMock()
        ;
        $filesystem->expects($this->never())
            ->method('dumpFile')
        ;
        $filesystem->expects($this->once())
            ->method('remove')
        ;

        $updater = new BootstrapYamlUpdater($io, $filesystem);

        $updater->save([
            'paths' => [],
        ]);

        $output = $io->getOutput();
        $this->assertContains('The paths in your .bolt.yml file match the defaults now.', $output);
        $this->assertContains("Since this file is optional we've deleted it for you.", $output);
    }

    public function testSaveEmptyPathsAndNoOtherOptionsError()
    {
        $io = new BufferIO();
        $filesystem = $this->getMockBuilder(Filesystem::class)
            ->setMethods(['dumpFile', 'remove'])
            ->getMock()
        ;
        $filesystem->expects($this->never())
            ->method('dumpFile')
        ;
        $filesystem->expects($this->once())
            ->method('remove')
            ->willThrowException(new IOException(''))
        ;

        $updater = new BootstrapYamlUpdater($io, $filesystem);

        $updater->save([
            'paths' => [],
        ]);

        $output = $io->getOutput();
        $this->assertContains('The paths in your .bolt.yml file match the defaults now.', $output);
        $this->assertContains('It is safe to delete it.', $output);
    }

    public function testSaveEmptyPathsButOtherOptions()
    {
        $io = new BufferIO();
        $filesystem = $this->getMockBuilder(Filesystem::class)
            ->setMethods(['dumpFile', 'remove'])
            ->getMock()
        ;
        $filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('.bolt.yml', "application: My\\App\n")
        ;
        $filesystem->expects($this->never())
            ->method('remove')
        ;

        $updater = new BootstrapYamlUpdater($io, $filesystem);

        $updater->save([
            'paths'       => [],
            'application' => 'My\App',
        ]);

        $output = $io->getOutput();
        $this->assertContains('The paths in your .bolt.yml file match the defaults now.', $output);
        $this->assertContains("We've removed them from the file for you.", $output);
    }

    public function testSaveEmptyPathsButOtherOptionsError()
    {
        $io = new BufferIO();
        $filesystem = $this->getMockBuilder(Filesystem::class)
            ->setMethods(['dumpFile', 'remove'])
            ->getMock()
        ;
        $filesystem->expects($this->once())
            ->method('dumpFile')
            ->with('.bolt.yml', "application: My\\App\n")
            ->willThrowException(new IOException(''))
        ;
        $filesystem->expects($this->never())
            ->method('remove')
        ;

        $updater = new BootstrapYamlUpdater($io, $filesystem);

        $updater->save([
            'paths'       => [],
            'application' => 'My\App',
        ]);

        $output = $io->getOutput();
        $this->assertContains('The paths in your .bolt.yml file match the defaults now.', $output);
        $this->assertContains('It is safe and encouraged to remove them.', $output);
    }
}
