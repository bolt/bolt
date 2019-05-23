<?php

namespace Bolt\Storage\Entity;

use Bolt\Helpers\Excerpt;
use Bolt\Storage\Collection;
use Bolt\Storage\ContentLegacyService;
use Bolt\Storage\Mapping;
use Bolt\Storage\Mapping\ContentTypeTitleTrait;
use Carbon\Carbon;
use Twig\Markup;

/**
 * Entity for Content.
 */
class Content extends Entity
{
    use ContentRouteTrait;
    use ContentTypeTrait;
    use ContentTypeTitleTrait;
    use ContentUserTrait;
    use ContentUriTrait;

    /** @var string|Mapping\ContentType */
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
    /** @var TemplateFields */
    protected $templatefields;

    /** @var array @deprecated Deprecated since 3.0, to be removed in 4.0. */
    protected $group;
    /** @var int */
    protected $sortorder;

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $setter = 'set' . ucfirst($key);
        if (is_array($value)) {
            // Filter empty values from the array
            $value = array_filter($value, function ($item) {
                return $item !== null && $item !== '';
            });
        }
        $this->$setter($value);
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @return int
     */
    public function getSortorder()
    {
        return $this->sortorder;
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
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
     * @param string|null $contentType
     *
     * @return Collection\Relations|Collection\LazyCollection
     */
    public function getRelation($contentType = null)
    {
        if (!$this->relation instanceof Collection\Relations) {
            $this->relation = new Collection\Relations();
        }

        if ($contentType !== null) {
            return $this->relation[$contentType];
        }

        return $this->relation;
    }

    /**
     * @param Collection\Relations $rel
     */
    public function setRelation(Collection\Relations $rel)
    {
        $this->relation = $rel;
        $rel->setOwner($this);
    }

    /**
     * Gets all related content items
     *
     * @param $contentType
     *
     * @return Collection\LazyCollection
     */
    public function getRelated($contentType = null)
    {
        if ($contentType !== null) {
            return $this->getRelation($contentType);
        }

        return $this->getRelation()->all();
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
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @return array
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @param array $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * Helper to set an array of values.
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
     * Helper to return an array of user-defined values from the Entity.
     * This excludes meta fields set by Bolt.
     */
    public function getValues()
    {
        $contentType = $this->getContenttype();
        if (!isset($contentType['fields'])) {
            return [];
        }

        $allValues = $this->toArray();

        return array_intersect_key($allValues, ($contentType['fields']));
    }

    /**
     * Create an excerpt for the Entity.
     *
     * @param int               $length
     * @param bool              $includeTitle
     * @param array|string|null $focus
     * @param array             $stripFields
     *
     * @return string|null
     */
    public function getExcerpt($length = 200, $includeTitle = false, $focus = null, $stripFields = [])
    {
        $excerpter = new Excerpt($this);
        $excerpt = $excerpter->getExcerpt($length, $includeTitle, $focus, $stripFields);

        return new Markup($excerpt, 'UTF-8');
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
        $fields = $this->_fields;

        if (array_key_exists('title', $fields)) {
            return (string) $fields['title'];
        }

        $fieldNames = $this->getTitleColumnNames($this->contenttype);

        $title = [];
        foreach ($fieldNames as $fieldName) {
            // Make sure we add strings only, as some fields may be an array or DateTime.
            if (array_key_exists($fieldName, $fields)) {
                $title[] = is_array($fields[$fieldName]) ? implode(' ', $fields[$fieldName]) : (string) $fields[$fieldName];
            }
        }

        return implode(' ', $title);
    }

    /**
     * @return string|Mapping\ContentType
     */
    public function getContenttype()
    {
        return $this->contenttype;
    }

    /**
     * @param string|Mapping\ContentType $value
     */
    public function setContenttype($value)
    {
        $this->contenttype = $value;
    }

    /**
     * @return TemplateFields|array|null
     */
    public function getTemplatefields()
    {
        return $this->templatefields ?: [];
    }

    /**
     * @param TemplateFields|array|null $value
     */
    public function setTemplatefields($value)
    {
        if ($value === null) {
            $value = [];
        }
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
        }
        if (is_string($date)) {
            return new Carbon($date);
        }

        return $date;
    }
}
