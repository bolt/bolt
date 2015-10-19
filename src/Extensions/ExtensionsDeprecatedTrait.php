<?php

namespace Bolt\Extensions;

use Bolt;
use Bolt\Asset\Target;

/**
 * Deprecated function dump for Bolt\Extensions class.
 *
 * Farewell little functions, and rest in peace…
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait ExtensionsDeprecatedTrait
{
    /**
     * Add jQuery to the output.
     *
     * @deprecated Since 2.3 will be removed in Bolt 3.0
     */
    public function addJquery()
    {
        $this->app['config']->set('general/add_jquery', true);
    }

    /**
     * Don't add jQuery to the output.
     *
     * @deprecated Since 2.3 will be removed in Bolt 3.0
     */
    public function disableJquery()
    {
        $this->app['config']->set('general/add_jquery', false);
    }

    /**
     * Legacy function that returns a list of all css and js assets that are
     * added via extensions.
     *
     * @deprecated Use $app['asset.queue.file']->getQueue() and/or $app['asset.queue.snippet']->getQueue()
     *
     * @return array
     */
    public function getAssets()
    {
        $files = $this->app['asset.queue.file']->getQueue();
        $assets = [
            'css' => [],
            'js'  => []
        ];

        foreach ($files['javascript'] as $file) {
            $assets['js'][] = $file->getFileName();
        }
        foreach ($files['stylesheet'] as $file) {
            $assets['css'][] = $file->getFileName();
        }

        return $assets;
    }

    /**
     * @deprecated since 2.3 and will removed in Bolt 3.
     */
    public function insertWidget()
    {
    }

    /**
     * @deprecated since 2.3 and will removed in Bolt 3.
     */
    public function renderWidgetHolder()
    {
    }

    /**
     * @deprecated since 2.3 and will removed in Bolt 3.
     */
    public function renderWidget()
    {
    }

    /**
     * @deprecated since 2.3 and will removed in Bolt 3.
     */
    public function insertSnippet($location, $callback, $extensionname = 'core', $extraparameters = [])
    {
        $this->app['asset.queue.snippet']->add($location, $callback, $extensionname, (array) $extraparameters);
    }

    /**
     * @deprecated since 2.3 and will removed in Bolt 3.
     */
    public function clearSnippetQueue()
    {
        $this->app['asset.queue.snippet']->clear();
    }

    /**
     * @deprecated since 2.3 and will removed in Bolt 3.
     */
    public function processSnippetQueue($html)
    {
        return $this->app['asset.queue.snippet']->process($html);
    }

    /**
     * @deprecated since 2.3 and will removed in Bolt 3.
     */
    public function processAssets($html)
    {
        return $this->app['asset.queue.file']->process($html);
    }

    /**
     * @deprecated since 2.3 will be removed in 3.0
     */
    public function insertStartOfHead($tag, $html)
    {
        return $this->app['asset.injector']->inject($tag, Target::START_OF_HEAD, $html);
    }

    /**
     * @deprecated since 2.3 will be removed in 3.0
     */
    public function insertStartOfBody($tag, $html)
    {
        return $this->app['asset.injector']->inject($tag, Target::START_OF_BODY, $html);
    }

    /**
     * @deprecated since 2.3 will be removed in 3.0
     */
    public function insertEndOfHead($tag, $html)
    {
        return $this->app['asset.injector']->inject($tag, Target::END_OF_HEAD, $html);
    }

    /**
     * @deprecated since 2.3 will be removed in 3.0
     */
    public function insertEndOfBody($tag, $html)
    {
        return $this->app['asset.injector']->inject($tag, Target::END_OF_BODY, $html);
    }

    /**
     * @deprecated since 2.3 will be removed in 3.0
     */
    public function insertEndOfHtml($tag, $html)
    {
        return $this->app['asset.injector']->inject($tag, Target::END_OF_HTML, $html);
    }

    /**
     * @deprecated since 2.3 will be removed in 3.0
     */
    public function insertAfterMeta($tag, $html)
    {
        return $this->app['asset.injector']->inject($tag, Target::AFTER_META, $html);
    }

    /**
     * @deprecated since 2.3 will be removed in 3.0
     */
    public function insertAfterCss($tag, $html)
    {
        return $this->app['asset.injector']->inject($tag, Target::AFTER_CSS, $html);
    }

    /**
     * @deprecated since 2.3 will be removed in 3.0
     */
    public function insertBeforeCss($tag, $html)
    {
        return $this->app['asset.injector']->inject($tag, Target::BEFORE_CSS, $html);
    }

    /**
     * @deprecated since 2.3 will be removed in 3.0
     */
    public function insertBeforeJS($tag, $html)
    {
        return $this->app['asset.injector']->inject($tag, Target::BEFORE_JS, $html);
    }

    /**
     * @deprecated since 2.3 will be removed in 3.0
     */
    public function insertAfterJs($tag, $html, $insidehead = true)
    {
        return $this->app['asset.injector']->inject($tag, Target::AFTER_JS, $html, $insidehead);
    }
}
