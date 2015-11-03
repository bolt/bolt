<?php
namespace Bolt\Storage\Repository;

use Bolt\Storage\Repository;

/**
 * A Repository class that handles storage operations for the Authtoken table.
 */
class AuthtokenRepository extends Repository
{
    /**
     * Fetches an existing token for the given user / ip
     *
     * @param string      $username
     * @param string      $ip
     * @param string|null $useragent
     *
     * @return \Bolt\Storage\Entity\Authtoken
     */
    public function getUserToken($username, $ip, $useragent = null)
    {
        $query = $this->getUserTokenQuery($username, $ip, $useragent);

        return $this->findOneWith($query);
    }

    public function getUserTokenQuery($username, $ip, $useragent)
    {
        $qb = $this->createQueryBuilder();
        $qb->select('*')
            ->where('username = :username')
            ->andWhere('ip = :ip')
            ->setParameter('username', $username)
            ->setParameter('ip', $ip);

        if ($useragent !== null) {
            $qb->andWhere('useragent = :useragent')
                ->setParameter('useragent', $useragent);
        }

        return $qb;
    }

    /**
     * Fetches an existing token for the given user / ip
     *
     * @param string      $token
     * @param string      $ip
     * @param string|null $useragent
     *
     * @return \Bolt\Storage\Entity\Authtoken
     */
    public function getToken($token, $ip, $useragent = null)
    {
        $query = $this->getTokenQuery($token, $ip, $useragent);

        return $this->findOneWith($query);
    }

    public function getTokenQuery($token, $ip, $useragent)
    {
        $qb = $this->createQueryBuilder();
        $qb->select('*')
            ->where('token = :token')
            ->andWhere('ip = :ip')
            ->setParameter('token', $token)
            ->setParameter('ip', $ip);

        if ($useragent !== null) {
            $qb->andWhere('useragent = :useragent')
                ->setParameter('useragent', $useragent);
        }

        return $qb;
    }

    /**
     * Deletes all tokens for the given user
     *
     * @param $username
     *
     * @return integer
     */
    public function deleteTokens($username)
    {
        $query = $this->deleteTokensQuery($username);

        return $query->execute();
    }

    public function deleteTokensQuery($username)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->delete($this->getTableName())
            ->where('username = :username')
            ->setParameter('username', $username);

        return $qb;
    }

    /**
     * Deletes all expired tokens
     *
     * @return integer
     */
    public function deleteExpiredTokens()
    {
        $query = $this->deleteExpiredTokensQuery();

        return $query->execute();
    }

    public function deleteExpiredTokensQuery()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->delete($this->getTableName())
            ->where('validity < :now')
            ->setParameter('now', date('Y-m-d H:i:s'));

        return $qb;
    }

    /**
     * Fetches all active sessions
     *
     * @return \Bolt\Storage\Entity\Authtoken[]
     */
    public function getActiveSessions()
    {
        $this->deleteExpiredTokens();
        $query = $this->getActiveSessionsQuery();

        return $this->findWith($query);
    }

    public function getActiveSessionsQuery()
    {
        $qb = $this->createQueryBuilder();
        $qb->select('*');

        return $qb;
    }

    /**
     * Creates a query builder instance namespaced to this repository
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function createQueryBuilder($alias = null)
    {
        return $this->em->createQueryBuilder()
            ->from($this->getTableName());
    }
}
