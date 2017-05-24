<?php

namespace Bolt\Tests\Session;

use Bolt\Session\IniBag;
use PHPUnit_Framework_TestCase as TestCase;

/**
 * IniBag Tests.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class IniBagTest extends TestCase
{
    public function testAllGlobal()
    {
        $ini = new IniBag();

        $expectedSubset = [
            'allow_url_fopen'   => ini_get('allow_url_fopen'),
            'allow_url_include' => ini_get('allow_url_include'),
        ];
        $actual = $ini->all();

        $this->assertArraySubset($expectedSubset, $actual);
    }

    public function testAllGlobalAndPrefix()
    {
        $ini = new IniBag(null, 'allow_url_');

        $expectedSubset = [
            'fopen'   => ini_get('allow_url_fopen'),
            'include' => ini_get('allow_url_include'),
        ];
        $actual = $ini->all();

        $this->assertArraySubset($expectedSubset, $actual);
    }

    public function testAllExtension()
    {
        $ini = new IniBag('session');

        $expectedSubset = [
            'name'         => ini_get('session.name'),
            'save_handler' => ini_get('session.save_handler'),
        ];
        $actual = $ini->all();

        $this->assertArraySubset($expectedSubset, $actual);
    }

    public function testAllExtensionAndPrefix()
    {
        $ini = new IniBag('date', 'default_');

        $expectedSubset = [
            'latitude'  => ini_get('date.default_latitude'),
            'longitude' => ini_get('date.default_longitude'),
        ];
        $actual = $ini->all();

        $this->assertArraySubset($expectedSubset, $actual);
    }

    public function testAllEditable()
    {
        $ini = new IniBag();

        $actual = $ini->allEditable();

        $this->assertArrayHasKey('date.timezone', $actual);
        $this->assertArrayNotHasKey('allow_url_fopen', $actual);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Extension "foo" does not exist.
     */
    public function testInvalidExtension()
    {
        new IniBag('foo');
    }

    public function testIterator()
    {
        $ini = new IniBag();

        $expected = ini_get_all(null, false);

        $actual = iterator_to_array($ini);

        $this->assertEquals($expected, $actual);
    }

    public function testCount()
    {
        $ini = new IniBag();

        $expected = count(ini_get_all());

        $this->assertCount($expected, $ini);
    }

    public function testKeys()
    {
        $ini = new IniBag('session');

        $keys = $ini->keys();

        $this->assertContains('name', $keys, '', false, true, true);
        $this->assertContains('save_handler', $keys, '', false, true, true);
    }

    public function testGet()
    {
        $ini = new IniBag('session');

        $expected = ini_get('session.name');

        $actual = $ini->get('name');

        $this->assertSame($expected, $actual);
    }

    public function testHas()
    {
        $ini = new IniBag('session');

        $this->assertTrue($ini->has('name'));
        $this->assertFalse($ini->has('kajhsdfakjsdfh'));
    }

    public function testSet()
    {
        $ini = new IniBag('session');

        $backup = ini_get('session.name');

        try {
            $ini->set('name', 'foo');
            $this->assertSame('foo', ini_get('session.name'));
        } finally {
            ini_set('session.name', $backup);
        }
    }

    public function testSetBoolean()
    {
        $ini = new IniBag();

        $backup = ini_get('assert.bail');

        try {
            $ini->set('assert.bail', false);
            $this->assertSame('0', ini_get('assert.bail'));

            $ini->set('assert.bail', true);
            $this->assertSame('1', ini_get('assert.bail'));
        } finally {
            ini_set('assert.bail', $backup);
        }
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to change ini option "precision" to -2.
     */
    public function testSetInvalidValue()
    {
        $ini = new IniBag();

        $ini->set('precision', -2);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage ini values must be scalar or null. Got: array
     */
    public function testSetInvalidType()
    {
        $ini = new IniBag();

        $ini->set('precision', []);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage The ini option "session.derp" does not exist. New ini options cannot be added.
     */
    public function testSetNewKey()
    {
        $ini = new IniBag('session');

        $ini->set('derp', 'foo');
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Unable to change ini option "allow_url_fopen", because it is not editable at runtime.
     */
    public function testSetUnauthorized()
    {
        $ini = new IniBag();

        $ini->set('allow_url_fopen', true);
    }

    public function testAdd()
    {
        $ini = new IniBag('session');

        $backup = ini_get('session.name');

        try {
            $ini->add(['name' => 'foo']);
            $this->assertSame('foo', ini_get('session.name'));
        } finally {
            ini_set('session.name', $backup);
        }
    }

    public function testReplace()
    {
        $ini = new IniBag('session');

        $backup = ini_get('session.name');

        try {
            $ini->replace(['name' => 'foo']);
            $this->assertSame('foo', ini_get('session.name'));

            // Assert keys can't be removed
            $this->assertArrayHasKey('session.save_handler', ini_get_all());
        } finally {
            ini_set('session.name', $backup);
        }
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testRemove()
    {
        $ini = new IniBag();

        $ini->remove('why though');
    }
}
