<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for cron jobs.
 */
class Cron extends Entity
{
    /** @var integer */
    protected $id;
    /** @var string */
    protected $interim;
    /** @var \DateTime */
    protected $lastrun;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getInterim()
    {
        return $this->interim;
    }

    /**
     * @param string $interim
     */
    public function setInterim($interim)
    {
        $this->interim = $interim;
    }

    /**
     * @return \DateTime
     */
    public function getLastrun()
    {
        return $this->lastrun;
    }

    /**
     * @param \DateTime $lastrun
     */
    public function setLastrun($lastrun)
    {
        $this->lastrun = $lastrun;
    }
}
