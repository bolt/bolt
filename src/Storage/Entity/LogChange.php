<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for change logs.
 *
 * @method integer   getId()
 * @method \DateTime getDate()
 * @method integer   getOwnerid()
 * @method string    getTitle()
 * @method string    getContenttype()
 * @method integer   getContentid()
 * @method string    getMutationType()
 * @method array     getDiff()
 * @method string    getComment()
 * @method setId($id)
 * @method setDate(\DateTime $date)
 * @method setOwnerid($ownerid)
 * @method setTitle($title)
 * @method setContenttype($contenttype)
 * @method setContentid($contentid)
 * @method setMutationType($mutationType)
 * @method setDiff($diff)
 * @method setComment($comment)
 */
class LogChange extends Entity
{
    protected $id;
    protected $date;
    protected $ownerid;
    protected $title;
    protected $contenttype;
    protected $contentid;
    protected $mutationType;
    protected $diff;
    protected $comment;
}
