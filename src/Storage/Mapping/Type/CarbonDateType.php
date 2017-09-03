<?php

namespace Bolt\Storage\Mapping\Type;

use Bolt\Common\Deprecated;
use Bolt\Storage\Database\Schema\Types;

Deprecated::cls(CarbonDateType::class, 3.3);

class_alias(Types\CarbonDateType::class, CarbonDateType::class);
