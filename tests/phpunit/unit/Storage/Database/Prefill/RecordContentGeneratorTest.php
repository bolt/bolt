<?php

namespace Bolt\Tests\Storage\Database\Prefill;

use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Bolt\Storage\Database\Prefill;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Mocks\ImageApiMock;
use Bolt\Tests\Mocks\LoripsumMock;

class RecordContentGeneratorTest extends BoltUnitTest
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Bolt\Storage\Database\Prefill\RecordContentGenerator::generate requires a value greater than 1.
     */
    public function testZeroCount()
    {
        $app = $this->getApp();
        $factory = $app['prefill.generator_factory'];
        $generator = $factory('pages');
        $generator->generate(0);
    }

    public function testBuild()
    {
        $app = $this->getApp();
        $this->resetDb();
        $this->addDefaultUser($app);
        $em = $app['storage'];

        $expected = [
            'blocks',
            'entries',
            'pages',
            'showcases',
        ];

        foreach ($expected as $expect) {
            $generator = $this->getContentGenerator($expect);

            $this->assertSame($expect, $generator->getContentTypeName());
            $this->assertSame(0, $em->getRepository($expect)->count());

            $generator->generate(3);
            $this->assertSame(3, $em->getRepository($expect)->count());

            $generator->generate(6);
            $this->assertSame(9, $em->getRepository($expect)->count());
        }
    }

    public function getContentGenerator($contentTypeName)
    {
        $app = $this->getApp();
        $path = $app['path_resolver']->resolve('%bolt%/files');
        $app['filesystem']->mountFilesystem('files', new Filesystem(new Local($path)));

        $generator = new Prefill\RecordContentGenerator(
            $contentTypeName,
            new LoripsumMock(),
            new ImageApiMock(),
            $app['storage']->getRepository($contentTypeName),
            $app['filesystem']->getFilesystem('files'),
            $app['config']->get('taxonomy'),
            $app['prefill.default_field_values']
        );

        return $generator;
    }
}
