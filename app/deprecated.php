<?php

/**
 * Autoloaded file to handle deprecation such as class aliases.
 *
 * NOTE:
 * If for any reason arrays are required in this file, only ever use array()
 * syntax to prevent breakage on PHP < 5.4 and allow the legacy warnings.
 */

// Class aliases for BC
class_alias('\Bolt\Asset\Target', '\Bolt\Extensions\Snippets\Location');
class_alias('\Bolt\Legacy\Content', '\Bolt\Content');
class_alias('\Bolt\Legacy\Storage', '\Bolt\Storage');
class_alias('\Bolt\Storage\Field\Base', '\Bolt\Field\Base');
class_alias('\Bolt\Storage\Field\FieldInterface', '\Bolt\Field\FieldInterface');
class_alias('\Bolt\Storage\Field\Manager', '\Bolt\Field\Manager');
