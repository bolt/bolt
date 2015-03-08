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
    
}
