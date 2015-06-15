<?php
namespace Bolt\Storage;

use Bolt\Storage\Entity\Content;

/**
 * A Repository class that handles dynamically created content tables.
 */
class ContentRepository extends Repository
{
    public $namingStrategy;

    /**
     * @inheritdoc
     */
    public function createQueryBuilder($alias = null, $indexBy = null)
    {
        if (null === $alias) {
            $alias = $this->getAlias();
        }
        return $this->em->createQueryBuilder()
            ->select($alias.".*")
            ->from($this->getTableName(), $alias);
    }

    /**
     * Creates a new Content entity and passes the supplied data to the constructor.
     *
     * @param array $params
     *
     * @return Content
     */
    public function create($params = null)
    {
        return new Content($params);
    }
}
