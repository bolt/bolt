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
trait DeprecatedFunctionsTrait
{
    /** @return \Silex\Application */
    abstract public function getApp();

    /**
     * Add jQuery to the output.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function addJquery()
    {
        $this->getApp()['config']->set('general/add_jquery', true);
    }

    /**
     * Don't add jQuery to the output.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function disableJquery()
    {
        $this->getApp()['config']->set('general/add_jquery', false);
    }

    /**
     * Legacy function that returns a list of all css and js assets that are
     * added via extensions.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use $app['asset.queue.file']->getQueue() and/or $app['asset.queue.snippet']->getQueue()
     *
     * @return array
     */
    public function getAssets()
    {
        $files = $this->getApp()['asset.queue.file']->getQueue();
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
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function insertWidget()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function renderWidgetHolder()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function renderWidget()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function insertSnippet($location, $callback, $extensionname = 'core', $extraparameters = [])
    {
        $this->getApp()['asset.queue.snippet']->add($location, $callback, $extensionname, (array) $extraparameters);
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function clearSnippetQueue()
    {
        $this->getApp()['asset.queue.snippet']->clear();
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function processSnippetQueue($html)
    {
        return $this->getApp()['asset.queue.snippet']->process($html);
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function processAssets($html)
    {
        return $this->getApp()['asset.queue.file']->process($html);
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function insertStartOfHead($tag, $html)
    {
        return $this->getApp()['asset.injector']->inject($tag, Target::START_OF_HEAD, $html);
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function insertStartOfBody($tag, $html)
    {
        return $this->getApp()['asset.injector']->inject($tag, Target::START_OF_BODY, $html);
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function insertEndOfHead($tag, $html)
    {
        return $this->getApp()['asset.injector']->inject($tag, Target::END_OF_HEAD, $html);
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function insertEndOfBody($tag, $html)
    {
        return $this->getApp()['asset.injector']->inject($tag, Target::END_OF_BODY, $html);
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function insertEndOfHtml($tag, $html)
    {
        return $this->getApp()['asset.injector']->inject($tag, Target::END_OF_HTML, $html);
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function insertAfterMeta($tag, $html)
    {
        return $this->getApp()['asset.injector']->inject($tag, Target::AFTER_META, $html);
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function insertAfterCss($tag, $html)
    {
        return $this->getApp()['asset.injector']->inject($tag, Target::AFTER_CSS, $html);
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function insertBeforeCss($tag, $html)
    {
        return $this->getApp()['asset.injector']->inject($tag, Target::BEFORE_CSS, $html);
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function insertBeforeJS($tag, $html)
    {
        return $this->getApp()['asset.injector']->inject($tag, Target::BEFORE_JS, $html);
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function insertAfterJs($tag, $html, $insidehead = true)
    {
        return $this->getApp()['asset.injector']->inject($tag, Target::AFTER_JS, $html, $insidehead);
    }
}
