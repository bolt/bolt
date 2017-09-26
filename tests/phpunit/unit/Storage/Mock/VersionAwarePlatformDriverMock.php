<?php

namespace Bolt\Tests\Storage\Mock;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\VersionAwarePlatformDriver;

interface VersionAwarePlatformDriverMock extends Driver, VersionAwarePlatformDriver
{
}
