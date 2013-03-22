<?php
namespace Bolt;

use Silex;
use Bolt;
use util;
use Symfony\Component\EventDispatcher\Event;

class StorageEvent extends Event
{
    /**
     * The id
     */
    private $id = null;

    /**
     * The content type
     */
    private $content_type = null;

    /**
     * The content to act upon
     */
    private $content = null;


    /**
     * Instantiate generic Storage Event
     *
     * @param mixed $in The content or (contenttype,id) combination
     */
    public function __construct($in = null)
    {
        if ($in instanceof Content) {
            $this->setContent($in);
        }
        else if (is_array($in)) {
            $this->setContentTypeAndId($in[0], $in[1]);
        }
    }

    /**
     * Return the id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Return the content type
     */
    public function getContentType()
    {
        return $this->content_type;
    }

    /**
     * Return the content (if any)
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set the content type and id
     */
    private function setContentTypeAndId($content_type, $id)
    {
        $this->content_type = $content_type;
        $this->id           = $id;
    }

    /**
     * Set the content
     */
    private function setContent($content)
    {
        $this->content = $content;

        $content_type = $content->contenttype;
        if (is_array($content_type)) {      // (weird stuff)
            $content_type = $content_type['slug'];
        }

        $this->setContentTypeAndId($content_type, $content->id);
    }
}
