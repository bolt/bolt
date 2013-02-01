<?php

namespace Bolt\Tests;

use Bolt\Cache;

class CacheTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @var \Bolt\Cache
     */
    protected $object;
    protected $cachePath;

    protected $cacheFiles = array();

    public function setUp()
    {
        $this->cachePath = __DIR__ . '/../../cache';
        $this->object = new Cache($this->cachePath);
    }

    /**
     * Uses reflection to return a private or protected class method to be able to
     * test class internals.
     *
     * @param string $name
     *
     * @return \ReflectionMethod
     */
    public function getMethod($name)
    {
        $class = new \ReflectionClass('\\Bolt\\Cache');
        $method = $class->getMethod($name);
        $method->setAccessible(TRUE);
        return $method;
    }

    /*
     * Tests if unique file names are generated
     */
    public function testGetFileName()
    {
        $key = 'foo';
        $getFilenameMethod = $this->getMethod('getFilename');

        $correctlyHashed = $this->cachePath . "/c_" .
                substr(md5($key), 0, 18) . ".cache";

        $notCorrectlyHashed = $this->cachePath . "/c_" .
                substr(md5($key), 1, 19) .".cache";

        $result = $getFilenameMethod->invokeArgs($this->object, array($key));
        $this->assertEquals($correctlyHashed, $result);
        $this->assertNotEquals($notCorrectlyHashed, $result);
    }

    public function tearDown()
    {
        // remove used cached files
        foreach ($this->cacheFiles as $cacheFile) {
            unlink($cacheFile);
        }
        $this->object->clearCache();
    }

    public static function setProvider()
    {
        return array(
            array(
                'bar',
                'bar'
            ),
            array(
                array('foo' => 'bar', 'baz' => 'meh'),
                serialize(array('foo' => 'bar', 'baz' => 'meh'))
            ),
            array(
                new \Bolt\Tests\FooObject(),
                serialize(new \Bolt\Tests\FooObject())
            )
        );
    }

    /**
     * @dataProvider setProvider
     */
    public function testSet($value, $expected)
    {
        $key = 'foo';
        $this->object->set($key, $value);
        // We've just checked the get file name method, so we can use this now
        $getFilenameMethod = $this->getMethod('getFilename');
        $cacheFilePath = $getFilenameMethod->invokeArgs(
            $this->object, array($key)
        );
        $this->assertTrue(file_exists($cacheFilePath));
        $this->cacheFiles [] = $cacheFilePath;
        $this->assertEquals($expected, file_get_contents($cacheFilePath));
    }

}