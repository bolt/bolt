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
        $field = $this->getMock('Bolt\Storage\Field\Base', null, ['test', 'test.twig']);
        $this->assertEquals('test', $field->getName());
        $this->assertEquals('test.twig', $field->getTemplate());

        // This tests the default returns for base
        $this->assertEquals('Text', (string) $field->getStorageType());
        $this->assertEquals([], $field->getStorageOptions());
    }
}
