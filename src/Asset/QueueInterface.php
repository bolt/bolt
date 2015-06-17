<?php
namespace Bolt\Asset;

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
     * @param $html
     *
     * @return string
     */
    public function process($html);

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
