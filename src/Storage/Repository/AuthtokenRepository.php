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
     * @param $username
     * @param $ip
     * @param $useragent
     *
     * @return Bolt\Entity\Authtoken
     **/
    public function getUserToken($username, $ip, $useragent)
    {
        $query = $this->queryExistingToken($username, $ip, $useragent);
        return $this->findOneWith($query);
    }
    
    public function queryUserToken($username, $ip, $useragent)
    {
        $qb = $this->createQueryBuilder();
        $qb->select('*')
            ->where('username=:username')
            ->andWhere('ip=:ip')
            ->andWhere('useragent=:useragent')
            ->setParameter('username', $username)
            ->setParameter('ip', $ip)
            ->setParameter('useragent', $useragent);
        return $qb;
    }
    
    /**
     * Fetches an existing token for the given user / ip
     *
     * @param $token
     * @param $ip
     * @param $useragent
     *
     * @return Bolt\Entity\Authtoken
     **/
    public function getToken($token, $ip, $useragent)
    {
        $query = $this->queryToken($token, $ip, $useragent);
        return $this->findOneWith($query);
    }
    
    public function queryToken($token, $ip, $useragent)
    {
        $qb = $this->createQueryBuilder();
        $qb->select('*')
            ->where('token=:token')
            ->andWhere('ip=:ip')
            ->andWhere('useragent=:useragent')
            ->setParameter('token', $token)
            ->setParameter('ip', $ip)
            ->setParameter('useragent', $useragent);
        return $qb;
    }
    
    public function createQueryBuilder($alias = null)
    {
        return $this->em->createQueryBuilder()
            ->from($this->getTableName());
    }
}
