<?php

namespace Bolt\Legacy;

use Bolt;
use Bolt\Asset\Snippet\Snippet;
use Bolt\Asset\Target;
use Silex\Application;

/**
 * Deprecated Bolt\Extensions class.
 *
 * Farewell little functions, and rest in peace…
 *
 * @deprecated Deprecated since 3.0, to be removed in 4.0.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
trait ExtensionsTrait
{
    /** @return \Silex\Application */
    abstract protected function getApp();

    /**
     * Legacy function that returns a list of all css and js assets that are
     * added via extensions.
     *
     * @deprecated Deprecated since 3.0, to be removed in 4.0. Use $app['asset.queue.file']->getQueue() and/or
     *             $app['asset.queue.snippet']->getQueue()
     *
     * @return array
     */
    public function getAssets()
    {
        $files = $this->getApp()['asset.queue.file']->getQueue();
        $assets = [
            'css' => [],
            'js'  => [],
        ];

        /** @var \Bolt\Asset\File\JavaScript $file */
        foreach ($files['javascript'] as $file) {
            $assets['js'][] = $file->getFileName();
        }
        /** @var \Bolt\Asset\File\Stylesheet $file */
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
    public function insertSnippet($location, $callback, $extensionName = 'core', $callbackArguments = [])
    {
        $snippet = (new Snippet())
            ->setLocation($location)
            ->setCallback($callback)
            ->setExtension($extensionName)
            ->setCallbackArguments($callbackArguments)
        ;

        $this->getApp()['asset.queue.snippet']->add($snippet);
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
    public function insertAfterJs($tag, $html)
    {
        return $this->getApp()['asset.injector']->inject($tag, Target::AFTER_JS, $html);
    }

    /**
     * Placeholder functions
     */

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function addCss()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function addJavascript()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function addJquery()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function addMenuOption()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function addTwigExtension()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function autoload()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function checkLocalAutoloader()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function clearAssets()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function disableJquery()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function getComposerConfig()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function getEnabled()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function getMenuOptions()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function getSnippets()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function hasMailSenders()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function hasMenuOptions()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function initialize()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function isEnabled($name)
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function localload()
    {
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     */
    public function register()
    {
    }
}
