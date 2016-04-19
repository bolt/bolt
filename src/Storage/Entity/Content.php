<?php
namespace Bolt\Storage\Entity;

use Bolt\Storage\Collection;
use Bolt\Storage\ContentLegacyService;
use Bolt\Storage\Mapping\ContentTypeTitleTrait;
use Carbon\Carbon;

/**
 * Entity for Content.
 */
class Content extends Entity
{
    use ContentRouteTrait;
    use ContentTypeTitleTrait;

    protected $contenttype;
    /** @var ContentLegacyService */
    protected $_legacy;
    /** @var int */
    protected $id;
    /** @var string */
    protected $slug;
    /** @var \DateTime */
    protected $datecreated;
    /** @var \DateTime */
    protected $datechanged;
    /** @var \DateTime */
    protected $datepublish;
    /** @var \DateTime */
    protected $datedepublish;
    /** @var int */
    protected $ownerid;
    /** @var string */
    protected $status;
    /** @var Collection\Relations */
    protected $relation;
    /** @var Collection\Taxonomy */
    protected $taxonomy;

    /** @var array @deprecated Deprecated since 3.0, to be removed in 4.0. */
    protected $group;
    /** @var int */
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
        $setter = 'set' . ucfirst($key);
        if (is_array($value)) {
            $value = array_filter($value);
        }
        $this->$setter($value);
    }

    /**
     * @return int
     */
    public function getSortorder()
    {
        return $this->sortorder;
    }

    /**
     * @param int $sortorder
     */
    public function setSortorder($sortorder)
    {
        $this->sortorder = $sortorder;
    }

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
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
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
     * @return \DateTime
     */
    public function getDatepublish()
    {
        return $this->datepublish;
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
     * @return \DateTime
     */
    public function getDatedepublish()
    {
        return $this->datedepublish;
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

    /**
     * @return int
     */
    public function getOwnerid()
    {
        return $this->ownerid;
    }

    /**
     * @param int $ownerid
     */
    public function setOwnerid($ownerid)
    {
        $this->ownerid = $ownerid;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return Collection\Relations
     */
    public function getRelation()
    {
        if (!$this->relation instanceof Collection\Relations) {
            $this->relation = new Collection\Relations();
        }

        return $this->relation;
    }

    /**
     * @param Collection\Relations $rel
     */
    public function setRelation(Collection\Relations $rel)
    {
        $this->relation = $rel;
    }

    /**
     * @return Collection\Taxonomy
     */
    public function getTaxonomy()
    {
        if (!$this->taxonomy instanceof Collection\Taxonomy) {
            $this->taxonomy = new Collection\Taxonomy();
        }

        return $this->taxonomy;
    }

    /**
     * @param Collection\Taxonomy $taxonomy
     */
    public function setTaxonomy(Collection\Taxonomy $taxonomy)
    {
        $this->taxonomy = $taxonomy;
    }

    /**
     * @return array
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param array $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * Helper to set an array of values
     *
     * @param array $values
     */
    public function setValues(array $values)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
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
        return $this->templatefields ?: [];
    }

    public function setTemplatefields($value)
    {
        $this->templatefields = $value;
    }

    /**
     * @return ContentLegacyService
     */
    public function getLegacy()
    {
        return $this->_legacy;
    }

    /**
     * @param ContentLegacyService $service
     */
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
