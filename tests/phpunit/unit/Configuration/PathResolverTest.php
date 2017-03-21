<?php

namespace Bolt\Tests\Configuration;

use Bolt\Configuration\PathResolver;
use PHPUnit\Framework\TestCase;

class PathResolverTest extends TestCase
{
    public function pathResolutionProvider()
    {
        return [
            'absolute path'                => ['/tmp',            '/tmp'],
            'relative path'                => ['tmp',             '/root/tmp'],
            'non-canonical path'           => ['tmp/foo/../derp', '/root/tmp/derp'],
            'defined path/alias'           => ['web',             '/root/public'],
            'path with variable'           => ['%web%/foo',       '/root/public/foo'],
            'path with variable recursive' => ['%files%/foo.jpg', '/root/public/files/foo.jpg'],
            'empty path'                   => ['',                '/root'],
            'empty alias at start'         => ['%empty%/foo',     '/root/foo'],
            'empty alias in middle'        => ['/derp/%empty%/foo', '/derp/foo'],
        ];
    }

    /**
     * @dataProvider pathResolutionProvider
     *
     * @param string $path
     * @param string $expected
     */
    public function testResolve($path, $expected)
    {
        $resolver = new PathResolver('/root/', [
            'web'   => 'public',
            'empty' => '',
            'files' => '%web%/files',
        ]);

        $this->assertSame($expected, $resolver->resolve($path));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Failed to resolve path. Alias %nope% is not defined.
     */
    public function testUndefinedAliasFails()
    {
        $resolver = new PathResolver('/root/');
        $resolver->resolve('%nope%/foo/bar');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRelativeRootFails()
    {
        new PathResolver('foo');
    }

    public function testConstructor()
    {
        $resolver = new PathResolver('/foo/bar/../', [
            'bar' => 'herp/../derp',
        ]);

        $this->assertSame('/foo', $resolver->resolve('root'), 'Root path was not applied');
        $this->assertSame('/foo/derp', $resolver->resolve('bar'), 'Initial paths were not applied');
    }

    public function testRaw()
    {
        $resolver = new PathResolver('/', [
            'bar' => 'foo',
        ]);

        $this->assertSame('foo', $resolver->raw('bar'), 'Raw() should return unresolved path');
        $this->assertNull($resolver->raw('derp'), 'Raw() should return null for undefined path');
    }

    /**
     * @depends testResolve
     */
    public function testResolveAll()
    {
        $resolver = new PathResolver('/root/', [
            'web'   => 'public',
            'files' => '%web%/files',
        ]);

        $this->assertEquals(
            [
                'web'   => '/root/public',
                'files' => '/root/public/files',
                'root'  => '/root',
            ],
            $resolver->resolveAll()
        );
    }
}
