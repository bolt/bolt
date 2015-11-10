<?php
namespace Bolt\Storage\Entity;

use Bolt\Storage\ContentLegacyService;
use Bolt\Storage\Mapping\ContentTypeTitleTrait;
use Bolt\Storage\EntityProxy;
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
    use ContentTypeTitleTrait;

    protected $contenttype;
    protected $_legacy;
    protected $_relationInbound;
    protected $_relationOutbound;
    protected $_taxonomy;
    protected $id;
    protected $slug;
    protected $datecreated;
    protected $datechanged;
    protected $datepublish = null;
    protected $datedepublish = null;
    protected $ownerid;
    protected $status;
    protected $taxonomy;
    protected $templatefields;

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
        if ($key === 'title') {
            return $this->getTitle();
        }

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
     * Getter for a record's 'title' field.
     *
     * If there is no field called 'title' then we just return the first text
     * type field.
     *
     * @return string
     */
    public function getTitle()
    {
        if (isset($this->_fields['title'])) {
            return $this->_fields['title'];
        }

        $fieldName = $this->getTitleColumnName($this->contenttype);

        return $this->$fieldName;
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

    /**
     * A catch all for relations that handles BC requests by default.
     *
     * @param boolean $outboundOnly
     *
     * @return EntityProxy[]
     */
    public function getRelation($outboundOnly = false)
    {
        if ($outboundOnly) {
            return (array) $this->_relationOutbound;
        }

        return array_merge(
            (array) $this->_relationOutbound,
            (array) $this->_relationInbound
        );
    }

    /**
     * Get the outbound relationships.
     *
     * @return EntityProxy[]
     */
    public function getRelationOutbound()
    {
        return $this->_relationOutbound;
    }

    /**
     * Set the outbound relationships.
     *
     * @param EntityProxy[] $values
     */
    public function setRelationOutbound($values)
    {
        $this->_relationOutbound = $values;
    }

    /**
     * Get the inbound relationships.
     *
     * @return EntityProxy[]
     */
    public function getRelationInbound()
    {
        return $this->_relationInbound;
    }

    /**
     * Set the inbound relationships.
     *
     * @param EntityProxy[] $values
     */
    public function setRelationInbound($values)
    {
        $this->_relationInbound = $values;
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
