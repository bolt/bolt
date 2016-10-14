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
        $this->assertEquals('', $field->getPattern());
        $this->assertEquals('', $field->getPostfix());
        $this->assertEquals('', $field->getPrefix());
        $this->assertEquals(false, $field->getReadonly());
        $this->assertEquals(false, $field->getSeparator());
        $this->assertEquals('', $field->getTitle());
    }


}
