<?php
namespace Bolt\Storage\Entity;

/**
 * Trait class for ContentType relations.
 *
 * This is a breakout of the old Bolt\Content class and serves two main purposes:
 *   * Maintain backward compatibility for Bolt\Content through the remainder of
 *     the 2.x development/release life-cycle
 *   * Attempt to break up former functionality into sections of code that more
 *     resembles Single Responsibility Principles
 *
 * These traits should be considered transitional, the functionality in the
 * process of refactor, and not representative of a valid approach.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait ContentRelationTrait
{
    /**
     * Alias for getRelation()
     */
    public function related($filterContentType = null, $options = [])
    {
        return $this->getRelation($filterContentType, $options);
    }

    /**
     * Gets one or more related records.
     *
     * @param string $filterContentType Contenttype to filter returned results on
     * @param array  $options           A set of 'WHERE' options to apply to the filter
     *
     * Backward compatability note:
     * The $options parameter used to be $filterid, an integer.
     *
     * @return \Bolt\Legacy\Content[]
     */
    public function getRelation($filterContentType = null, $options = [])
    {
        if (empty($this->relation)) {
            return false; // nothing to do here.
        }

        // Backwards compatibility: If '$options' is a string, assume we passed an id
        if (!is_array($options)) {
            $options = [
                'id' => $options,
            ];
        }

        $records = [];

        foreach ($this->relation as $contenttype => $ids) {
            if (!empty($filterContentType) && ($contenttype != $filterContentType)) {
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

            // Only get published items, unless specifically stated otherwise.
            if (!isset($where['status'])) {
                $where['status'] = 'published';
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

    /**
     * Clears a relation.
     *
     * @param string|array $contenttype
     *
     * @return void
     */
    public function clearRelation($contenttype)
    {
        if (!empty($this->relation[$contenttype])) {
            unset($this->relation[$contenttype]);
        }
    }
}
