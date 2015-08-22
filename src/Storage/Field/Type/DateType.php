<?php
namespace Bolt\Storage\Field\Type;

use Bolt\Exception\QueryParseException;
use Bolt\Storage\Mapping\ClassMetadata;
use Bolt\Storage\Query\QueryInterface;
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
    public function __construct(array $mapping = [])
    {
        parent::__construct($mapping);
        Type::overrideType(Type::DATE, 'Bolt\Storage\Mapping\Type\CarbonDateType');
        Type::overrideType(Type::DATETIME, 'Bolt\Storage\Mapping\Type\CarbonDateType');
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
     * @inheritdoc
     */
    public function getName()
    {
        return 'date';
    }
}
