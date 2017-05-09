<?php

namespace Bolt\Twig\Extension;

use Twig_Extension as Extension;
use Twig_SimpleFilter as TwigFilter;
use Twig_SimpleFunction as TwigFunction;

/**
 * Bolt specific Twig functions and filters that provide array manipulation
 *
 * @internal
 */
class ArrayExtension extends Extension
{
    private $orderOn;
    private $orderAscending;
    private $orderOnSecondary;
    private $orderAscendingSecondary;

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        $safe = ['is_safe' => ['html']];

        return [
            // @codingStandardsIgnoreStart
            new TwigFunction('unique', [$this, 'unique'], $safe),
            // @codingStandardsIgnoreEnd
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            // @codingStandardsIgnoreStart
            new TwigFilter('order',   [$this, 'order']),
            new TwigFilter('shuffle', [$this, 'shuffle']),
            // @codingStandardsIgnoreEnd
        ];
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
        list($this->orderOn, $this->orderAscending) = $this->getSortOrder($on);

        // Set the secondary order, if any.
        if (!empty($onSecondary)) {
            list($this->orderOnSecondary, $this->orderAscendingSecondary) = $this->getSortOrder($onSecondary);
        } else {
            $this->orderOnSecondary = false;
            $this->orderAscendingSecondary = false;
        }

        uasort($array, [$this, 'orderHelper']);

        return $array;
    }

    /**
     * Get sorting order of name, stripping possible " DESC" " ASC" etc., and
     * also return the sorting order.
     *
     * @param string $name
     *
     * @return string
     */
    private function getSortOrder($name = '-datepublish')
    {
        // If we don't get a string, we can't determine a sort order.
        if (!is_string($name)) {
            return false;
        }

        $parts = explode(' ', $name);
        $fieldName = $parts[0];
        $sort = 'ASC';
        if (isset($parts[1])) {
            $sort = $parts[1];
        }

        if ($fieldName[0] === '-') {
            $fieldName = substr($fieldName, 1);
            $sort = 'DESC';
        }

        return [$fieldName, (strtoupper($sort) === 'ASC')];
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

        // Check the primary sorting criterion.
        if ($aVal < $bVal) {
            return !$this->orderAscending;
        } elseif ($aVal > $bVal) {
            return $this->orderAscending;
        } else {
            // Primary criterion is the same. Use the secondary criterion, if it is set. Otherwise return 0.
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
