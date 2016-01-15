<?php
namespace Bolt\Asset;

/**
 * Bolt Snippet target location.
 *
 * This class categorizes all possible snippet locations in constants.
 */
class Target
{
    // unpredictable
    const BEFORE_CSS       = 'beforecss';
    const AFTER_CSS        = 'aftercss';
    const BEFORE_JS        = 'beforejs';
    const AFTER_JS         = 'afterjs';
    const AFTER_META       = 'aftermeta';

    // main structure
    const START_OF_HEAD    = 'startofhead';
    const END_OF_HEAD      = 'endofhead';
    const START_OF_BODY    = 'startofbody';
    const END_OF_BODY      = 'endofbody';
    const END_OF_HTML      = 'endofhtml';
    const AFTER_HTML       = 'afterhtml';

    // substructure
    const BEFORE_HEAD_META = 'beforeheadmeta';
    const AFTER_HEAD_META  = 'afterheadmeta';

    const BEFORE_HEAD_CSS  = 'beforeheadcss';
    const AFTER_HEAD_CSS   = 'afterheadcss';

    const BEFORE_HEAD_JS   = 'beforeheadjs';
    const AFTER_HEAD_JS    = 'afterheadjs';

    const BEFORE_BODY_CSS  = 'beforebodycss';
    const AFTER_BODY_CSS   = 'afterbodycss';

    const BEFORE_BODY_JS   = 'beforebodyjs';
    const AFTER_BODY_JS    = 'afterbodyjs';

    const WIDGET_FRONT_MAIN_TOP     = 'main_top';
    const WIDGET_FRONT_MAIN_BREAK   = 'main_break';
    const WIDGET_FRONT_MAIN_BOTTOM  = 'main_bottom';
    const WIDGET_FRONT_ASIDE_TOP    = 'aside_top';
    const WIDGET_FRONT_ASIDE_MIDDLE = 'aside_middle';
    const WIDGET_FRONT_ASIDE_BOTTOM = 'aside_bottom';
    const WIDGET_FRONT_FOOTER       = 'footer';

    const WIDGET_BACK_DASHBOARD_ASIDE_TOP      = 'dashboard_aside_top';
    const WIDGET_BACK_DASHBOARD_ASIDE_MIDDLE   = 'dashboard_aside_middle';
    const WIDGET_BACK_DASHBOARD_ASIDE_BOTTOM   = 'dashboard_aside_bottom';
    const WIDGET_BACK_DASHBOARD_BELOW_HEADER   = 'dashboard_below_header';
    const WIDGET_BACK_DASHBOARD_BOTTOM         = 'dashboard_bottom';
    const WIDGET_BACK_OVERVIEW_ASIDE_TOP       = 'overview_aside_top';
    const WIDGET_BACK_OVERVIEW_ASIDE_MIDDLE    = 'overview_aside_middle';
    const WIDGET_BACK_OVERVIEW_ASIDE_BOTTOM    = 'overview_aside_bottom';
    const WIDGET_BACK_OVERVIEW_BELOW_HEADER    = 'overview_below_header';
    const WIDGET_BACK_OVERVIEW_BOTTOM          = 'overview_bottom';
    const WIDGET_BACK_EDITCONTENT_ASIDE_TOP    = 'editcontent_aside_top';
    const WIDGET_BACK_EDITCONTENT_ASIDE_MIDDLE = 'editcontent_aside_middle';
    const WIDGET_BACK_EDITCONTENT_ASIDE_BOTTOM = 'editcontent_aside_bottom';
    const WIDGET_BACK_EDITCONTENT_BELOW_HEADER = 'editcontent_below_header';
    const WIDGET_BACK_EDITCONTENT_BOTTOM       = 'editcontent_bottom';
    const WIDGET_BACK_FILES_BELOW_HEADER       = 'files_below_header';
    const WIDGET_BACK_FILES_BOTTOM             = 'files_bottom';
    const WIDGET_BACK_EDITFILE_BELOW_HEADER    = 'editfile_below_header';
    const WIDGET_BACK_EDITFILE_BOTTOM          = 'editfile_bottom';
    const WIDGET_BACK_LOGIN_TOP                = 'login_top';
    const WIDGET_BACK_LOGIN_MIDDLE             = 'login_middle';
    const WIDGET_BACK_LOGIN_BOTTOM             = 'login_bottom';

    /**
     * Returns all possible target locations (which are constants).
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @return array
     */
    public function listAll()
    {
        $reflection = new \ReflectionClass($this);

        return $reflection->getConstants();
    }
}
