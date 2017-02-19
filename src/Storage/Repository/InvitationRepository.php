<?php
namespace Bolt\Storage\Repository;

use Bolt\Storage\Entity;
use Bolt\Storage\Repository;
use Carbon\Carbon;
use Doctrine\DBAL\Query\QueryBuilder;

/**
 * A Repository class that handles storage operations for the invitation table.
 */
class InvitationRepository extends Repository
{
    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        $this->expire();

        return $this->findBy([]);
    }

    /**
     * Get a token entity.
     *
     * @param string $token
     *
     * @return Entity\Invitation|false
     */
    public function getInviteByToken($token)
    {
        $this->expire();
        $query = $this->getInviteByTokenQuery($token);

        return $this->findOneWith($query);
    }

    /**
     * @param string $token
     *
     * @return QueryBuilder
     */
    public function getInviteByTokenQuery($token)
    {
        $qb = $this->createQueryBuilder();
        $qb
            ->where('token = :token')
            ->andWhere('expiration > :now')
            ->setParameter('token', $token)
            ->setParameter('now', Carbon::now())
        ;

        return $qb;
    }

    /**
     * Remove expired tokens.
     *
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function expire()
    {
        $query = $this->getExpireQuery();

        return $query->execute();
    }

    /**
     * @return QueryBuilder
     */
    public function getExpireQuery()
    {
        $qb = $this->createQueryBuilder();
        $qb
            ->delete($this->getTableName())
            ->where('expiration < :now')
            ->setParameter('now', Carbon::now())
        ;

        return $qb;
    }
}
