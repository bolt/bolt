<?php

namespace Bolt\Storage\Query;

/**
 * This class builds on the default QueryResultset to add
 * the ability to merge sets based on weighted scores.
 */
class SearchQueryResultset extends QueryResultset
{
    protected $results = [];

    protected $scores = [];

    /**
     * @param array  $results A set of results
     * @param string $type    An optional label to partition results
     * @param array  $scores  An array of scores for the corresponding results
     */
    public function add($results, $type = null, $scores = [])
    {
        if ($type !== null) {
            $this->results[$type] = $results;
            $this->scores[$type] = $scores;
            $this->sortSingle($type);
        } else {
            $this->results = array_merge($this->results, $results);
        }

        $this->append(new \ArrayIterator($results));
    }

    public function sortSingle($label)
    {
        $results = $this->get($label);
        $scores = $this->scores[$label];
        arsort($scores);
        $sorted = [];

        foreach ($scores as $k => $v) {
            $sorted[] = $results[$k];
        }

        $this->results[$label] = $sorted;
    }
}
