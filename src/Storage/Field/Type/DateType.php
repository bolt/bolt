<?php
namespace Bolt\Storage\Field\Type;

use Bolt\Exception\QueryParseException;
use Bolt\Storage\EntityManager;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Query\QueryInterface;
use Bolt\Storage\QuerySet;
use Carbon\Carbon;
use Doctrine\DBAL\Types\Type;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class DateType extends FieldTypeBase
{
    /**
     * @inheritdoc
     */
    public function __construct(array $mapping = [], EntityManager $em = null)
    {
        parent::__construct($mapping, $em);
        Type::overrideType(Type::DATE, 'Bolt\Storage\Mapping\Type\CarbonDateType');
    }

    /**
     * Date fields perform substitution on the parameters passed in to query.
     * To handle this we pass every parameter through `strtotime()` to make
     * sure that it is a valid search.
     *
     * @param QueryInterface $query
     * @param ClassMetadata  $metadata
     *
     * @return void
     */
    public function query(QueryInterface $query, ClassMetadata $metadata)
    {
        $field = $this->mapping['fieldname'];
        $dateParams = $query->getWhereParametersFor($field);
        foreach ($dateParams as $key => $val) {
            $time = strtotime($val);
            if (!$time) {
                throw new QueryParseException('Unable to query $field = $val, not a valid date search', 1);
            }
            $replacement = date('Y-m-d H:i:s', $time);
            $query->setWhereParameter($key, $replacement);
        }
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
            $value::setToStringFormat('Y-m-d');
            $entity->set($key, $value);
        }

        parent::persist($queries, $entity, $em);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'date';
    }
}
