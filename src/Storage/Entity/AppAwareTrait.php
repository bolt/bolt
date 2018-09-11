<?php

namespace Bolt\Storage\Entity;

use Bolt\Legacy\AppSingleton;
use Silex\Application;

trait AppAwareTrait
{
    /**
     * @var Application
     */
    protected $app;

    public function __get($name)
    {
        if ($name === 'app') {
            $this->getApp();
        }

        return is_callable(['parent', '__get']) ? parent::__get($name) : null;
    }

    /**
     * @return Application
     */
    protected function getApp()
    {
        if (!$this->app) {
            $this->setApp();
        }

        return $this->app;
    }

    /**
     * @param Application|null $app
     */
    protected function setApp(Application $app = null)
    {
        $this->app = $app ?: AppSingleton::get();
    }
}
