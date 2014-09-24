<?php

namespace Bolt\Extensions;

use Bolt\Application;

interface IsExtension
{
    public function __construct(Application $app);
    public function initialize();
    public function getConfig();
    public function getName();
    public function getExtensionConfig();
}
