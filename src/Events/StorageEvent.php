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
     * The id
     */
    private $id = null;

    /**
     * The content type
     */
    private $contentType = null;

    /**
     * The content to act upon
     */
    private $content = null;

    /**
     * Record create/update flag
     */
    private $create = null;

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
     * Return the id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Return the content type
     *
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Return the content (if any)
     *
     * @return Bolt\Content
     */
    public function getContent()
    {
        return $this->content;
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
        return $this->create;
    }

    /**
     * Set the content type and id
     *
     * @param string  $contentType
     * @param integer $id
     */
    private function setContentTypeAndId($contentType, $id)
    {
        $this->contentType = $contentType;
        $this->id = $id;
    }

    /**
     * Set the content
     *
     * @param Content $content
     */
    private function setContent(Content $content)
    {
        $this->content = $content;

        $this->setContentTypeAndId($content->contenttype['slug'], $content->id);
    }
}
