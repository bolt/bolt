<?php

namespace Bolt\Twig\Handler;

use Silex;

/**
 * Bolt specific Twig functions and filters that provide array manipulation
 *
 * @internal
 */
class ArrayHandler
{
    private $orderOn;
    private $orderAscending;
    private $orderAscendingSecondary;
    private $orderOnSecondary;

    /** @var \Silex\Application */
    private $app;

    /**
     * @param \Silex\Application $app
     */
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
    }

    /**
     * Sorts / orders items of an array.
     *
     * @param array  $array
     * @param string $on
     * @param string $onSecondary
     *
     * @return array
     */
    public function order($array, $on, $onSecondary = '')
    {
        // Set the 'orderOn' and 'orderAscending', taking into account things like '-datepublish'.
        list($this->orderOn, $this->orderAscending) = $this->app['storage']->getSortOrder($on);

        // Set the secondary order, if any.
        if (!empty($onSecondary)) {
            list($this->orderOnSecondary, $this->orderAscendingSecondary) = $this->app['storage']->getSortOrder($onSecondary);
        } else {
            $this->orderOnSecondary = false;
            $this->orderAscendingSecondary = false;
        }

        uasort($array, [$this, 'orderHelper']);

        return $array;
    }

    /**
     * Helper function for sorting an array of \Bolt\Legacy\Content.
     *
     * @param \Bolt\Legacy\Content|array $a
     * @param \Bolt\Legacy\Content|array $b
     *
     * @return boolean
     */
    private function orderHelper($a, $b)
    {
        $aVal = $a[$this->orderOn];
        $bVal = $b[$this->orderOn];

        // Check the primary sorting criterium.
        if ($aVal < $bVal) {
            return !$this->orderAscending;
        } elseif ($aVal > $bVal) {
            return $this->orderAscending;
        } else {
            // Primary criterium is the same. Use the secondary criterium, if it is set. Otherwise return 0.
            if (empty($this->orderOnSecondary)) {
                return 0;
            }

            $aVal = $a[$this->orderOnSecondary];
            $bVal = $b[$this->orderOnSecondary];

            if ($aVal < $bVal) {
                return !$this->orderAscendingSecondary;
            } elseif ($aVal > $bVal) {
                return $this->orderAscendingSecondary;
            } else {
                // both criteria are the same. Whatever!
                return 0;
            }
        }
    }

    /**
     * Randomly shuffle the contents of a passed array.
     *
     * @param array $array
     *
     * @return array
     */
    public function shuffle($array)
    {
        if (is_array($array)) {
            shuffle($array);
        }

        return $array;
    }

    /**
     * Takes two arrays and returns a compiled array of unique, sorted values
     *
     * @param $arr1
     * @param $arr2
     *
     * @return array
     */
    public function unique($arr1, $arr2)
    {
        $merged = array_unique(array_merge($arr1, $arr2), SORT_REGULAR);
        $compiled = [];

        foreach ($merged as $val) {
            $compiled[$val[0]] = $val;
        }

        return $compiled;
    }
}
