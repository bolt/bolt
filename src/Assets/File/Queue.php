<?php
namespace Bolt\Assets\File;

use Bolt\Assets\QueueInterface;
use Bolt\Assets\AssetInterface;
use Silex\Application;

/**
 * File asset queue processor.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Bob den Otter <bob@twokings.nl>
 */
class Queue implements QueueInterface
{
    /** @var \Silex\Application */
    private $app;
    /** @var AssetInterface[] */
    private $stylesheet = [];
    /** @var AssetInterface[] */
    private $javascript = [];

    /**
     * Constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Add a file asset to the queue.
     *
     * @param string $type
     * @param string $fileName
     * @param array  $options
     *
     * @throws \InvalidArgumentException
     */
    public function add($type, $fileName, array $options = [])
    {
        if ($type === 'javascript') {
            $this->javascript[md5($fileName)] = new JavaScript($fileName, $options);
        } elseif ($type === 'stylesheet') {
            $this->stylesheet[md5($fileName)] = new Stylesheet($fileName, $options);
        } else {
            throw new \InvalidArgumentException("Requested asset type of '$type' is not valid.");
        }
    }

    /**
     * Insert all assets in template. Use sorting by priority.
     *
     * @param $html
     *
     * @return string
     */
    public function process($html)
    {
        foreach ($this->sort($this->javascript) as $asset) {
            $html = $this->processJsAssets($asset, $html);
        }

        foreach ($this->sort($this->stylesheet) as $asset) {
            $html = $this->processCssAssets($asset, $html);
        }

        return $html;
    }

    /**
     * Get the queued snippets.
     *
     * @return \Bolt\Assets\Snippets\Snippet
     */
    public function getQueue()
    {
        return [
            'javascript' => $this->javascript,
            'stylesheet' => $this->stylesheet,
        ];
    }

    /**
     * Process the CSS asset queue.
     *
     * @param AssetInterface $asset
     * @param string         $html
     *
     * @return string
     */
    protected function processCssAssets(AssetInterface $asset, $html)
    {
        if ($asset->isLate()) {
            return $this->app['assets.injector']->bodyTagEnd($html, (string) $asset);
        } else {
            return $this->app['assets.injector']->cssTagsBefore($html, (string) $asset);
        }
    }

    /**
     * Process the JavaScript asset queue.
     *
     * @param AssetInterface $asset
     * @param string         $html
     *
     * @return string
     */
    protected function processJsAssets(AssetInterface $asset, $html)
    {
        if ($asset->isLate()) {
            return $this->app['assets.injector']->bodyTagEnd($html, (string) $asset);
        } else {
            return $this->app['assets.injector']->jsTagsAfter($html, (string) $asset);
        }
    }

    /**
     * Do a Schwartzian Transform for stable sort
     *
     * @see http://en.wikipedia.org/wiki/Schwartzian_transform
     *
     * @param AssetInterface[] $files
     *
     * @return AssetInterface[]
     */
    private function sort(array $files)
    {
        // @codingStandardsIgnoreStart
        array_walk($files, function (&$v, $k) {$v = [$v->getPriority(), $k, $v];});
        sort($files);
        array_walk($files, function (&$v, $k) {$v = $v[2];});
        // @codingStandardsIgnoreEnd

        return $files;
    }
}
