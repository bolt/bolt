<?php

namespace Bolt\Tests\Database\Entity;

/**
 * Sqlite entity test.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SqliteEntityTest extends AbstractEntityTest
{
    /**
     * {@inheritdoc}
     */
    protected function getBrand()
    {
        return 'Sqlite';
    }

    /**
     * {@inheritdoc}
     */
    protected function getPlatformName()
    {
        return 'sqlite';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDriver()
    {
        return 'pdo_sqlite';
    }
}
