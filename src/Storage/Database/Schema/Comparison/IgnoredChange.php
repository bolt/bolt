<?php

namespace Bolt\Storage\Database\Schema\Comparison;

/**
 * .
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class IgnoredChange
{
    /** @var string */
    protected $alteration;
    /** @var string */
    protected $propertyName;
    /** @var string */
    protected $before;
    /** @var string */
    protected $after;

    /**
     * Constructor.
     *
     * @param string $alteration
     * @param string $propertyName
     * @param string $before
     * @param string $after
     */
    public function __construct($alteration, $propertyName, $before, $after)
    {
        $this->alteration = $alteration;
        $this->propertyName = $propertyName;
        $this->before = $before;
        $this->after = $after;
    }

    /**
     * Check if parameters match object parameters.
     *
     * @param string $alteration
     * @param string $propertyName
     * @param string $before
     * @param string $after
     *
     * @return boolean
     */
    public function matches($alteration, $propertyName, $before, $after)
    {
        if ($this->alteration === $alteration
            && $this->propertyName === $propertyName
            && $this->before === $before
            && $this->after === $after
        ) {
            return true;
        }

        return false;
    }

    public function getAlteration()
    {
        return $this->alteration;
    }

    public function getPropertyName()
    {
        return $this->propertyName;
    }

    public function getBefore()
    {
        return $this->before;
    }

    public function getAfter()
    {
        return $this->after;
    }
}
