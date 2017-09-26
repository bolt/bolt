<?php

namespace Bolt\Tests\Storage\Mock;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\ServerInfoAwareConnection;

interface ServerInfoAwareConnectionMock extends Connection, ServerInfoAwareConnection
{
}
