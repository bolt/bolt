<?php
namespace Bolt\Events;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Event instance for Storage events.
 *
 * PRE_SAVE (preSave)
 * - Available:
 *   - Content obejct
 * - Notes:
 *   - Do not call saveContent()
 *
 * POST_SAVE (postSave)
 * - Available:
 *   - Content obejct
 *   - ID
 * - Notes:
 *   - Safe to call saveContent()
 *
 * PRE_DELETE (preDelete)
 * - Available:
 *   - Content obejct
 *   - ID
 * - Notes:
 *   - Do not call saveContent()
 *
 * POST_DELETE (postDelete)
 * - Available:
 *   - Content obejct
 *   - ID
 * - Notes:
 *   - Do not call saveContent()
 *   - Database record will no longer exist
 */
class StorageEvent extends GenericEvent
{
    /**
     * @var \Bolt\Legacy\Content|array
     */
    protected $subject;

    /**
     * @var array
     */
    protected $arguments;

    /**
     * Instantiate generic Storage Event.
     *
     * @param \Bolt\Legacy\Content|array $subject   A Content object that is being saved or deleted
     * @param array                      $arguments Arguments to store in the event.
     */
    public function __construct($subject = null, array $arguments = [])
    {
        $this->subject = $subject;
        $this->arguments = $arguments;
    }

    /**
     * Return the record id.
     *
     * @return integer
     */
    public function getId()
    {
        return $this->getSubject()->id;
    }

    /**
     * Return the record contenttype.
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->getSubject()->contenttype['slug'];
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
