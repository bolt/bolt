<?php

namespace Bolt\Nut;

use Bolt\Configuration\YamlUpdater;
use Bolt\Exception\FilesystemException;
use Bolt\Filesystem\Exception\FileNotFoundException;
use Bolt\Filesystem\Handler\YamlFile;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Nut command to get a value in config.yml
 */
class ConfigGet extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:get')
            ->setDescription('Get a value from config.yml.')
            ->addArgument('key', InputArgument::REQUIRED, 'The key you wish to get.')
            ->addOption('file', 'f', InputOption::VALUE_OPTIONAL, 'Specify config file to use', 'config://config.yml')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $input->getArgument('key');
        $file = $this->getFile($input);

        try {
            $yaml = new YamlUpdater($this->app, $file);
            $match = $yaml->get($key);

            if (null !== $match) {
                if (is_bool($match)) {
                    $match = $match ? 'true' : 'false';
                }
                $result = sprintf('%s: %s', $key, $match);
            } else {
                $result = sprintf("<error>The key '%s' was not found in %s.</error>", $key, $file->getFilename());
            }
        } catch (FileNotFoundException $e) {
            $result = sprintf("<error>Can't read file: %s.</error>", $file->getFilename());
        } catch (ParseException $e) {
            $result = sprintf('<error>Invalid YAML in file: %s.</error>', $file->getFilename());
        } catch (FilesystemException $e) {
            $result = sprintf('<error>' . $e->getMessage() . '</error>');
        }

        $output->writeln($result);
    }

    /**
     * @param InputInterface $input
     *
     * @return YamlFile
     */
    private function getFile(InputInterface $input)
    {
        $fileName = $input->getOption('file');
        if (strpos($fileName, '://') == false) {
            $fileName = 'config://' . $fileName;
        }

        $fs = $this->app['filesystem'];

        return $fs->get($fileName, new YamlFile());
    }
}
