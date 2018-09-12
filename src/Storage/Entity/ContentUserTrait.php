<?php

namespace Bolt\Storage\Entity;

trait ContentUserTrait
{
    protected function getUser()
    {
        return $this->app['users']->getUser($this->getOwnerid());
    }
}
