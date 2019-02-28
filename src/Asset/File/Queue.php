<?php

namespace Bolt\Asset\File;

use Bolt\Asset\AssetSortTrait;
use Bolt\Asset\Injector;
use Bolt\Asset\QueueInterface;
use Bolt\Asset\Target;
use Bolt\Config;
use Bolt\Controller\Zone;
use Symfony\Component\Asset\Packages;
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
    /** @var Packages */
    protected $packages;
    /** @var Config */
    protected $config;

    /** @var FileAssetInterface[] */
    private $stylesheet = [];
    /** @var FileAssetInterface[] */
    private $javascript = [];

    /**
     * Constructor.
     *
     * @param Injector $injector
     * @param Packages $packages
     * @param Config   $config
     */
    public function __construct(Injector $injector, Packages $packages, Config $config)
    {
        $this->injector = $injector;
        $this->packages = $packages;
        $this->config = $config;
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
        if (!$asset->getPackageName()) {
            // Deprecated.
            $asset->setPackageName('extensions');
        }

        $key = $asset->getPackageName()."/".$asset->getPath();

        if ($asset->getType() === 'javascript') {
            $this->javascript[$key] = $asset;
        } elseif ($asset->getType() === 'stylesheet') {
            $this->stylesheet[$key] = $asset;
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
        // Conditionally add jQuery
        $this->addJquery($request, $response);

        /** @var FileAssetInterface $asset */
        foreach ($this->sort($this->javascript) as $key => $asset) {
            $this->processAsset($asset, $request, $response);
            unset($this->javascript[$key]);
        }

        /** @var FileAssetInterface $asset */
        foreach ($this->sort($this->stylesheet) as $key => $asset) {
            $this->processAsset($asset, $request, $response);
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
     * Process a single asset.
     *
     * @param FileAssetInterface $asset
     * @param Request            $request
     * @param Response           $response
     */
    protected function processAsset(FileAssetInterface $asset, Request $request, Response $response)
    {
        $url = $this->packages->getUrl($asset->getPath(), $asset->getPackageName());
        $asset->setUrl($url);

        if ($asset->getZone() !== Zone::get($request)) {
            return;
        }

        if ($asset->isLate()) {
            if ($asset->getLocation() === null) {
                $location = Target::END_OF_BODY;
            } else {
                $location = $asset->getLocation();
            }
        } elseif ($asset->getLocation() !== null) {
            $location = $asset->getLocation();
        } else {
            $location = Target::END_OF_HEAD;
        }

        $this->injector->inject($asset, $location, $response);
    }

    /**
     * Insert jQuery, if it's not inserted already.
     *
     * Some of the patterns that 'match' are:
     * - jquery.js
     * - jquery.min.js
     * - jquery-latest.js
     * - jquery-latest.min.js
     * - jquery-1.8.2.min.js
     * - jquery-1.5.js
     *
     * @param Request  $request
     * @param Response $response
     */
    protected function addJquery(Request $request, Response $response)
    {
        if (!$this->config->get('general/add_jquery', false) &&
            !$this->config->get('theme/add_jquery', false)) {
            return;
        }

        // Check zone to skip expensive regex
        if (Zone::isFrontend($request) === false) {
            return;
        }

        $html = $response->getContent();
        $regex = '/<script(.*)jquery(-latest|-[0-9\.]*)?(\.min)?\.js/';
        if (preg_match($regex, $html)) {
            return;
        }

        $this->add(
            (new JavaScript('js/jquery-2.2.4.min.js', 'bolt'))
                ->setLocation(Target::BEFORE_JS)
        );
    }
}
