<?php
namespace Bolt\Asset\File;

use Bolt\Asset\AssetSortTrait;
use Bolt\Asset\Injector;
use Bolt\Asset\QueueInterface;
use Bolt\Asset\Target;
use Closure;
use Doctrine\Common\Cache\CacheProvider;

/**
 * File asset queue processor.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Bob den Otter <bob@twokings.nl>
 */
class Queue implements QueueInterface
{
    use AssetSortTrait;

    /** @var \Bolt\Asset\Injector */
    protected $injector;
    /** @var \Doctrine\Common\Cache\CacheProvider */
    protected $cache;
    /** @var \Closure */
    protected $fileHasher;

    /** @var FileAssetBase[] */
    private $stylesheet = [];
    /** @var FileAssetBase[] */
    private $javascript = [];

    /**
     * Constructor.
     *
     * @param Injector      $injector
     * @param CacheProvider $cache
     * @param Closure       $fileHasher
     */
    public function __construct(Injector $injector, CacheProvider $cache, Closure $fileHasher)
    {
        $this->injector = $injector;
        $this->cache = $cache;
        $this->fileHasher = $fileHasher;
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
        $fileHasher = $this->fileHasher;
        $cacheHash = $fileHasher($fileName);

        if ($type === 'javascript') {
            $this->javascript[$cacheHash] = new JavaScript($fileName, $cacheHash, $options);
        } elseif ($type === 'stylesheet') {
            $this->stylesheet[$cacheHash] = new Stylesheet($fileName, $cacheHash, $options);
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
        foreach ($this->sort($this->javascript) as $key => $asset) {
            $html = $this->processJsAssets($asset, $html);
            unset($this->javascript[$key]);
        }

        foreach ($this->sort($this->stylesheet) as $key => $asset) {
            $html = $this->processCssAssets($asset, $html);
            unset($this->stylesheet[$key]);
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
            return $this->injector->inject($asset, Target::END_OF_BODY, $html);
        } else {
            return $this->injector->inject($asset, Target::BEFORE_CSS, $html);
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
            return $this->injector->inject($asset, Target::END_OF_BODY, $html);
        } else {
            return $this->injector->inject($asset, Target::AFTER_JS, $html);
        }
    }
}
