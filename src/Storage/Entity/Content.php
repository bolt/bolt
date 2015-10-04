<?php
namespace Bolt\Storage\Entity;

use Bolt\Storage\ContentLegacyService;
use Carbon\Carbon;

/**
 * Entity for Content.
 *
 * @method integer   getId()
 * @method string    getSlug()
 * @method \DateTime getDatepublish()
 * @method \DateTime getDatedepublish()
 * @method integer   getOwnerid()
 * @method string    getStatus()
 * @method string    getTitle()
 * @method array     getTemplatefields()
 * @method array     getRelation()
 * @method array     getTaxonomy()
 * @method setId(integer $id)
 * @method setSlug(string $slug)
 * @method setOwnerid(integer $ownerid)
 * @method setUsername(string $userName)
 * @method setStatus(string $status)
 * @method setTemplatefields(array $templatefields)
 * @method setRelation(array $relation)
 * @method setTaxonomy(array $taxonomy)
 */
class Content extends Entity
{
    protected $contenttype;
    protected $_legacy;
    protected $id;
    protected $slug;
    protected $datecreated;
    protected $datechanged;
    protected $datepublish = null;
    protected $datedepublish = null;
    protected $ownerid;
    protected $status;
    protected $relation;
    protected $taxonomy;

    /** @var array @deprecated Since v2.3 will be removed in v3.0 */
    protected $group;
    protected $sortorder;

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

    /**
     * Setter for content values.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function set($key, $value)
    {
        $this->$key = $value;
    }

    /**
     * Get creation date.
     *
     * @return \DateTime
     */
    public function getDatecreated()
    {
        if (!$this->datecreated) {
            return new Carbon();
        }

        return $this->datecreated;
    }

    /**
     * Set creation date.
     *
     * @param \DateTime|string|null $date
     */
    public function setDatecreated($date)
    {
        $this->datecreated = $this->getValidDateObject($date);
    }

    /**
     * Get change date.
     *
     * @return \DateTime
     */
    public function getDatechanged()
    {
        if (!$this->datechanged) {
            return new Carbon();
        }

        return $this->datechanged;
    }

    /**
     * Set change date.
     *
     * @param \DateTime|string|null $date
     */
    public function setDatechanged($date)
    {
        $this->datechanged = $this->getValidDateObject($date);
    }

    /**
     * Set published date.
     *
     * @param \DateTime|string|null $date
     */
    public function setDatepublish($date)
    {
        $this->datepublish = $this->getValidDateObject($date);
    }

    /**
     * Set depublished date.
     *
     * @param \DateTime|string|null $date
     */
    public function setDatedepublish($date)
    {
        $this->datedepublish = $this->getValidDateObject($date);
    }

    public function getContenttype()
    {
        return $this->contenttype;
    }

    public function setContenttype($value)
    {
        $this->contenttype = $value;
    }

    public function getTemplatefields()
    {
        return $this->templatefields;
    }

    public function setTemplatefields($value)
    {
        $this->templatefields = $value;
    }

    public function setLegacyService(ContentLegacyService $service)
    {
        $this->_legacy = $service;
        $this->_legacy->initialize($this);
    }

    /**
     * Get a valid date property to persist.
     *
     * @param \DateTime|string|null $date
     *
     * @return \DateTime|null
     */
    protected function getValidDateObject($date)
    {
        if (empty($date)) {
            return null;
        } elseif (is_string($date)) {
            return new Carbon($date);
        }
        return $date;
    }
}
