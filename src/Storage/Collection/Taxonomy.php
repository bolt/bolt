<?php

namespace Bolt\Storage\Collection;

use Bolt\Storage\Entity;
use Bolt\Storage\Mapping\MetadataDriver;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * This class stores an array collection of Taxonomy Entities
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Taxonomy extends ArrayCollection
{

    protected $config;

    /**
     * Taxonomy constructor.
     * @param MetadataDriver $metadata
     */
    public function __construct(MetadataDriver $metadata)
    {
        $this->config = $metadata->getTaxonomyConfig();
    }

    public function setFromPost($formValues, $entity)
    {
        foreach ($formValues['taxonomy'] as $field => $values) {
            foreach ($values as $val) {
                $order = isset($formValues['taxonomy-order'][$field]) ? $formValues['taxonomy-order'][$field] : 0;
                if (isset($this->config[$field]['options'][$val])) {
                    $name = $this->config[$field]['options'][$val];
                }
                $taxentity = new Entity\Taxonomy( [
                    'name' => $name,
                    'contentId' => $entity->getId(),
                    'contenttype' => (string)$entity->getContenttype(),
                    'taxonomytype' => $field,
                    'slug' => $val,
                    'sortorder' => $order
                ]);
                $this->add($taxentity);
            }
        }
    }
}