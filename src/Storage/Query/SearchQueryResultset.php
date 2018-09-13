<?php

namespace Bolt\Storage\Query;

use ArrayIterator;

/**
 * This class builds on the default QueryResultset to add
 * the ability to merge sets based on weighted scores.
 */
class SearchQueryResultset extends QueryResultset
{
    /** @var array */
    protected $results = [];
    /** @var array */
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

        $this->append(new ArrayIterator($results));
    }

    /**
     * @param string $label
     */
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

    /**
     * @return array
     */
    public function getSortedResults()
    {
        $results = [];
        foreach ($this->results as $type => $records) {
            $scores = $this->scores[$type];

            foreach ($records as $i => $record) {
                $results[] = [
                    'record' => $record,
                    'score'  => $scores[$i],
                ];
            }
        }

        usort($results, function ($a, $b) {
            if ($a['score'] == $b['score']) {
                return 0;
            }
            return ($a['score'] < $b['score']) ? -1 : 1;
        });

        $results = array_map(function ($item) {
            return $item['record'];
        }, $results);

        return $results;
    }
}
