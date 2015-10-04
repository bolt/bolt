<?php
namespace Bolt\Storage\Field\Type;

use Bolt\Storage\EntityManager;
use Bolt\Storage\QuerySet;
use Carbon\Carbon;
use Doctrine\DBAL\Types\Type;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class DateTimeType extends DateType
{
    /**
     * @inheritdoc
     */
    public function __construct(array $mapping = [], EntityManager $em = null)
    {
        parent::__construct($mapping, $em);
        Type::overrideType(Type::DATETIME, 'Bolt\Storage\Mapping\Type\CarbonDateTimeType');
    }

    /**
     * {@inheritdoc}
     */
    public function persist(QuerySet $queries, $entity, EntityManager $em = null)
    {
        $key = $this->mapping['fieldname'];
        $value = $entity->get($key);

        if (!$value instanceof \DateTime && $value !== null) {
            $value = new Carbon($value);
            $entity->set($key, $value);
        }

        parent::persist($queries, $entity, $em);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'datetime';
    }
}
