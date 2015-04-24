<?php
namespace Bolt\Controllers\Backend;

use Bolt\Controllers\Base;
use Silex\Application;

abstract class BackendBase extends Base
{
    public function connect(Application $app)
    {
        $c = parent::connect($app);
        $c->value('end', 'backend'); // For now
    }
}
