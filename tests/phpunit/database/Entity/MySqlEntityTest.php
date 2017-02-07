<?php

namespace Bolt\Tests\Database\Entity;

/**
 * MySQL entity test.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class MySqlEntityTest extends AbstractEntityTest
{
    /**
     * {@inheritdoc}
     */
    protected function getBrand()
    {
        return 'MySQL';
    }

    /**
     * {@inheritdoc}
     */
    protected function getPlatformName()
    {
        return 'mysql';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDriver()
    {
        return 'pdo_mysql';
    }
}
