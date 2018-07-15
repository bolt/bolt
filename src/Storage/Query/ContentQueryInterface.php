<?php

namespace Bolt\Storage\Query;

interface ContentQueryInterface extends QueryInterface
{
    /**
     * Returns the content type this query is executing on.
     *
     * @return string
     */
    public function getContentType();

    /**
     * Returns the value of a parameter by key name.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getParameter($key);

    /**
     * Sets the value of a parameter by key name.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return mixed
     */
    public function setParameter($key, $value);
}
