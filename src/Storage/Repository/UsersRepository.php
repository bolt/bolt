<?php
namespace Bolt\Storage\Repository;

use Bolt\Storage\Entity;
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
     *                               address.
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
        $qb->delete($this->getTableName());

        if (is_numeric($userId)) {
            $qb->where('id = :userId');
        } else {
            $qb->where('username = :userId')->orWhere('email = :userId');
        }
        $qb->setParameter('userId', $userId);

        return $qb;
    }

    /**
     * Get a user.
     *
     * @param string|integer $userId Either the user's ID, username, or email
     *                               address.
     *
     * @return Entity\Users|false
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
        $qb->select('*');

        if (is_numeric($userId)) {
            $qb->where('id = :userId');
        } else {
            $qb->where('username = :userId')->orWhere('email = :userId');
        }
        $qb->setParameter('userId', $userId);

        return $qb;
    }

    /**
     * Check to see if there are users in the user table.
     *
     * @return integer
     */
    public function hasUsers()
    {
        $query = $this->hasUsersQuery();

        return $query->execute()->fetch();
    }

    /**
     * @return QueryBuilder
     */
    public function hasUsersQuery()
    {
        $qb = $this->createQueryBuilder();
        $qb->select('COUNT(id) as count');

        return $qb;
    }

    /**
     * Get user based on password reset notification.
     *
     * @return Entity\Users|false
     */
    public function getUserShadowAuth($shadowtoken)
    {
        $query = $this->getUserShadowAuthQuery($shadowtoken);

        return $this->findOneWith($query);
    }

    /**
     * @return QueryBuilder
     */
    public function getUserShadowAuthQuery($shadowtoken)
    {
        $qb = $this->createQueryBuilder();
        $qb->select('*')
            ->where('shadowtoken = :shadowtoken')
            ->andWhere('shadowvalidity > :shadowvalidity')
            ->setParameter('shadowtoken', $shadowtoken)
            ->setParameter('shadowvalidity', date('Y-m-d H:i:s'));

        return $qb;
    }

    /**
     * Saves a single object that already exists.
     *
     * @param object $entity     The entity to save.
     * @param array  $exclusions ignore updates to these fields
     *
     * @return boolean
     */
    public function update($entity, $exclusions = [])
    {
        $password = $entity->getPassword(); // PHP 5.4 compatibility
        if (empty($password) || $entity->getPassword() === '**dontchange**') {
            $result = parent::update($entity, ['password']);
        } else {
            $result = parent::update($entity);
        }

        return $result;
    }
}
