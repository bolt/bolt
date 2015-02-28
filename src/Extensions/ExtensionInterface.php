<?php

namespace Bolt\Extensions;

use Bolt\Application;

interface ExtensionInterface
{
    public function __construct(Application $app);
    public function initialize();
    public function getConfig();
    public function getName();
    public function getExtensionConfig();
    public function getSnippets();
}
