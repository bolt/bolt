<?php

namespace Bolt\Tests\Field;

use Bolt\Storage\Field\Base;
use Bolt\Storage\Field\Manager;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Field/Manager.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ManagerTest extends BoltUnitTest
{
    /**
     * @group legacy
     */
    public function testLegacyManagerDefaultsSetup()
    {
        $manager = new Manager();
        $this->assertTrue($manager->has('text'));
        $this->assertTrue($manager->has('integer'));
        $this->assertTrue($manager->has('float'));
        $this->assertTrue($manager->has('geolocation'));
        $this->assertTrue($manager->has('imagelist'));
        $this->assertTrue($manager->has('image'));
        $this->assertTrue($manager->has('file'));
        $this->assertTrue($manager->has('filelist'));
        $this->assertTrue($manager->has('video'));
        $this->assertTrue($manager->has('html'));
        $this->assertTrue($manager->has('textarea'));
        $this->assertTrue($manager->has('datetime'));
        $this->assertTrue($manager->has('date'));
        $this->assertTrue($manager->has('select'));
        $this->assertTrue($manager->has('templateselect'));
        $this->assertTrue($manager->has('markdown'));
        $this->assertTrue($manager->has('checkbox'));
        $this->assertTrue($manager->has('slug'));
    }

    /**
     * @group legacy
     */
    public function testLegacyAddingFetchingFields()
    {
        /** @var Base $field */
        $field = $this->getMockBuilder(Base::class)
            ->setMethods(null)
            ->setConstructorArgs(['test', 'test.twig'])
            ->getMock()
        ;
        $manager = new Manager();
        $manager->addField($field);
        $this->assertTrue($manager->has('test'));
        $this->assertEquals($field, $manager->getField('test'));

        $this->assertFalse($manager->getField('nonexistent'));
        $this->assertGreaterThan(5, $manager->fields());
    }
}
