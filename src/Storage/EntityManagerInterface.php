<?php

namespace Bolt\Storage;

use Bolt\Exception\InvalidRepositoryException;

/**
 * EntityManager interface.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface EntityManagerInterface
{
    /**
     * Gets the repository for a class.
     *
     * @param string $className
     *
     * @throws InvalidRepositoryException
     *
     * @return Repository
     */
    public function getRepository($className);
}
