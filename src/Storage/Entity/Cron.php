<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for cron jobs.
 *
 * @method integer   getId()
 * @method string    getInterim()
 * @method \DateTime getLastrun()
 * @method setId($id)
 * @method setInterim($interim)
 * @method setLastrun($lastrun)
 */
class Cron extends Entity
{
    protected $id;
    protected $interim;
    protected $lastrun;
}
