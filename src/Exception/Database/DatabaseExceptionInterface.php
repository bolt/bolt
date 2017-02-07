<?php

namespace Bolt\Exception\Database;

interface DatabaseExceptionInterface
{
    /**
     * @return string
     */
    public function getDriver();

    /**
     * Returns the driver's platform (human name).
     *
     * @return string
     */
    public function getPlatform();
}
