<?php
namespace Bolt\Storage\Entity;

/**
 * Trait class for ContentType relations.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait ContentRelationTrait
{
    /**
     * Add a relation.
     *
     * @param string|array $contenttype
     * @param integer      $id
     *
     * @return void
     */
    public function setRelation($contenttype, $id)
    {
        if (!empty($this->relation[$contenttype])) {
            $ids = $this->relation[$contenttype];
        } else {
            $ids = [];
        }

        $ids[] = $id;
        sort($ids);

        $this->relation[$contenttype] = array_unique($ids);
    }
}
