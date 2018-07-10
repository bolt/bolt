<?php

namespace Bolt\Twig;

use Bolt\Storage\Entity\Content;
use Bolt\Storage\Field\Collection\FieldCollection;
use Bolt\Storage\Mapping\MetadataDriver;
use Bolt\Storage\Query\QueryResultset;
use ParsedownExtra;
use Twig\Markup;

/**
 * Twig Records View, wraps content records before they are passed to Twig
 * so appropriate transformation can occur.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class TwigRecordsView
{
    /** @var MetadataDriver */
    protected $metadata;
    /** @var array $transformers */
    protected $transformers;

    /**
     * Constructor.
     *
     * @param MetadataDriver $metadata
     */
    public function __construct(MetadataDriver $metadata)
    {
        $this->metadata = $metadata;
        $this->setupDefaults();
    }

    /**
     *  Here we register some transformers that prepare the fields to be passed to
     *  twig. For repeaters and blocks this is recursive.
     */
    protected function setupDefaults()
    {
        $this->addTransformer('html', function ($value) {
            return new Markup($value, 'UTF-8');
        });
        $this->addTransformer('text', function ($value) {
            return new Markup($value, 'UTF-8');
        });
        $this->addTransformer('textarea', function ($value) {
            return new Markup($value, 'UTF-8');
        });
        $this->addTransformer('markdown', function ($value) {
            $markdown = new ParsedownExtra();
            $value = $markdown->text($value);

            return new Markup($value, 'UTF-8');
        });

        $this->addTransformer('repeater', function ($value) {
            /** @var FieldCollection $collection */
            foreach ($value as $collection) {
                foreach ($collection as $field) {
                    $field->setValue($this->transform($field->getValue(), $field->getFieldType()));
                }
            }

            return $value;
        });

        $this->addTransformer('block', function ($value) {
            return $this->transform($value, 'repeater');
        });
    }

    /**
     * This loads the relevant record or records and activates the class.
     *
     * @param Content|QueryResultset $records
     *
     * @return Content|QueryResultset
     */
    public function createView($records)
    {
        if ($records instanceof Content) {
            $this->processSingleRecord($records);
        } elseif ($records instanceof QueryResultset) {
            $this->processRecords($records);
        }

        return $records;
    }

    /**
     * @param $record
     */
    protected function processSingleRecord($record)
    {
        $values = $record->getValues();
        if (is_array($values)) {
            foreach ($values as $field => $value) {
                $type = $this->metadata->getFieldMetadata((string) $record->getContenttype(), $field);
                $boltType = $type['data']['type'];
                $record->set($field, $this->transform($value, $boltType, $type));
            }
        }
    }

    /**
     * @param $records
     */
    protected function processRecords($records)
    {
        foreach ($records as $record) {
            $this->processSingleRecord($record);
        }
    }

    /**
     * Adds a transformer callback to the field type $label
     *
     * @param $label
     * @param callable $callback
     */
    public function addTransformer($label, callable $callback)
    {
        $this->transformers[$label] = $callback;
    }

    /**
     * Checks if a transformer is registered for $label
     *
     * @param $label
     *
     * @return bool
     */
    public function hasTransformer($label)
    {
        return array_key_exists($label, $this->transformers);
    }

    /**
     * @param $label
     *
     * @return array|mixed
     */
    public function getTransformer($label)
    {
        if ($this->hasTransformer($label)) {
            return $this->transformers[$label];
        }
    }

    /**
     * @param $value
     * @param $label
     * @param array $fieldData
     *
     * @return mixed
     */
    protected function transform($value, $label, array $fieldData = [])
    {
        if ($this->hasTransformer($label)) {
            return $this->transformers[$label]($value, $fieldData);
        }

        return $value;
    }
}
