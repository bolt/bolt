<?php

namespace Bolt\Tests\Storage\Mapping;

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
        $manager = new MappingManager($app['mapping.definitions'], $app['mapping.default']);

        $field = $manager->load('textfield', ['type' => 'text']);
        $this->assertEquals('', $field->getClass());
        $this->assertEquals('', $field->getDefault());
        $this->assertEquals('', $field->getError());
        $this->assertEquals('ungrouped', $field->getGroup());
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
        $manager = new MappingManager($app['mapping.definitions'], $app['mapping.default']);

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


}
