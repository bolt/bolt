<?php

namespace Bolt\Tests\Storage\Mock;

use Doctrine\DBAL\Driver\ResultStatement;

interface DriverResultStatementMock extends ResultStatement, \IteratorAggregate
{
}
