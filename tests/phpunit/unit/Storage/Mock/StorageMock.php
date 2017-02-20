<?php

namespace Bolt\Tests\Storage\Mock;

use Bolt\Legacy\Storage;

class StorageMock extends Storage
{
    public $queries = [];

    protected function tableExists($name)
    {
        return true;
    }

    protected function executeGetContentQueries($decoded)
    {
        $this->queries[] = $decoded;

        return parent::executeGetContentQueries($decoded);
    }
}
