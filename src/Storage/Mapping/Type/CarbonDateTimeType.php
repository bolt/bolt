<?php

namespace Bolt\Storage\Mapping\Type;

use Bolt\Common\Deprecated;
use Bolt\Storage\Database\Schema\Types;

Deprecated::cls(CarbonDateTimeType::class, 3.3);

class_alias(Types\CarbonDateTimeType::class, CarbonDateTimeType::class);
