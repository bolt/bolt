<?php

declare(strict_types=1);

namespace Bolt\Storage\Entity;

use Bolt\Legacy\AppSingleton;
use Silex\Application;

trait AppAwareTrait
{
    protected $app;

    public function __get($name)
    {
        if ($name === 'app') {
            $this->getApp();
        }

        return is_callable(['parent', '__get']) ? parent::__get($name) : null;
    }

    protected function getApp()
    {
        if (!$this->app) {
            $this->setApp();
        }

        return $this->app;
    }

    protected function setApp(Application $app = null)
    {
        $this->app = $app ?: AppSingleton::get();
    }
}
