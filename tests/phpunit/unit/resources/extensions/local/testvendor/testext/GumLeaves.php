<?php
namespace Bolt\Extensions\TestVendor\TestExt;

class GumLeaves
{
    protected $dropBear = 'Koala Power!';

    public function __construct()
    {
    }

    public function getDropBear()
    {
        return $this->dropBear;
    }
}
