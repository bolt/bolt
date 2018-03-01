<?php

namespace Bolt\Storage\Repository;

use Bolt\Storage\Repository;

/**
 * A Repository class that handles storage operations for the Authtoken table.
 */
class AuthtokenRepository extends Repository
{
    /**
     * Fetches an existing token for the given user / ip.
     *
     * @param string      $userId
     * @param string      $ip
     * @param string|null $userAgent
     *
     * @return \Bolt\Storage\Entity\Authtoken|false
     */
    public function getUserToken($userId, $ip, $userAgent = null)
    {
        $query = $this->getUserTokenQuery($userId, $ip, $userAgent);

        return $this->findOneWith($query);
    }

    public function getUserTokenQuery($userId, $ip, $userAgent)
    {
        $qb = $this->createQueryBuilder();
        $qb->select('*')
            ->where('user_id = :user_id')
            ->andWhere('ip = :ip')
            ->setParameter('user_id', $userId)
            ->setParameter('ip', $ip);

        if ($userAgent !== null) {
            $qb->andWhere('useragent = :useragent')
                ->setParameter('useragent', $userAgent);
        }

        return $qb;
    }

    /**
     * Fetches an existing token for the given user / ip.
     *
     * @param string      $token
     * @param string|null $ip
     * @param string|null $userAgent
     *
     * @return \Bolt\Storage\Entity\Authtoken|false
     */
    public function getToken($token, $ip = null, $userAgent = null)
    {
        $query = $this->getTokenQuery($token, $ip, $userAgent);

        return $this->findOneWith($query);
    }

    public function getTokenQuery($token, $ip, $userAgent)
    {
        $qb = $this->createQueryBuilder();
        $qb->select('*')
            ->where('token = :token')
            ->setParameter('token', $token);

        if ($ip !== null) {
            $qb->andWhere('ip = :ip')
                ->setParameter('ip', $ip);
        }

        if ($userAgent !== null) {
            $qb->andWhere('useragent = :useragent')
                ->setParameter('useragent', $userAgent);
        }

        return $qb;
    }

    /**
     * Deletes all tokens for the given user.
     *
     * @param int $userId
     *
     * @return int
     */
    public function deleteTokens($userId)
    {
        $query = $this->deleteTokensQuery($userId);

        return $query->execute();
    }

    public function deleteTokensQuery($userId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->delete($this->getTableName())
            ->where('user_id = :user_id')
            ->setParameter('user_id', $userId);

        return $qb;
    }

    /**
     * Deletes all expired tokens.
     *
     * @return int
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
     * Fetches all active sessions.
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
}
