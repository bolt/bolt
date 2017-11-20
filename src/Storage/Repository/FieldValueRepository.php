<?php

namespace Bolt\Storage\Repository;

use Bolt\Storage\Entity\Entity;
use Bolt\Storage\Repository;

class FieldValueRepository extends Repository
{
    /**
     * @param int    $id
     * @param string $contentType
     * @param string $field
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function queryExistingFields($id, $contentType, $field)
    {
        $query = $this->createQueryBuilder()
            ->select('grouping, id', 'name')
            ->where('content_id = :id')
            ->andWhere('contenttype = :contenttype')
            ->andWhere('name = :name')
            ->orderBy('grouping', 'ASC')
            ->setParameters([
                'id'          => $id,
                'contenttype' => $contentType,
                'name'        => $field,
            ]);

        return $query;
    }

    /**
     * @param int    $id
     * @param string $contentType
     * @param string $field
     *
     * @return Entity[]
     */
    public function getExistingFields($id, $contentType, $field)
    {
        $query = $this->queryExistingFields($id, $contentType, $field);
        $results = $query->execute()->fetchAll();

        $fields = [];

        if (!$results) {
            return $fields;
        }

        foreach ($results as $result) {
            $fields[$result['grouping']][] = $result['id'];
        }

        return $fields;
    }
}
