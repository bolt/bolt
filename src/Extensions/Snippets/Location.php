<?php

namespace Bolt\Extensions\Snippets;

/**
 * Bolt Snippet Locations
 * This class categorizes all possible snippet locations in constants.
 */
class Location
{
    // unpredictable
    const BEFORE_CSS = "beforecss";
    const AFTER_CSS = "aftercss";
    const BEFORE_JS = "beforejs";
    const AFTER_JS = "afterjs";
    const AFTER_META = "aftermeta";

    // main structure
    const START_OF_HEAD = "startofhead";
    const END_OF_HEAD = "endofhead";
    const START_OF_BODY = "startofbody";
    const END_OF_BODY = "endofbody";
    const END_OF_HTML = "endofhtml";
    const AFTER_HTML = "afterhtml";

    // substructure
    const BEFORE_HEAD_META = "beforeheadmeta";
    const AFTER_HEAD_META = "afterheadmeta";

    const BEFORE_HEAD_CSS = "beforeheadcss";
    const AFTER_HEAD_CSS = "afterheadcss";

    const BEFORE_HEAD_JS = "beforeheadjs";
    const AFTER_HEAD_JS = "afterheadjs";

    const BEFORE_BODY_CSS = "beforebodycss";
    const AFTER_BODY_CSS = "afterbodycss";

    const BEFORE_BODY_JS = "beforebodyjs";
    const AFTER_BODY_JS = "afterbodyjs";

    /**
     * Returns all possible locations (which are constants).
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
