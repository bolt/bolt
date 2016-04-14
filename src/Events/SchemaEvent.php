<?php

namespace Bolt\Events;

use Symfony\Component\EventDispatcher\Event;

/**
 * Schema event.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SchemaEvent extends Event
{
    /** @var array */
    protected $creates;
    /** @var array */
    protected $alters;

    /**
     * Constructor.
     *
     * @param array $creates
     * @param array $alters
     */
    public function __construct(array $creates, array $alters)
    {
        $this->creates = $creates;
        $this->alters = $alters;
    }

    /**
     * @return array
     */
    public function getCreates()
    {
        return $this->creates;
    }

    /**
     * @return array
     */
    public function getAlters()
    {
        return $this->alters;
    }
}
