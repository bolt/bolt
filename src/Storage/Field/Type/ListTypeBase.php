<?php

namespace Bolt\Storage\Field\Type;

use Bolt\Common\Json;
use Bolt\Storage\QuerySet;
use Doctrine\DBAL\Types\Type;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ListTypeBase extends FieldTypeBase
{
    /**
     * {@inheritdoc}
     */
    public function persist(QuerySet $queries, $entity)
    {
        $key = $this->mapping['fieldname'];
        $value = $entity->get($key);

        if ($value !== null && !empty($value)) {
            $value = Json::test($value) ? Json::parse($value) : $value;

            // Remove elements that are not important for storage.
            foreach ($value as &$v) {
                unset($v['id']);
                unset($v['order']);
                unset($v['progress']);
                unset($v['element']);
            }
        }
        $entity->set($key, $value);

        parent::persist($queries, $entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getStorageType()
    {
        return Type::getType('json');
    }
}
