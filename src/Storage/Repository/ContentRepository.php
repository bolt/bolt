<?php

namespace Bolt\Storage\Repository;

use Bolt\Storage\Repository;

/**
 * A Repository class that handles dynamically created content tables.
 */
class ContentRepository extends Repository
{
    public function createQueryBuilder($alias = 'content')
    {
        return parent::createQueryBuilder($alias);
    }
}
