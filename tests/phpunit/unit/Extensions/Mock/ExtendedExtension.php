<?php
namespace Bolt\Tests\Extensions\Mock;

use Bolt\BaseExtension;

/**
 * Simple mock of extended extension. Solves the problem that PHPUnit cannot mock
 * a class in a namespace other than the global one
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ExtendedExtension extends BaseExtension
{
    public function initialize()
    {
    }
}
