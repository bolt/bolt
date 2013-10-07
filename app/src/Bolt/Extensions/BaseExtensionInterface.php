<?php

namespace Bolt\Extensions;

use Bolt\Application;

interface BaseExtensionInterface
{
    public function __construct(Application $app);
    public function initialize();
    public function getInfo();
    public function getConfig();
}
