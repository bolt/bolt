<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for Content.
 */
class Content extends Entity
{
    protected $_contenttype;
    protected $id;
    protected $datecreated;
    protected $datechanged;
    protected $datepublish = null;
    protected $datedepublish = null;

    public function getDatecreated()
    {
        if (!$this->datecreated) {
            return new \DateTime();
        }

        return $this->datecreated;
    }

    public function getDatechanged()
    {
        if (!$this->datechanged) {
            return new \DateTime();
        }

        return $this->datechanged;
    }

    public function getContenttype()
    {
        return $this->_contenttype;
    }

    public function setContenttype($value)
    {
        $this->_contenttype = $value;
    }
}
