<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for relations.
 */
class Relations extends Entity
{
    /** @var int */
    protected $id;
    /** @var string */
    protected $from_contenttype;
    /** @var int */
    protected $from_id;
    /** @var string */
    protected $to_contenttype;
    /** @var int */
    protected $to_id;

    private $invert = false;

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
    public function getFromContenttype()
    {
        return $this->from_contenttype;
    }

    /**
     * @param string $from_contenttype
     */
    public function setFromContenttype($from_contenttype)
    {
        $this->from_contenttype = $from_contenttype;
    }

    /**
     * @return int
     */
    public function getFromId()
    {
        return $this->from_id;
    }

    /**
     * @param int $from_id
     */
    public function setFromId($from_id)
    {
        $this->from_id = $from_id;
    }

    /**
     * @return string
     */
    public function getToContenttype()
    {
        return $this->to_contenttype;
    }

    /**
     * @return int
     */
    public function getToId()
    {
        if ($this->invert === true) {
            return $this->from_id;
        }

        return $this->to_id;
    }

    /**
     * @param int $to_id
     */
    public function setToId($to_id)
    {
        $this->to_id = $to_id;
    }

    /**
     * @param string $to_contenttype
     */
    public function setToContenttype($to_contenttype)
    {
        $this->to_contenttype = $to_contenttype;
    }

    /**
     * @return boolean
     */
    public function isInvert()
    {
        return $this->invert;
    }

    /**
     * @param boolean $invert
     */
    public function setInvert($invert)
    {
        $this->invert = $invert;
    }

    public function actAsInverse()
    {
        $this->invert = true;
    }

    public function isInverted()
    {
        return $this->invert;
    }
}
