<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to set up extension directories.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class ExtensionsSetup extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('extensions:setup')
            ->setDescription('Set up extension directories, composer.json and required dependencies.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->setupJson($output);
        $this->setupLocal($output);
        $this->setupAutoloader($output);
    }

    /**
     * Create or update the extensions/composer.json file.
     *
     * @param OutputInterface $output
     */
    private function setupJson(OutputInterface $output)
    {
        $output->write("\n<info>Creating/updating composer.json… </info>");
        $this->app['extend.manager.json']->update();
        $output->write("<info>[DONE]</info>\n");
    }

    /**
     * Set up local extension dependencies.
     *
     * @param OutputInterface $output
     */
    private function setupLocal(OutputInterface $output)
    {
        $hasLocal = $this->app['filesystem']->getFilesystem('extensions')->has('local');
        if ($hasLocal === false) {
            return;
        }
        $output->write("\n<info>Installing merge plugin for local extension support… </info>");
        $result = $this->app['extend.manager']->requirePackage(['name' => 'wikimedia/composer-merge-plugin', 'version' => '^1.3']);
        $this->outputResult($output, $result);
    }

    /**
     * Set up the Composer autoloader.
     *
     * @param OutputInterface $output
     *
     * @throws \Bolt\Exception\PackageManagerException
     */
    private function setupAutoloader(OutputInterface $output)
    {
        $output->write("\n<info>Updating autoloaders… </info>");
        $result = $this->app['extend.action']['autoload']->execute();
        $this->outputResult($output, $result);
    }

    /**
     * Output the relevant result.
     *
     * @param OutputInterface $output
     * @param int             $result
     */
    private function outputResult(OutputInterface $output, $result)
    {
        if ($result === 0) {
            $output->write("<info>[DONE]</info>\n");
        } else {
            $output->write("<error>[FAILED]</error>\n");
        }

        $output->writeln(sprintf('<comment>%s</comment>', $this->app['extend.action.io']->getOutput()), OutputInterface::OUTPUT_PLAIN);
    }
}
