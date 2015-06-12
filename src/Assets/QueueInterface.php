<?php
namespace Bolt\Assets;

/**
 * Interface for asset queues.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface QueueInterface
{
    /**
     * Insert all assets into template.
     *
     * @param $html
     *
     * @return string
     */
    public function process($html);

    /**
     * Get the queued assets in this queue.
     *
     * @return 
     */
    public function getQueue();
}
