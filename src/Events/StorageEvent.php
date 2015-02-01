<?php
namespace Bolt\Events;

use Bolt;
use Bolt\Content;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Our specific Event instance for Storage Events
 *
 * For preSave and postSave you may assume you can directly access the
 * content using $this->getContent(). The id will be available in a
 * postSave but is not guarenteerd for a preSave (since it could be
 * new).
 * For preDelete and postDelete the content won't be available, but the
 * $this->getId() and $this->getContentType() will be set.
 */
class StorageEvent extends GenericEvent
{
    /**
     * @var Bolt\Content
     */
    private $subject;

    /**
     * @var array
     */
    private $arguments;

    /**
     * Instantiate generic Storage Event
     *
     * @param Bolt\Content $subject   A Content object that is being saved or deleted
     * @param array        $arguments Arguments to store in the event.
     */
    public function __construct(Content $subject = null, array $arguments = array())
    {
        $this->subject = $subject;
        $this->arguments = $arguments;
    }

    /**
     * Return the record id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->getSubject()->id;
    }

    /**
     * Return the record contenttype
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->getSubject()->contenttype['slug'];
    }

    /**
     * Return the content object
     *
     * @return Bolt\Content
     */
    public function getContent()
    {
        return $this->getSubject();
    }

    /**
     * Is the record being created, updated or deleted
     *
     * @return bool True  - Create
     *              False - Update
     *              Null  - Delete
     */
    public function isCreate()
    {
        if ($this->hasArgument('create')) {
            return $this->getArgument('create');
        }
    }
}
