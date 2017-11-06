<?php

namespace Bolt\Nut;

use Bolt\Translation\Translator as Trans;
use Bolt\Version;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to perform initial setup tasks.
 */
class Init extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('init')
            ->setDescription('Greet the user (and perform initial setup tasks).')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->updateDistBundles();
        } catch (\Exception $e) {
            if ($output instanceof Output && $output->isDebug()) {
                throw $e;
            }
            $this->io->error(sprintf('There was an exception when updating the distribution bundle: %s', $e->getMessage()));
        }

        $message = sprintf(
            "<info>%s</info> - %s <comment>%s</comment>.\n",
            Trans::__('nut.greeting'),
            Trans::__('nut.version'),
            Version::VERSION
        );
        $this->io->text($message);

        return 0;
    }

    /**
     * Archive distributions have the site bundle files installed as .dist
     * files to prevent the real files being overridden. If the .dist file
     * exists, but the original doesn't then we should rename them.
     */
    private function updateDistBundles()
    {
        $fs = $this->app['filesystem']->getFilesystem('root');
        $files = [
            '.bolt.yml',
            'composer.json',
            'composer.lock',
            'src/Site/CustomisationExtension.php',
        ];
        foreach ($files as $file) {
            $dist = $fs->getFile($file . '.dist');
            if (!$fs->has($file) && $dist->exists()) {
                $dist->rename($file);
            }
        }
    }
}
