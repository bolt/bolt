<?php
namespace Bolt\Tests\Cache;

class FooObject
{
    private $value;

    public function __construct($value = 'bar')
    {
        $this->value = $value;
    }
}
