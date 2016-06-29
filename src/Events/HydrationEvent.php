<?php

namespace Bolt\Events;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Hydration event allow access to
 * pre and post hydration of entities.
 *
 * Before hydration, the subject will be an array of fetched data
 * After hydration, the subject will be the hydrated object
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class HydrationEvent extends GenericEvent
{
    /**
     * Encapsulate an event with $subject and $args.
     *
     * @param mixed $subject   The subject of the event, where an array this will be passed by reference.
     * @param array $arguments Arguments to store in the event.
     */
    public function __construct(&$subject = null, array $arguments = [])
    {
        $this->subject = $subject;
        $this->arguments = $arguments;
    }
}
