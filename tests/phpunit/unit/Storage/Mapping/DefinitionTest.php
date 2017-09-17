<?php

namespace Bolt\Tests\Storage\Mapping;

use Bolt\Storage\Mapping\Definition;
use Bolt\Storage\Mapping\MappingManager;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Mapping/MappingManager.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class DefinitionTest extends BoltUnitTest
{
    public function testDefinitionDefaults()
    {
        $app = $this->getApp();
        $manager = $app['mapping'];

        $field = $manager->load('textfield', ['type' => 'text']);
        $this->assertEquals('', $field->getClass());
        $this->assertEquals('', $field->getDefault());
        $this->assertEquals('', $field->getError());
        $this->assertEquals('', $field->getGroup());
        $this->assertEquals('', $field->getInfo());
        $this->assertEquals('', $field->getPattern());
        $this->assertEquals('', $field->getPlaceholder());
        $this->assertEquals('', $field->getPostfix());
        $this->assertEquals('', $field->getPrefix());
        $this->assertEquals(false, $field->getReadonly());
        $this->assertEquals(false, $field->getSeparator());
        $this->assertEquals('', $field->getTitle());
    }

    public function testDefinitionWithSettings()
    {
        $app = $this->getApp();
        $manager = $app['mapping'];

        $options = [
            'type' => 'text',
            'class' => 'testclass',
            'default' => 'testdefault',
            'error' => 'testerror',
            'group' => 'testgroup',
            'info' => 'testinfo',
            'pattern' => 'testpattern',
            'placeholder' => 'testplaceholder',
            'postfix' => 'testpostfix',
        ];

        $field = $manager->load('textfield', $options);
        $this->assertEquals('text', $field->getType());
        $this->assertEquals('testclass', $field->getClass());
        $this->assertEquals('testdefault', $field->getDefault());
        $this->assertEquals('testerror', $field->getError());
        $this->assertEquals('testgroup', $field->getGroup());
        $this->assertEquals('testinfo', $field->getInfo());
        $this->assertEquals('testpattern', $field->getPattern());
        $this->assertEquals('testplaceholder', $field->getPlaceholder());
        $this->assertEquals('testpostfix', $field->getPostfix());
    }

    public function testSlugDefinition()
    {
        $app = $this->getApp();
        $options = [
            'type' => 'slug',
            'uses' => 'title',
        ];
        $field = $app['mapping']->load('testslug', $options);
        $this->assertEquals(['title'], $field->getUses());
    }

    public function testFileDefinition()
    {
        $app = $this->getApp();
        $options = [
            'type' => 'file',
        ];
        $field = $app['mapping']->load('testfile', $options);
        $this->assertContains('pdf', $field->getExtensions());
        $this->assertContains('doc', $field->getExtensions());
        $this->assertContains('md', $field->getExtensions());
        $this->assertContains('jpg', $field->getExtensions());

    }

    public function testImageDefinition()
    {
        $app = $this->getApp();
        $options = [
            'type' => 'imagelist',
        ];
        $field = $app['mapping']->load('testimage', $options);
        $this->assertContains('jpg', $field->getExtensions());
        $this->assertContains('gif', $field->getExtensions());
        $this->assertContains('png', $field->getExtensions());
        $this->assertNotContains('doc', $field->getExtensions());
    }

    public function testRepeaterDefinition()
    {
        $app = $this->getApp();
        $options = [
            'type' => 'repeater',
            'fields' => [
                'atitle' => ['type' => 'text'],
                'aslug' => ['type' => 'slug'],
                'ateaser' => ['type' => 'textarea'],
                'afile' => ['type' => 'file'],
            ],
        ];
        $repeater = $app['mapping']->load('testrepeater', $options);

        // 4 defined, but slug is blacklisted so should be discarded
        $this->assertEquals(3, count($repeater->getFields()));
        $this->assertInstanceOf(Definition::class, $repeater->getField('atitle'));
        $this->assertInstanceOf(Definition::class, $repeater->getField('ateaser'));
        $this->assertInstanceOf(Definition\File::class, $repeater->getField('afile'));
    }
}
