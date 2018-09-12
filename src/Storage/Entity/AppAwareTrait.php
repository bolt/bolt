<?php

namespace Bolt\Storage\Entity;

use Bolt\Legacy\AppSingleton;
use Silex\Application;

trait AppAwareTrait
{
    /**
     * @var Application
     */
    protected $_app;

    /**
     * @return Application
     */
    protected function getApp()
    {
        if (!$this->_app) {
            $this->setApp();
        }

        return $this->_app;
    }

    /**
     * @param Application|null $app
     */
    protected function setApp(Application $app = null)
    {
        $this->_app = $app ?: AppSingleton::get();
    }
}
