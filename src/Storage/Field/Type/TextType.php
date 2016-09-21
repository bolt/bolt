<?php

namespace Bolt\Storage\Field\Type;

use Bolt\Storage\QuerySet;

/**
 * This is one of a suite of basic Bolt field transformers that handles
 * the lifecycle of a field from pre-query to persist.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class TextType extends FieldTypeBase
{
    /**
     * {@inheritdoc}
     */
    public function persist(QuerySet $queries, $entity)
    {
        $key = $this->mapping['fieldname'];
        $value = $entity->get($key);

        // Note: The following section is commented out, because it was
        // sanitising fields when not desired. See #5789, for details.

        // Only sanitize when type is string, and not when the name is one of the Bolt-system ones.
        // Finally, we skip this if the value is empty-ish, e.g. '' or `null`.
        // if ($this->mapping['type'] === 'string' && !in_array($key, ['username', 'status']) && !empty($value)) {
        //     $entity->set($key, $this->getSanitiser()->sanitise($value));
        // }

        parent::persist($queries, $entity);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'text';
    }
}
