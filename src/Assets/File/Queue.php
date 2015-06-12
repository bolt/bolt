<?php
namespace Bolt\Assets\File;

use Bolt\Assets\QueueInterface;
use Bolt\Assets\Target;
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
    /** @var FileAssetBase[] */
    private $stylesheet = [];
    /** @var FileAssetBase[] */
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
     * {@inheritdoc}
     *
     * Uses sorting by priority.
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
     * {@inheritdoc}
     */
    public function getQueue()
    {
        return [
            'javascript' => $this->javascript,
            'stylesheet' => $this->stylesheet,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        $this->stylesheet = [];
        $this->javascript = [];
    }

    /**
     * Process the CSS asset queue.
     *
     * @param FileAssetBase $asset
     * @param string        $html
     *
     * @return string
     */
    protected function processCssAssets(FileAssetBase $asset, $html)
    {
        if ($asset->isLate()) {
            return $this->app['assets.injector']->inject($asset, Target::END_OF_BODY, $html);
        } else {
            return $this->app['assets.injector']->inject($asset, Target::BEFORE_CSS, $html);
        }
    }

    /**
     * Process the JavaScript asset queue.
     *
     * @param FileAssetBase $asset
     * @param string        $html
     *
     * @return string
     */
    protected function processJsAssets(FileAssetBase $asset, $html)
    {
        if ($asset->isLate()) {
            return $this->app['assets.injector']->inject($asset, Target::END_OF_BODY, $html);
        } else {
            return $this->app['assets.injector']->inject($asset, Target::AFTER_JS, $html);
        }
    }

    /**
     * Do a Schwartzian Transform for stable sort
     *
     * @see http://en.wikipedia.org/wiki/Schwartzian_transform
     *
     * @param FileAssetBase[] $files
     *
     * @return FileAssetBase[]
     */
    private function sort(array $files)
    {
        array_walk($files, function (&$v, $k) {
            $v = [$v->getPriority(), $k, $v];
        });

        sort($files);

        array_walk($files, function (&$v) {
            $v = $v[2];
        });

        return $files;
    }
}
