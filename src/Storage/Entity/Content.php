<?php
namespace Bolt\Storage\Entity;

use Bolt\Storage\ContentLegacyService;
use Carbon\Carbon;

/**
 * Entity for Content.
 *
 * @method integer getId()
 * @method string  getSlug()
 * @method integer getOwnerid()
 * @method string  getStatus()
 * @method array   getTemplatefields()
 * @method setId(integer $id)
 * @method setSlug(string $slug)
 * @method setOwnerid(integer $ownerid)
 * @method setStatus(string $status)
 * @method setTemplatefields(array $templatefields)
 */
class Content extends Entity
{
    protected $_contenttype;
    protected $_legacy;
    protected $id;
    protected $slug;
    protected $datecreated;
    protected $datechanged;
    protected $datepublish = null;
    protected $datedepublish = null;
    protected $ownerid;
    protected $status;
    protected $templatefields;

    /**
     * Getter for templates using {{ content.get(title) }} functions.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        return $this->$key;
    }

    public function getDatecreated()
    {
        if (!$this->datecreated) {
            return new Carbon();
        }

        return $this->datecreated;
    }

    public function getDatechanged()
    {
        if (!$this->datechanged) {
            return new Carbon();
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

    public function setLegacyService(ContentLegacyService $service)
    {
        $this->_legacy = $service;
        $this->_legacy->initialize($this);
    }
}
