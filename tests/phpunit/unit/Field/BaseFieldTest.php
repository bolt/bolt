<?php

namespace Bolt\Tests\Field;

use Bolt\Storage\Field\Base;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Field/Base.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class BaseFieldTest extends BoltUnitTest
{
    public function testFieldSetup()
    {
        /** @var Base $field */
        $field = $this->getMockBuilder(Base::class)
            ->setMethods(null)
            ->setConstructorArgs(['test', 'test.twig'])
            ->getMock()
        ;
        $this->assertEquals('test', $field->getName());
        $this->assertEquals('test.twig', $field->getTemplate());

        // This tests the default returns for base
        $this->assertEquals('Doctrine\\DBAL\\Types\\TextType', get_class($field->getStorageType()));
        $this->assertEquals([], $field->getStorageOptions());
    }
}
