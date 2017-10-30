<?php

namespace Bolt\Storage\Entity;

/**
 * Entity for TemplateFields.
 */
class TemplateFields extends Entity
{
    /**
     * Getter for templates using {{ content.get(title) }} functions.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        $accessor = 'get' . ucfirst($key);

        return $this->$accessor();
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        /** @var \Bolt\Storage\Mapping\ContentType $contentType */
        $contentType = $this->get('contenttype');
        if (!method_exists($contentType, 'getFields')) {
            throw new \BadMethodCallException(
                sprintf('ContentType contained in %s does not implement getFields()', __CLASS__)
            );
        }
        $fields = $contentType->getFields();
        $values = [];
        foreach ($fields as $field) {
            $fieldName = $field['fieldname'];
            $val = $this->$fieldName;
            if (in_array($field['type'], ['date','datetime'])) {
                $val = (string) $this->$fieldName;
            }
            if (is_callable([$val, 'serialize'])) {
                $val = $val->serialize();
            }

            $values[$fieldName] = $val;
        }

        return $values;
    }
}
