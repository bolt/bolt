<?php
namespace Bolt\Assets\File;

use Silex\Application;

/**
 * File asset queue processor.
 *
 * @author Gawain Lynch <gawain.lynch@gmaill.com>
 * @author Bob den Otter <bob@twokings.nl>
 */
class Queue
{
    /** @var \Silex\Application */
    private $app;
    /** @var AssetBase[] */
    private $stylesheet = [];
    /** @var AssetBase[] */
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

    protected function processCssAssets(AssetBase $asset, $html)
    {
        if ($asset->isLate()) {
            $html = $this->app['assets.injector']->bodyTagEnd($html, (string) $asset);
        } else {
            $html = $this->app['assets.injector']->cssTagsBefore($html, (string) $asset);
        }

        return $html;
    }

    protected function processJsAssets(AssetBase $asset, $html)
    {
        if ($asset->isLate()) {
            $html = $this->app['assets.injector']->bodyTagEnd($html, (string) $asset);
        } else {
            $html = $this->app['assets.injector']->jsTagsAfter($html, (string) $asset);
        }

        return $html;
    }

    /**
     * Do a Schwartzian Transform for stable sort
     *
     * @see http://en.wikipedia.org/wiki/Schwartzian_transform
     *
     * @param array $files
     *
     * @return array
     */
    private function sort(array $files)
    {
        // We use create_function(), because it's faster than closure decorate
        array_walk($files, create_function('&$v, $k', '$v = [$v[\'priority\'], $k, $v];'));
        // sort
        sort($files);
        // undecorate
        array_walk($files, create_function('&$v, $k', '$v = $v[2];'));

        return $files;
    }
}
