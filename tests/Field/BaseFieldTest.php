<?php
namespace Bolt\Tests\Field;

use Bolt\Field\Base;
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
        $field = $this->getMock('Bolt\Field\Base', null, array('test', 'test.twig'));
        $this->assertEquals('test', $field->getName());
        $this->assertEquals('test.twig', $field->getTemplate());

        // This tests the default returns for base
        $this->assertEquals('text', $field->getStorageType());
        $this->assertEquals(array(), $field->getStorageOptions());
    }
}
