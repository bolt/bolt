<?php
namespace Bolt\Asset\Snippet;

use Bolt\Asset\AssetInterface;

/**
 * Snippet asset interface.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
interface SnippetAssetInterface extends AssetInterface
{
    /**
     * Get callback or HTML string.
     *
     * @return callable|string
     */
    public function getCallback();

    /**
     * Set callback or HTML string.
     *
     * @param callable|string $callback
     *
     * @return Snippet
     */
    public function setCallback($callback);

    /**
     * Get the callback arguments.
     *
     * @return array
     */
    public function getCallbackArguments();

    /**
     * Set the callback arguments.
     *
     * @param array $callbackArguments
     *
     * @return Snippet
     */
    public function setCallbackArguments($callbackArguments);
}
