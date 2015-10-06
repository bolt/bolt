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
    public $order_on;
    public $order_ascending;
    public $order_ascending_secondary;
    public $order_on_secondary;

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
        // Set the 'order_on' and 'order_ascending', taking into account things like '-datepublish'.
        list($this->order_on, $this->order_ascending) = $this->app['storage']->getSortOrder($on);

        // Set the secondary order, if any.
        if (!empty($onSecondary)) {
            list($this->order_on_secondary, $this->order_ascending_secondary) = $this->app['storage']->getSortOrder($onSecondary);
        } else {
            $this->order_on_secondary = false;
            $this->order_ascending_secondary = false;
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
        $aVal = $a[$this->order_on];
        $bVal = $b[$this->order_on];

        // Check the primary sorting criterium.
        if ($aVal < $bVal) {
            return !$this->order_ascending;
        } elseif ($aVal > $bVal) {
            return $this->order_ascending;
        } else {
            // Primary criterium is the same. Use the secondary criterium, if it is set. Otherwise return 0.
            if (empty($this->order_on_secondary)) {
                return 0;
            }

            $aVal = $a[$this->order_on_secondary];
            $bVal = $b[$this->order_on_secondary];

            if ($aVal < $bVal) {
                return !$this->order_ascending_secondary;
            } elseif ($aVal > $bVal) {
                return $this->order_ascending_secondary;
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
}
