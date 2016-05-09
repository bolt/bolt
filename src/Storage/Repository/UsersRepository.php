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
    private $userEntities = [];

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
        // Forget remembered users.
        $this->userEntities = [];

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
        // Check if we've already retrieved this user.
        if (isset($this->userEntities[$userId])) {
            return $this->userEntities[$userId];
        }

        $query = $this->getUserQuery($userId);
        /** @var Entity\Users $userEntity */
        if ($userEntity = $this->findOneWith($query)) {
            $this->unsetSensitiveFields($userEntity);
        }

        // Remember the user
        $this->userEntities[$userId] = $userEntity;

        return $userEntity;
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
     * Get a user's authentication data.
     *
     * @param string|integer $userId
     *
     * @return Entity\Users|false
     */
    public function getUserAuthData($userId)
    {
        $query = $this->getUserAuthDataQuery($userId);

        return $this->findOneWith($query);
    }

    /**
     * Get the user fetch query.
     *
     * @param string|integer $userId
     *
     * @return QueryBuilder
     */
    public function getUserAuthDataQuery($userId)
    {
        $qb = $this->createQueryBuilder();
        $qb->select('id')
            ->addSelect('password')
            ->addSelect('shadowpassword')
            ->where('id = :userId')
            ->setParameter('userId', $userId);

        return $qb;
    }

    /**
     * Get all the system users.
     *
     * @return Entity\Users[]|false
     */
    public function getUsers()
    {
        $userEntities = $this->findAll();
        if ($userEntities) {
            foreach ($userEntities as $userEntity) {
                $this->unsetSensitiveFields($userEntity);
            }
        }

        return $userEntities;
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
     * {@inheritdoc}
     */
    public function save($entity, $silent = null)
    {
        $this->userEntities = [];

        return parent::save($entity, $silent);
    }

    /**
     * {@inheritdoc}
     */
    public function update($entity, $exclusions = [])
    {
        // Forget remembered users.
        $this->userEntities = [];

        if ($entity->getPassword() === null) {
            $result = parent::update($entity, ['password']);
        } else {
            $result = parent::update($entity);
        }

        return $result;
    }

    /**
     * Null sensitive data that doesn't need to be passed around.
     *
     * @param Entity\Users $entity
     */
    protected function unsetSensitiveFields(Entity\Users $entity)
    {
        $entity->setPassword(null);
        $entity->setShadowpassword(null);
        $entity->setShadowtoken(null);
        $entity->setShadowvalidity(null);
    }
}
