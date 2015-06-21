<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for cron jobs.
 */
class Cron extends Entity
{
    protected $id;
    protected $lastrun;
    protected $interim;
}
