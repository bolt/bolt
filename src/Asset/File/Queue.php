<?php
namespace Bolt\Asset\File;

use Bolt\Asset\AssetSortTrait;
use Bolt\Asset\Injector;
use Bolt\Asset\QueueInterface;
use Bolt\Asset\Target;
use Closure;
use Doctrine\Common\Cache\CacheProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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

    /** @var FileAssetInterface[] */
    private $stylesheet = [];
    /** @var FileAssetInterface[] */
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
     * @param FileAssetInterface $asset
     *
     * @throws \InvalidArgumentException
     */
    public function add(FileAssetInterface $asset)
    {
        $fileHasher = $this->fileHasher;
        $cacheHash = $fileHasher($asset->getFileName());
        $asset->setCacheHash($cacheHash);

        if ($asset->getType() === 'javascript') {
            $this->javascript[$cacheHash] = $asset;
        } elseif ($asset->getType() === 'stylesheet') {
            $this->stylesheet[$cacheHash] = $asset;
        } else {
            throw new \InvalidArgumentException(sprintf('Requested asset type %s is not valid.', $asset->getType()));
        }
    }

    /**
     * {@inheritdoc}
     *
     * Uses sorting by priority.
     */
    public function process(Request $request, Response $response)
    {
        /** @var FileAssetInterface $asset */
        foreach ($this->sort($this->javascript) as $key => $asset) {
            $this->processJsAssets($asset, $response);
            unset($this->javascript[$key]);
        }

        /** @var FileAssetInterface $asset */
        foreach ($this->sort($this->stylesheet) as $key => $asset) {
            $this->processCssAssets($asset, $response);
            unset($this->stylesheet[$key]);
        }
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
     * @param FileAssetInterface $asset
     * @param Response           $response
     */
    protected function processCssAssets(FileAssetInterface $asset, Response $response)
    {
        if ($asset->isLate()) {
            $this->injector->inject($asset, Target::END_OF_BODY, $response);
        } else {
            $this->injector->inject($asset, Target::BEFORE_CSS, $response);
        }
    }

    /**
     * Process the JavaScript asset queue.
     *
     * @param FileAssetInterface $asset
     * @param Response           $response
     */
    protected function processJsAssets(FileAssetInterface $asset, Response $response)
    {
        if ($asset->isLate()) {
            $this->injector->inject($asset, Target::END_OF_BODY, $response);
        } else {
            $this->injector->inject($asset, Target::AFTER_JS, $response);
        }
    }
}
