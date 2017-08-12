<?php

namespace Bolt\Tests\Composer;

use Bolt\Common\Json;
use Bolt\Composer\Package;
use Bolt\Composer\PackageCollection;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Bolt\Composer\PackageCollection
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class PackageCollectionTest extends TestCase
{
    public function testAddGet()
    {
        $input = ['name' => 'bolt/test', 'type' => 'bolt/extension'];
        $package = Package::createFromComposerJson($input);

        $collection = new PackageCollection();
        $collection->add($package);

        $result = $collection->get($input['name']);

        $this->assertSame($package, $result);
        $this->assertNull($collection->get('koala'));
    }

    public function testAddImutable()
    {
        $input = ['name' => 'bolt/test', 'type' => 'bolt/extension'];
        $package1 = Package::createFromComposerJson($input);
        $package2 = Package::createFromComposerJson($input);

        $collection = new PackageCollection();
        $collection->add($package1);
        $collection->add($package2);

        $result = $collection->get($input['name']);

        $this->assertSame($package1, $result);
        $this->assertNotSame($package2, $result);
    }

    public function testJsonSerialize()
    {
        $input = ['name' => 'bolt/test', 'type' => 'bolt/extension'];
        $expected = '{"bolt/test":{"status":null,"type":"bolt/extension","name":"bolt/test","title":null,"description":"","version":"local","authors":[],"keywords":[],"readmeLink":null,"configLink":null,"repositoryLink":null,"constraint":"0.0.0","valid":false,"enabled":false}}';
        $package = Package::createFromComposerJson($input);

        $collection = new PackageCollection();
        $collection->add($package);

        $this->assertSame($expected, Json::dump($collection));
    }
}
