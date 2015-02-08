<?php

namespace Bolt\Composer\Action;

use Bolt\Exception\PackageManagerException;
use Composer\Installer;
use Composer\Json\JsonFile;
use Composer\Json\JsonManipulator;
use Composer\Package\Version\VersionParser;
use Composer\Repository\CompositeRepository;
use Composer\Repository\PlatformRepository;
use Silex\Application;

/**
 * Composer require package class
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class RequirePackage
{
    /**
     * @var Silex\Application
     */
    private $app;

    /**
     * @param $app Silex\Application
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Require (install) packages
     *
     * @param  $packages array Associative array of package names/versions to remove
     *                         Format: array('name' => '', 'version' => '')
     * @return integer 0 on success or a positive error code on failure
     */
    public function execute(array $packages)
    {
        $composer = $this->app['extend.manager']->getComposer();
        $io = $this->app['extend.manager']->getIO();
        $options = $this->app['extend.manager']->getOptions();

        $file = $options['composerjson'];

        $newlyCreated = !file_exists($file);

        if (!file_exists($file) && !file_put_contents($file, "{\n}\n")) {
            // JSON could not be created
            return 1;
        }
        if (!is_readable($file)) {
            // JSON is not readable
            return 1;
        }
        if (!is_writable($file)) {
            // JSON is not writable
            return 1;
        }

        $json = new JsonFile($options['composerjson']);
        $composerDefinition = $json->read();
        $composerBackup = file_get_contents($json->getPath());

        $repos = $composer->getRepositoryManager()->getRepositories();

        $this->repos = new CompositeRepository(
            array_merge(
                array(new PlatformRepository()),
                $repos
            )
        );

        $requireKey = $options['dev'] ? 'require-dev' : 'require';
        $removeKey = $options['dev'] ? 'require' : 'require-dev';

        $baseRequirements = array_key_exists($requireKey, $composerDefinition) ? $composerDefinition[$requireKey] : array();
        $packages = $this->formatRequirements($packages);
        $sortPackages = $options['sortpackages'];

        // Validate requirements format
        $versionParser = new VersionParser();
        foreach ($packages as $constraint) {
            $versionParser->parseConstraints($constraint);
        }

        if (!$this->updateFileCleanly($json, $baseRequirements, $packages, $requireKey, $removeKey, $sortPackages)) {
            foreach ($packages as $package => $version) {
                $baseRequirements[$package] = $version;

                if (isset($composerDefinition[$removeKey][$package])) {
                    unset($composerDefinition[$removeKey][$package]);
                }
            }

            $composerDefinition[$requireKey] = $baseRequirements;
            $json->write($composerDefinition);
        }

        // JSON file has been created/updated, if we're not installing, exit
        if ($options['noupdate']) {
            return 0;
        }

        // Reload Composer config
        $composer = $this->app['extend.manager']->getFactory()->resetComposer();

        $install = Installer::create($io, $composer);

        try {
            $install
                ->setVerbose($options['verbose'])
                ->setPreferSource($options['prefersource'])
                ->setPreferDist($options['preferdist'])
                ->setDevMode(!$options['updatenodev'])
                ->setUpdate($options['update'])
                ->setUpdateWhitelist(array_keys($packages))
                ->setWhitelistDependencies($options['updatewithdependencies'])
                ->setIgnorePlatformRequirements($options['ignoreplatformreqs']);

            $status = $install->run();
            if ($status !== 0) {
                if ($newlyCreated) {
                    // Installation failed, deleting JSON
                    unlink($json->getPath());
                } else {
                    // Installation failed, reverting JSON to its original content
                    file_put_contents($json->getPath(), $composerBackup);
                }
            }

            return $status;
        } catch (\Exception $e) {
            // Installation failed, reverting JSON to its original content
            file_put_contents($json->getPath(), $composerBackup);

            $msg = __CLASS__ . '::' . __FUNCTION__ . ' recieved an error from Composer: ' . $e->getMessage() . ' in ' . $e->getFile() . '::' . $e->getLine();
            $this->app['logger.system']->addCritical($msg, array('event' => 'exception', 'exception' => $e));
            throw new PackageManagerException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     *
     * @param  array $packages
     * @return array
     */
    protected function formatRequirements(array $packages)
    {
        $requires = array();
        $packages = $this->normalizeRequirements($packages);
        foreach ($packages as $package) {
            $requires[$package['name']] = $package['version'];
        }

        return $requires;
    }

    /**
     * Parses a name/version pairs and returns an array of pairs
     *
     * @param  array   $packages a set of package/version pairs separated by ":", "=" or " "
     * @return array[] array of arrays containing a name and (if provided) a version
     */
    protected function normalizeRequirements(array $packages)
    {
        $parser = new VersionParser();

        return $parser->parseNameVersionPairs($packages);
    }

    /**
     * Cleanly update a Composer JSON file
     *
     * @param  \Composer\Json\JsonFile $json
     * @param  array                   $base
     * @param  array                   $new
     * @param  string                  $requireKey
     * @param  string                  $removeKey
     * @param  boolean                 $sortPackages
     * @return boolean
     */
    private function updateFileCleanly(\Composer\Json\JsonFile $json, array $base, array $new, $requireKey, $removeKey, $sortPackages)
    {
        $contents = file_get_contents($json->getPath());

        $manipulator = new JsonManipulator($contents);

        foreach ($new as $package => $constraint) {
            if (!$manipulator->addLink($requireKey, $package, $constraint, $sortPackages)) {
                return false;
            }
            if (!$manipulator->removeSubNode($removeKey, $package)) {
                return false;
            }
        }

        file_put_contents($json->getPath(), $manipulator->getContents());

        return true;
    }
}
