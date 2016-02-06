<?php
namespace Bolt\Storage\Field\Type;

use Bolt\Storage\QuerySet;
use Doctrine\DBAL\Types\Type;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class SlugType extends FieldTypeBase
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'slug';
    }

    /**
     * {@inheritdoc}
     */
    public function persist(QuerySet $queries, $entity)
    {
        if ($entity->getSlug() === null) {
            // When no slug value is given, generate a pseudo-random reasonably unique one.
            $entity->setSlug('slug-' . md5(mt_rand()));
        }
        parent::persist($queries, $entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getStorageType()
    {
        return Type::getType('string');
    }
}
