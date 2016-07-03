<?php

namespace Bolt\Asset\Widget;

use Bolt\Asset\AssetInterface;

/**
 * Widget assets interface.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface WidgetAssetInterface extends AssetInterface, \ArrayAccess
{
    /**
     * Get the wiget's key.
     *
     * @return string|null
     */
    public function getKey();

    /**
     * Set the widget (semi-) unique key.
     *
     * @internal
     *
     * @return WidgetAssetInterface
     */
    public function setKey();

    /**
     * Get the optional content provider callback function.
     *
     * callable|null
     */
    public function getCallback();

    /**
     * Set/clear the optional content provider callback function.
     *
     * @param callable $callback
     *
     * @return WidgetAssetInterface
     */
    public function setCallback(callable $callback);

    /**
     * Get the callback function arguments.
     *
     * @return array|null
     */
    public function getCallbackArguments();

    /**
     * Set/clear the arguments to pass to callback.
     *
     * @param array $callbackArguments
     *
     * @return WidgetAssetInterface
     */
    public function setCallbackArguments(array $callbackArguments);

    /**
     * Get the static HTML content for the widget.
     *
     * @return string|null
     */
    public function getContent();

    /**
     * Set the static HTML content for the widget.
     *
     * @param string $content
     *
     * @return WidgetAssetInterface
     */
    public function setContent($content);

    /**
     * Get the HTML class to use for the widget's holder div.
     *
     * @return string|null
     */
    public function getClass();

    /**
     * Set the HTML class to use for the widget's holder div.
     *
     * @param string $class
     *
     * @return WidgetAssetInterface
     */
    public function setClass($class);

    /**
     * Get the HTML to be applied before the widget's output.
     *
     * @return string|null
     */
    public function getPrefix();

    /**
     * Set the HTML to be applied before the widget's output.
     *
     * @param string $prefix
     *
     * @return WidgetAssetInterface
     */
    public function setPrefix($prefix);

    /**
     * Get the HTML to be applied after the widget's output.
     *
     * @return string|null
     */
    public function getPostfix();

    /**
     * Set the HTML to be applied after the widget's output.
     *
     * @param string $postfix
     *
     * @return WidgetAssetInterface
     */
    public function setPostfix($postfix);

    /**
     * Check if the widget is set to be lazy loaded.
     *
     * @return boolean
     */
    public function isDeferred();

    /**
     * Set/unset the lazy loading flag fo the widget content.
     *
     * @param boolean $defer
     *
     * @return WidgetAssetInterface
     */
    public function setDefer($defer);

    /**
     * Get the widget's render priroity in the target position.
     *
     * @return integer
     */
    public function getPriority();

    /**
     * Set the widget's render priroity in the target position.
     *
     * @param integer $priority
     *
     * @return WidgetAssetInterface
     */
    public function setPriority($priority);

    /**
     * Get the number of seconds to cache the widgets content.
     *
     * @return integer
     */
    public function getCacheDuration();

    /**
     * Set the number of seconds to cache the widgets content.
     *
     * @param integer $cacheDuration
     *
     * @return WidgetAssetInterface
     */
    public function setCacheDuration($cacheDuration);
}
