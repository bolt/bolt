<?php
/**
 * Bolt Snippet Locations
 *
 * PHP Version 5.3
 *
 * @license   http://www.opensource.org/licenses/mit-license.php MIT
 * @link      http://bolt.cm
 */
namespace Bolt\Extensions\Snippets;

/**
 * This class categorizes all possible snippet locations in constants.
 *
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link    http://bolt.cm
 */
class Location
{
    const AFTER_CSS = "aftercss";
    const AFTER_JS = "afterjs";
    const AFTER_META = "aftermeta";
    const AFTER_HTML = "afterhtml";

    const BEFORE_CSS = "beforecss";
    const BEFORE_JS = "beforejs";

    const END_OF_BODY = "endofbody";
    const END_OF_HEAD = "endofhead";
    const END_OF_HTML = "endofhtml";

    const START_OF_HEAD = "startofhead";
    const START_OF_BODY = "startofbody";

    /**
     * Returns all possible locations (which are constants)
     *
     * @return array
     */
    public function listAll()
    {
        // use reflection for this
        $reflection = new \ReflectionClass($this);

        return $reflection->getConstants();
    }
}
