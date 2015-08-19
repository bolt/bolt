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
     *
     */
    public function getRelation()
    {
    }

    /**
     * Gets one or more related records.
     *
     * @param string $filtercontenttype Contenttype to filter returned results on
     * @param array  $options           A set of 'WHERE' options to apply to the filter
     *
     * Backward compatability note:
     * The $options parameter used to be $filterid, an integer.
     *
     * @return \Bolt\Content[]
     */
    public function related($filtercontenttype = null, $options = [])
    {
        if (empty($this->relation)) {
            return false; // nothing to do here.
        }

        // Backwards compatibility: If '$options' is a string, assume we passed an id
        if (!is_array($options)) {
            $options = [
                'id' => $options
            ];
        }

        $records = [];

        foreach ($this->relation as $contenttype => $ids) {
            if (!empty($filtercontenttype) && ($contenttype != $filtercontenttype)) {
                continue; // Skip other contenttypes, if we requested a specific type.
            }

            $params = ['hydrate' => true];
            $where = ['id' => implode(' || ', $ids)];
            $dummy = false;

            // If there were other options add them to the 'where'. We potentially overwrite the 'id' here.
            if (!empty($options)) {
                foreach ($options as $option => $value) {
                    $where[$option] = $value;
                }
            }

            $tempResult = $this->app['storage']->getContent($contenttype, $params, $dummy, $where);

            if (empty($tempResult)) {
                continue; // Go ahead if content not found.
            }

            // Variable $temp_result can be an array of object.
            if (is_array($tempResult)) {
                $records = array_merge($records, $tempResult);
            } else {
                $records[] = $tempResult;
            }
        }

        return $records;
    }

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
