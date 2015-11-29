<?php

namespace Bolt\Extension;

use Silex\Application;
use Twig_ExtensionInterface;

/**
 * This will replace current BaseExtension.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
abstract class SimpleExtension extends AbstractExtension implements Twig_ExtensionInterface
{
    use AssetTrait;
    use MenuTrait;
    use TwigTrait;

    abstract public function initialize(Application $app);
}
