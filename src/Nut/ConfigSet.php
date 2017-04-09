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
 * Nut command to set a value in config.yml
 */
class ConfigSet extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('config:set')
            ->setDescription('Set a value in config.yml.')
            ->addArgument('key', InputArgument::REQUIRED, 'The key you wish to get.')
            ->addArgument('value', InputArgument::REQUIRED, 'The value you wish to set it to.')
            ->addOption('file', 'f', InputOption::VALUE_OPTIONAL, 'Specify config file to use', 'config://config.yml')
            ->addOption('backup', 'b', InputOption::VALUE_NONE, 'Make a backup of the config file')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key = $input->getArgument('key');
        $value = $input->getArgument('value');
        $backup = $input->getOption('backup');
        $file = $this->getFile($input);

        try {
            $yaml = new YamlUpdater($this->app, $file);

            if ($yaml->change($key, $value, $backup)) {
                $result = sprintf('New value for <info>%s: %s</info> was successful. File updated.', $key, $value);
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

        $this->auditLog(__CLASS__, "Config value '$key: $value' set");
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
