<?php

namespace Bolt\Nut;

use Bolt\Configuration\YamlUpdater;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Handler\FileInterface;
use Exception;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Config command base class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
abstract class AbstractConfig extends BaseCommand
{
    /** @var FileInterface */
    protected $file;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->addArgument('key', InputArgument::REQUIRED, 'The key you wish to get')
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Specify config file to use', 'config://config.yml')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fileName = $input->getOption('file');
        if (strpos($fileName, '://') === false) {
            $fileName = 'config://' . $fileName;
        }

        try {
            $this->file = $this->app['filesystem']->get($fileName);

            $updater = new YamlUpdater($this->file);

            $this->doExecute($updater, $input, $output);
        } catch (Exception $e) {
            if ($e instanceof FileNotFoundException) {
                $this->io->error($e->getMessage());
                $this->io->error("Can't read file: $fileName.");
            } elseif ($e instanceof ParseException) {
                $this->io->error(sprintf('Invalid YAML in file: %s.', $this->file->getFullPath()));
            } elseif ($e instanceof InvalidArgumentException) {
                $this->io->error($e->getMessage());
            } else {
                throw $e;
            }

            return 1;
        }

        return 0;
    }

    /**
     * @param YamlUpdater     $updater
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    abstract protected function doExecute(YamlUpdater $updater, InputInterface $input, OutputInterface $output);
}
