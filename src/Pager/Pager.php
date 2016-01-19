<?php

namespace Bolt\Pager;

use Bolt\Legacy\AbstractPager;

/**
 * Class Pager
 *  Elementary pager object.
 *
 * @author Rix Beck <rix@neologik.hu>
 */
class Pager extends AbstractPager
{
    public $for;
    public $count;
    public $totalpages;
    public $current;
    public $showingFrom;
    public $showingTo;
    /**
     * @var PagerManager
     */
    public $manager;

    public function __construct(PagerManager $manager = null)
    {
        if ($manager) {
            $this->manager = $manager;
        }
    }

    /**
     * @param mixed $for
     *
     * @return Pager
     */
    public function setFor($for)
    {
        $this->for = $for;

        return $this;
    }

    /**
     * @param mixed $count
     *
     * @return Pager
     */
    public function setCount($count)
    {
        $this->count = $count;

        return $this;
    }

    /**
     * @param mixed $totalpages
     *
     * @return Pager
     */
    public function setTotalpages($totalpages)
    {
        $this->totalpages = $totalpages;

        return $this;
    }

    /**
     * @param mixed $current
     *
     * @return Pager
     */
    public function setCurrent($current)
    {
        $this->current = $current;

        return $this;
    }

    /**
     * @param mixed $showingFrom
     *
     * @return Pager
     */
    public function setShowingFrom($showingFrom)
    {
        $this->showingFrom = $showingFrom;

        return $this;
    }

    /**
     * @param mixed $showingTo
     *
     * @return Pager
     */
    public function setShowingTo($showingTo)
    {
        $this->showingTo = $showingTo;

        return $this;
    }

    /**
     * @param PagerManager $manager
     *
     * @return Pager
     */
    public function setManager($manager)
    {
        $this->manager = $manager;

        return $this;
    }

    /**
     * @param string $linkFor
     *
     * @return mixed
     */
    public function makeLink($linkFor = '')
    {
        return $this->manager->makeLink($linkFor);
    }

    /**
     * For v2 and v3 BC reasons
     *
     * @return array
     */
    public function asArray()
    {
        $a = get_object_vars($this);
        $a['showing_from'] = $this->showingFrom;
        $a['showing_to'] = $this->showingTo;

        return $a;
    }
}
