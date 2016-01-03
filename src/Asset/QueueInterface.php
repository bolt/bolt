<?php

namespace Bolt\Asset;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface for asset queues.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface QueueInterface
{
    /**
     * Insert all queued assets into HTML response.
     *
     * @param Request  $request
     * @param Response $response
     */
    public function process(Request $request, Response $response);

    /**
     * Get the assets in this queue.
     *
     * @return AssetInterface[]|array
     */
    public function getQueue();

    /**
     * Clears the queue.
     */
    public function clear();
}
