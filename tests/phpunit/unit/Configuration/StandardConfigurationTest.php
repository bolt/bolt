<?php

namespace Bolt\Tests\Configuration;

use Bolt\Application;
use Bolt\Configuration\Standard;
use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Debug\DebugClassLoader;

/**
 * Class to test correct operation and locations of composer configuration.
 *
 * @group legacy
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class StandardConfigurationTest extends TestCase
{
    public function testInitWithClassloader()
    {
        $loader = $this->getAutoLoader();
        $config = new Standard($loader);
        $app = new Application(['resources' => $config]);
        $this->assertEquals('/app/', $config->getUrl('app'));
    }

    private function getAutoLoader()
    {
        foreach (spl_autoload_functions() as $autoloader) {
            if (is_array($autoloader) && $autoloader[0] instanceof DebugClassLoader) {
                $autoloader = $autoloader[0]->getClassLoader();
            }
            if (!is_array($autoloader) || !$autoloader[0] instanceof ClassLoader) {
                continue;
            }
            if ($file = $autoloader[0]->findFile(ClassLoader::class)) {
                return $autoloader[0];
            }
        }

        throw new \RuntimeException('LOL WUT?');
    }
}
