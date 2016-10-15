<?php

namespace Bolt\Storage\Mapping\Definition;

use Bolt\Storage\Mapping\Definition;
use Bolt\Storage\Mapping\MappingAwareInterface;
use Bolt\Storage\Mapping\MappingManager;

/**
 * Adds specific functionality for repeaters definition
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */

class Repeater extends Definition implements MappingAwareInterface
{
    protected $manager;

    public function setMappingManager(MappingManager $manager)
    {
        $this->manager = $manager;
    }

    public function validate()
    {
        parent::validate();

        if (! $this->get('fields')) {
            $error = sprintf('Repeater Field "%s" has no "fields" set.', $this->getName());

            throw new InvalidArgumentException($error);
        }
    }

    public function normalize()
    {
        parent::normalize();

        $parsed = [];
        foreach ($this->getFields() as $repeaterKey => $repeaterField) {
            $parsed[$repeaterKey] = $this->manager->load($repeaterKey, $repeaterField);
        }
        $this->set('fields', $parsed);
    }

    public function getFields()
    {
        $res = $this->get('fields', []);

        return (array) $res;
    }

    public function getField($name)
    {
        $fields = $this->getFields();
        if (array_key_exists($name, $fields)) {
            return $fields[$name];
        }

        return false;
    }
}