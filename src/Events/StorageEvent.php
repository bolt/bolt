<?php
namespace Bolt\Events;

use Bolt;
use Bolt\Content;
use Symfony\Component\EventDispatcher\Event;

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
class StorageEvent extends Event
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
     * @param mixed $in The content or (contenttype,id) combination
     */
    public function __construct($in = null, $create = null)
    {
        if ($in instanceof \Bolt\Content) {
            $this->setContent($in);
        } elseif (is_array($in)) {
            $this->setContentTypeAndId($in[0], $in[1]);
        }

        $this->create = $create;
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
