<?php
namespace Bolt\Storage\Repository;

use Bolt\Storage\Repository;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * A Repository class that handles storage operations for the users table.
 */
class UsersRepository extends Repository
{
    /**
     * Delete a user.
     *
     * @param string|integer $userId Either the user's ID, username, or email
     * address.
     *
     * @return integer
     */
    public function deleteUser($userId)
    {
        $query = $this->deleteUserQuery($userId);

        return $query->execute();
    }

    /**
     * Get the user deletion query.
     *
     * @param string|integer $userId
     *
     * @return QueryBuilder
     */
    public function deleteUserQuery($userId)
    {
        $qb = $this->createQueryBuilder();
        $qb->delete($this->getTableName())
            ->where('id = :userId')
            ->orWhere('username = :userId')
            ->orWhere('email = :userId')
            ->setParameter('userId', $userId);

        return $qb;
    }

    /**
     * Get a user.
     *
     * @param string|integer $userId Either the user's ID, username, or email
     * address.
     *
     * @return integer
     */
    public function getUser($userId)
    {
        $query = $this->getUserQuery($userId);

        return $this->findOneWith($query);
    }

    /**
     * Get the user fetch query.
     *
     * @param string|integer $userId
     *
     * @return QueryBuilder
     */
    public function getUserQuery($userId)
    {
        $qb = $this->createQueryBuilder();
        $qb->select('*')
            ->where('id = :userId')
            ->orWhere('username = :userId')
            ->orWhere('email = :userId')
            ->setParameter('userId', $userId);

        return $qb;
    }

    /**
     * Creates a query builder instance namespaced to this repository.
     *
     * @return QueryBuilder
     */
    public function createQueryBuilder($alias = null)
    {
        return $this->em->createQueryBuilder()
            ->from($this->getTableName());
    }
}
