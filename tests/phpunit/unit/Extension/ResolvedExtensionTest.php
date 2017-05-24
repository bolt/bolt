<?php

namespace Bolt\Tests\Extension;

use Bolt\Composer\EventListener\PackageDescriptor;
use Bolt\Extension\ExtensionInterface;
use Bolt\Extension\ResolvedExtension;
use Bolt\Filesystem\Handler\Directory;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test Bolt\Extension\ResolvedExtension.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ResolvedExtensionTest extends BoltUnitTest
{
    /** @var ExtensionInterface */
    protected $extension;
    /** @var ResolvedExtension */
    protected $resolvedExtension;

    public function setUp()
    {
        parent::setUp();
        $this->extension = new Mock\NormalExtension();
        $this->extension->setBaseDirectory(new Directory());
        $this->resolvedExtension = new ResolvedExtension($this->extension);
    }

    /**
     * @ expectedException
     */
    public function testGetInnerExtension()
    {
        $this->assertInstanceOf(ExtensionInterface::class, $this->resolvedExtension->getInnerExtension());
    }

    public function testGetId()
    {
        $this->assertSame($this->extension->getId(), $this->resolvedExtension->getId());
    }

    public function testGetName()
    {
        $this->assertSame($this->extension->getName(), $this->resolvedExtension->getName());
    }

    public function testGetClassName()
    {
        $this->assertSame(get_class($this->extension), $this->resolvedExtension->getClassName());
    }

    public function testGetVendor()
    {
        $this->assertSame($this->extension->getVendor(), $this->resolvedExtension->getVendor());
    }

    public function testGetDisplayName()
    {
        $this->assertSame($this->extension->getDisplayName(), $this->resolvedExtension->getDisplayName());
    }

    public function testGetBaseDirectory()
    {
        $this->assertSame(
            $this->extension->getBaseDirectory(),
            $this->resolvedExtension->getBaseDirectory()
        );
    }

    public function testEnabled()
    {
        $this->resolvedExtension->setEnabled(false);
        $this->assertFalse($this->resolvedExtension->isEnabled());

        $this->resolvedExtension->setEnabled(true);
        $this->assertTrue($this->resolvedExtension->isEnabled());
    }

    public function getDescriptor()
    {
        return new PackageDescriptor(
            'bolt/normal',
            Mock\NormalExtension::class,
            'extensions/vendor/bolt/normal',
            'extensions/vendor/bolt/normal/web',
            '^3.0',
            true
        );
    }

    public function testDescriptor()
    {
        $descriptor = $this->getDescriptor();
        $this->resolvedExtension->setDescriptor($descriptor);
        $this->assertInstanceOf(PackageDescriptor::class, $this->resolvedExtension->getDescriptor());
        $this->assertSame($descriptor, $this->resolvedExtension->getDescriptor());
    }

    public function testManagedOrBundled()
    {
        $this->resolvedExtension->setDescriptor();
        $this->assertFalse($this->resolvedExtension->isManaged());
        $this->assertTrue($this->resolvedExtension->isBundled());

        $descriptor = $this->getDescriptor();
        $this->resolvedExtension->setDescriptor($descriptor);
        $this->assertTrue($this->resolvedExtension->isManaged());
        $this->assertFalse($this->resolvedExtension->isBundled());
    }

    public function testIsValidNullDescriptor()
    {
        $this->resolvedExtension->setDescriptor();
        $this->assertTrue($this->resolvedExtension->isValid());
    }

    public function testIsValidWithDescriptor()
    {
        $descriptor = $this->getDescriptor();
        $this->resolvedExtension->setDescriptor($descriptor);
        $this->assertTrue($this->resolvedExtension->isValid());
    }

    public function testIsValidWithDisabledDescriptor()
    {
        $descriptor = new PackageDescriptor(null, null, null, null, null, false);
        $this->resolvedExtension->setDescriptor($descriptor);
        $this->assertFalse($this->resolvedExtension->isValid());
    }
}
