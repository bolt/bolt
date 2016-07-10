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
}
