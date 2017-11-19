<?php

namespace Bolt\Composer\Script;

use Bolt\Collection\MutableBag;
use Bolt\Nut\Output\NutStyleInterface;
use Bolt\Nut\Style\NutStyle;
use Bundle\Site\CustomisationExtension;
use Composer\Script\Event;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Configures project default site bundle.
 *
 * This should only be used for new projects.
 *
 * @internal
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
final class BundleConfigurator
{
    const FILENAME = '.bolt.yml';

    /** @var NutStyleInterface */
    private $io;
    /** @var Filesystem */
    private $filesystem;

    /**
     * Constructor.
     *
     * @param NutStyleInterface $io
     * @param Filesystem|null   $filesystem
     */
    public function __construct(NutStyleInterface $io, Filesystem $filesystem = null)
    {
        $this->io = $io;
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * Create from a Composer event object.
     *
     * @param Event $event
     *
     * @return BundleConfigurator
     */
    public static function fromEvent(Event $event)
    {
        $io = NutStyle::fromComposer($event->getIO());

        return new static($io);
    }

    /**
     * Setup .bolt.yml file if needed.
     */
    public function run()
    {
        if (!class_exists(CustomisationExtension::class)) {
            return;
        }
        $contents = $this->load();

        $this->updateSiteBundleLoader($contents);

        if (!$contents->isEmpty()) {
            $this->save($contents);
        }
    }

    /**
     * Load data from .bolt.yml if it exists.
     *
     * @return MutableBag
     */
    private function load()
    {
        if (file_exists(static::FILENAME)) {
            return MutableBag::from(Yaml::parse(file_get_contents(static::FILENAME)) ?: []);
        }

        return MutableBag::of();
    }

    /**
     * Save updated data .bolt.yml file, or remove if it matches defaults.
     *
     * @param MutableBag $contents
     */
    private function save(MutableBag $contents)
    {
        try {
            $this->filesystem->dumpFile(static::FILENAME, Yaml::dump($contents->toArray()));
            $this->io->success('Added the site bundle to .bolt.yml');
        } catch (IOException $e) {
            $this->io->error('Unable to add the site bundle to .bolt.yml');
        }
    }

    /**
     * Add the default site bundle if it exists.
     *
     * @param MutableBag $contents
     */
    private function updateSiteBundleLoader(MutableBag $contents)
    {
        if (!$this->io->confirm('Would you like to enable the default site Bundle?')) {
            return;
        }

        $extensions = (array) $contents->get('extensions', []);
        foreach ($extensions as $extension) {
            if (is_a($extension, CustomisationExtension::class, true)) {
                // If there is already a valid entry, exit
                return;
            }
        }
        // Add the extension to the bag
        $extensions[] = CustomisationExtension::class;
        $contents->set('extensions', $extensions);
    }
}
