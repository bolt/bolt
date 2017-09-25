<?php

namespace Bolt\Tests\Storage\Mock;

use Doctrine\DBAL\Driver\Statement;

interface DriverStatementMock extends Statement, \IteratorAggregate
{
}
