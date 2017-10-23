<?php

namespace Bolt\Tests\Storage\Mapping\Mock;

use Bolt\Storage\Mapping\ContentTypeTitleTrait;

class ContentTypeTitleMock
{
    use ContentTypeTitleTrait;

    public function getName($contentType)
    {
        return $this->getTitleColumnName($contentType);
    }

    public function getNames($contentType)
    {
        return $this->getTitleColumnNames($contentType);
    }
}
