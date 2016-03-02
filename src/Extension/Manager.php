<?php

namespace Bolt\Extension;

use Bolt\Composer\EventListener\PackageDescriptor;
use Bolt\Config;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\FilesystemInterface;
use Bolt\Filesystem\Handler\DirectoryInterface;
use Bolt\Filesystem\Handler\JsonFile;
use Bolt\Legacy\ExtensionsTrait;
use Bolt\Logger\FlashLoggerInterface;
use Bolt\Translation\LazyTranslator as Trans;
use Silex\Application;

/**
 * Class to manage loading of extensions.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Manager
{
    use ExtensionsTrait;

    /** @var ResolvedExtension[] */
    protected $extensions = [];
    /** @var string[] */
    protected $composerNames = [];

    /** @var FilesystemInterface */
    private $filesystem;
    /** @var FlashLoggerInterface */
    private $flashLogger;
    /** @var Config */
    private $config;
    /** @var bool */
    private $loaded = false;
    /** @var bool */
    private $registered = false;
    /** @var Application @deprecated */
    private $app;

    /**
     * Constructor.
     *
     * @param FilesystemInterface  $filesystem
     * @param FlashLoggerInterface $flashLogger
     * @param Config               $config
     */
    public function __construct(FilesystemInterface $filesystem, FlashLoggerInterface $flashLogger, Config $config)
    {
        $this->filesystem = $filesystem;
        $this->flashLogger = $flashLogger;
        $this->config = $config;
    }

    /**
     * Get all installed extensions.
     *
     * @return ResolvedExtension[]
     */
    public function all()
    {
        return $this->extensions;
    }

    /**
     * Get an installed extension class.
     *
     * @param string|null $id The extension ID or composer name
     *
     * @return ExtensionInterface|null
     */
    public function get($id)
    {
        $resolved = $this->getResolved($id);

        return $resolved ? $resolved->getInnerExtension() : null;
    }

    /**
     * Get the resolved form of an installed extension class.
     *
     * @param string|null $id The extension ID or composer name
     *
     * @return ResolvedExtension|null
     */
    public function getResolved($id)
    {
        if (isset($this->extensions[$id])) {
            return $this->extensions[$id];
        } elseif (isset($this->composerNames[$id])) {
            return $this->extensions[$this->composerNames[$id]];
        }

        return null;
    }

    /**
     * Add an extension to be registered.
     *
     * @param ExtensionInterface $extension
     * @param DirectoryInterface $baseDir
     * @param string             $relativeUrl
     * @param string|null        $composerName
     *
     * @throws \RuntimeException
     *
     * @return ResolvedExtension
     */
    public function add(ExtensionInterface $extension, DirectoryInterface $baseDir, $relativeUrl, $composerName = null)
    {
        if ($this->registered) {
            throw new \RuntimeException('Can not add extensions after they are registered.');
        }

        // Set paths in the extension
        $extension
            ->setBaseDirectory($baseDir)
            ->setRelativeUrl($relativeUrl)
        ;

        // Determine if enabled
        $enabled = $this->config->get('extensions/' . $extension->getId(), true);

        if ($composerName !== null) {
            // Map composer name to ID
            $this->composerNames[$composerName] = $extension->getId();

            // Check if enabled by composer name
            $enabled = $this->config->get("extensions/$composerName", $enabled);
        }

        // Instantiate resolved extension and mark enabled/disabled
        $resolved = (new ResolvedExtension($extension))
            ->setEnabled($enabled)
        ;

        return $this->extensions[$extension->getId()] = $resolved;
    }

    /**
     * Load a collection of extension classes.
     */
    public function addManagedExtensions()
    {
        if ($this->loaded) {
            throw new \RuntimeException('Extensions already loaded.');
        }

        // Include the extensions autoload file
        if ($this->filesystem->has('vendor/autoload.php') === false) {
            $this->loaded = true;

            return;
        }

        $this->filesystem->includeFile('vendor/autoload.php');

        $descriptors = $this->loadPackageDescriptors();
        foreach ($descriptors as $descriptor) {
            // Skip loading if marked invalid
            if ($descriptor->isValid() === false) {
                continue;
            }
            $this->addManagedExtension($descriptor);
        }

        $this->loaded = true;
    }

    /**
     * Call register() for each extension.
     *
     *
     * @param Application $app
     *
     * @throws \RuntimeException
     */
    public function register(Application $app)
    {
        if ($this->registered) {
            throw new \RuntimeException('Can not re-register extensions.');
        }
        foreach ($this->extensions as $extension) {
            if ($extension->isEnabled() !== true) {
                continue;
            }
            $extension->getInnerExtension()->setContainer($app);
            foreach ($extension->getInnerExtension()->getServiceProviders() as $provider) {
                $app->register($provider);
            }
        }
        $this->registered = true;

        // @deprecated Deprecated since 3.0, to be removed in 4.0.
        $this->app = $app;
    }

    /**
     * Load the extension autoload.json cache file and build the PackageDescriptor array.
     *
     * @return PackageDescriptor[]
     */
    private function loadPackageDescriptors()
    {
        $descriptors = [];
        try {
            /** @var JsonFile $autoload */
            $autoload = $this->filesystem->get('vendor/autoload.json');
        } catch (FileNotFoundException $e) {
            return $descriptors;
        }

        // Get extensions we're managing via the autoloader
        foreach ((array) $autoload->parse() as $name => $loader) {
            $descriptors[$name] = PackageDescriptor::create($loader);
        }

        return $descriptors;
    }

    /**
     * Load a single extension.
     *
     * @param PackageDescriptor $descriptor
     */
    private function addManagedExtension(PackageDescriptor $descriptor)
    {
        $className = $descriptor->getClass();
        if (class_exists($className) === false) {
            if ($descriptor->getType() === 'local' && class_exists('Wikimedia\Composer\MergePlugin') === false) {
                $this->flashLogger->error(Trans::__("Local extension set up incomplete. Please run 'Install all packages' on the Extensions page.", ['%NAME%' => $descriptor->getName(), '%CLASS%' => $className]));
            } else {
                $this->flashLogger->error(Trans::__("Extension package %NAME% has an invalid class '%CLASS%' and has been skipped.", ['%NAME%' => $descriptor->getName(), '%CLASS%' => $className]));
            }

            return;
        }

        /** @var ExtensionInterface $extension */
        $extension = new $className();
        if ($extension instanceof ExtensionInterface) {
            $baseDir = $this->filesystem->getDir($descriptor->getPath());
            $relativeUrl = sprintf('/extensions/%s/web/', str_replace('\\', '/', $descriptor->getPath()));
            $this->add($extension, $baseDir, $relativeUrl, $descriptor->getName())
                ->setDescriptor($descriptor)
            ;
        } else {
            $this->flashLogger->error(Trans::__("Extension package %NAME% base class '%CLASS%' does not implement \\Bolt\\Extension\\ExtensionInterface and has been skipped.", ['%NAME%' => $descriptor->getName(), '%CLASS%' => $className]));
        }
    }

    /**
     * @deprecated Deprecated since 3.0, to be removed in 4.0.
     *
     * @internal Do not use! For legacy support only.
     */
    protected function getApp()
    {
        return $this->app;
    }
}
