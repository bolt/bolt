<?php
namespace Bolt\Entity;

use Bolt\Entity\Entity;

/**
 * Entity for User.
 */
class Content extends Entity
{
    
    protected $id;
    protected $slug;
    protected $datecreated;
    protected $datechanged;
    protected $datepublish;
    protected $datedepublish;
    protected $ownerid;
    protected $status;
    
    
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
    
}
