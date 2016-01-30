<?php
namespace Bolt\Storage\Entity;

/**
 * Entity for Auth Tokens.
 */
class Relations extends Entity
{
    protected $id;
    protected $from_contenttype;
    protected $from_id;
    protected $to_contenttype;
    protected $to_id;

    private $invert = false;

    public function actAsInverse()
    {
        $this->invert = true;
    }

    public function getToId()
    {
        if ($this->invert === true) {
            return $this->from_id;
        }

        return $this->to_id;
    }
}
