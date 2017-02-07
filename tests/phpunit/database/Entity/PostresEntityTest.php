<?php

namespace Bolt\Tests\Database\Entity;

/**
 * PostgreSQL entity test.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class PostresEntityTest extends AbstractEntityTest
{
    /**
     * {@inheritdoc}
     */
    protected function getBrand()
    {
        return 'PoasgreSQL';
    }

    /**
     * {@inheritdoc}
     */
    protected function getPlatformName()
    {
        return 'postgresql';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDriver()
    {
        return 'pdo_pgsql';
    }
}
