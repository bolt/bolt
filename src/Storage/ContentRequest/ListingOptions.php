<?php

namespace Bolt\Storage\ContentRequest;

/**
 * Class to manage record listing options.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ListingOptions
{
    /** @var string */
    protected $order;
    /** @var int */
    protected $page;
    /** @var array */
    protected $taxonomies;
    /** @var string */
    protected $filter;
    /** @var string */
    protected $status;
    /** @var bool */
    protected $groupSort;

    /**
     * Set the order.
     *
     * @param string|null $order
     *
     * @return ListingOptions
     */
    public function setOrder($order)
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Get the order… Would you like fries with that?
     *
     * @return string
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set the page.
     *
     * @param int|null $page
     *
     * @return ListingOptions
     */
    public function setPage($page)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Get the page.
     *
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Get the previous page number.
     *
     * @return int
     */
    public function getPreviousPage()
    {
        return $this->page && $this->page > 1
            ? $this->page - 1
            : $this->page;
    }

    /**
     * Set the taxonomies.
     *
     * @param array|null $taxonomies
     *
     * @return ListingOptions
     */
    public function setTaxonomies($taxonomies)
    {
        $this->taxonomies = $taxonomies;

        return $this;
    }

    /**
     * Get the taxonomies.
     *
     * @return array
     */
    public function getTaxonomies()
    {
        return $this->taxonomies;
    }

    /**
     * Set the filter.
     *
     * @param string|null $filter
     *
     * @return ListingOptions
     */
    public function setFilter($filter)
    {
        $this->filter = $filter === '' ? null : $filter;

        return $this;
    }

    /**
     * Get the filter.
     *
     * @return string
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Set the status.
     *
     * @param string $status
     *
     * @return ListingOptions
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get the status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return mixed
     */
    public function getGroupSort()
    {
        return $this->groupSort;
    }

    /**
     * @param mixed $groupSort
     *
     * @return ListingOptions
     */
    public function setGroupSort($groupSort)
    {
        $this->groupSort = $groupSort;

        return $this;
    }
}
