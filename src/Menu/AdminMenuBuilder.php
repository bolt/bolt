<?php

namespace Bolt\Menu;

use Bolt\Translation\Translator as Trans;
use Silex\Application;

/**
 * Bolt admin (back-end) area menu builder.
 *
 * @internal Backwards compatibility not guaranteed on this class presently.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class AdminMenuBuilder
{
    /** @var MenuEntry */
    protected $rootEntry;
    /** @var MenuEntry[] */
    protected $children;

    /**
     * Constructor.
     *
     * @param MenuEntry $rootEntry
     */
    public function __construct(MenuEntry $rootEntry)
    {
        $this->rootEntry = $rootEntry;
    }

    /**
     * Build the menus.
     *
     * @param Application $app
     *
     * @return \Bolt\Menu\MenuEntry
     */
    public function build(Application $app)
    {
        return $this->rootEntry;
    }
}
