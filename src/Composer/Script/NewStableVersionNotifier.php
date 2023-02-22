<?php

namespace Bolt\Composer\Script;

use Bolt\Nut\Output\NutStyleInterface;
use Bolt\Nut\Style\NutStyle;
use Composer\Script\Event;

class NewStableVersionNotifier
{
    /**
     * Go!
     */
    public static function run()
    {
        $message = "\nYou are using Bolt v3. There is a new major Bolt release available.";
        $message .= "\nTo learn more go to https://docs.boltcms.io/\n";

        echo ($message);
    }
}
