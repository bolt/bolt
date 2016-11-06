<?php
/**
 * Created by PhpStorm.
 * User: ross
 * Date: 08/10/2015
 * Time: 15:29
 */

namespace Bolt\Storage\Repository;

use Bolt\Storage\Repository;

class FieldValueRepository extends Repository
{
    public function queryExistingFields($id, $contenttype, $field)
    {
        $query = $this->createQueryBuilder()
            ->select('grouping, id', 'name')
            ->where('content_id = :id')
            ->andWhere('contenttype = :contenttype')
            ->andWhere('name = :name')
            ->orderBy('grouping', 'ASC')
            ->setParameters([
                'id'          => $id,
                'contenttype' => $contenttype,
                'name'        => $field,
            ]);

        return $query;
    }

    public function getExistingFields($id, $contenttype, $field)
    {
        $query = $this->queryExistingFields($id, $contenttype, $field);
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
