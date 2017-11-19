<?php

namespace Bolt\Events;

use Bolt\Legacy;
use Bolt\Storage\Entity;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Event instance for Storage events.
 *
 * PRE_SAVE (preSave)
 * - Available:
 *   - Content object
 * - Notes:
 *   - Do not call saveContent()
 *
 * POST_SAVE (postSave)
 * - Available:
 *   - Content object
 *   - ID
 * - Notes:
 *   - Safe to call saveContent()
 *
 * PRE_DELETE (preDelete)
 * - Available:
 *   - Content object
 *   - ID
 * - Notes:
 *   - Do not call saveContent()
 *
 * POST_DELETE (postDelete)
 * - Available:
 *   - Content object
 *   - ID
 * - Notes:
 *   - Do not call saveContent()
 *   - Database record will no longer exist
 */
class StorageEvent extends GenericEvent
{
    /** @var Legacy\Content|Entity\Content|array */
    protected $subject;

    /**
     * Instantiate generic Storage Event.
     *
     * @param Legacy\Content|Entity\Content|array $subject   Content object
     * @param array                               $arguments
     */
    public function __construct($subject = null, array $arguments = [])
    {
        parent::__construct($subject, $arguments);
    }

    /**
     * Return the record id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->getSubject()->id;
    }

    /**
     * Return the record's ContentType name.
     *
     * @return string
     */
    public function getContentType()
    {
        $contentType = $this->getSubject()->contenttype;
        if ($contentType !== null && isset($contentType['slug'])) {
            return $contentType['slug'];
        }

        return null;
    }

    /**
     * Return the content object.
     *
     * @return \Bolt\Legacy\Content
     */
    public function getContent()
    {
        return $this->getSubject();
    }

    /**
     * Is the record being created, updated or deleted.
     *
     * @return bool|null True  - Create
     *                   False - Update
     *                   Null  - Delete
     */
    public function isCreate()
    {
        if ($this->hasArgument('create')) {
            return $this->getArgument('create');
        }

        return null;
    }
}
