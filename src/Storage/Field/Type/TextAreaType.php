<?php
namespace Bolt\Storage\Field\Type;

use Bolt\Configuration\ResourceManager;
use Bolt\Storage\QuerySet;
use Doctrine\DBAL\Types\Type;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class TextAreaType extends FieldTypeBase
{
    /**
     * {@inheritdoc}
     */
    public function persist(QuerySet $queries, $entity)
    {
        $value = $entity->get($key);

        $app = ResourceManager::getApp();
        $config = $app['config']->get('general/htmlcleaner');

        $value = parent::sanitize($value, $config['allowed_tags'], $config['allowed_attributes']);
        $entity->set($key, $value);

        parent::persist($queries, $entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'textarea';
    }

    /**
     * {@inheritdoc}
     */
    public function getStorageType()
    {
        return Type::getType('text');
    }
}
