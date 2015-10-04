<?php

namespace Bolt\Storage\Query;

/**
 * This class is a wrapper that handles single or multiple
 * sets or results fetched via a query. They can be iterated
 * normally, or split by label, eg just results from one
 * contenttype.
 */
class QueryResultset extends \AppendIterator implements \Countable
{
    protected $results = [];

    /**
     * @param array  $results A set of results
     * @param string $type    An optional label to partition results
     */
    public function add($results, $type = null)
    {
        if ($type) {
            $this->results[$type] = $results;
        } else {
            $this->results = array_merge($this->results, $results);
        }

        $this->append(new \ArrayIterator($results));
    }

    /**
     * Allows retrieval of a set or results, if a label has been used to
     * store results then passing the label as a parameter returns just
     * that set of results.
     *
     * @param string $label
     *
     * @return ArrayIterator
     */
    public function get($label = null)
    {
        if ($label && array_key_exists($label, $this->results)) {
            return $this->results[$label];
        } else {
            $results = [];
            foreach ($this->results as $v) {
                if (is_array($v)) {
                    $results = array_merge($results, $v);
                } else {
                    $results[] = $v;
                }
            }

            return $results;
        }
    }

    /**
     * Returns the total count
     *
     * @return int
     */
    public function count()
    {
        return count($this->get());
    }
}
