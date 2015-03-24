<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;

/**
 * Nut database importer command
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DatabaseImport extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('database:import')
            ->setDescription('Import database records from a YAML file')
            ->addOption('no-interaction', 'n', InputOption::VALUE_NONE, 'Do not ask for confirmation')
            ->addOption('file',           'f', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'A YAML file to use for import data. Must end with .yml or .yaml');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $files = $input->getOption('file');

        // Check passed files are all valid
        if (!$this->isFilesValid($files, $output)) {
            return;
        }

        if (!$input->getOption('no-interaction')) {
            $helper = $this->getHelper('question');
            $output->writeln('<error>WARNING: This will import records from the given YAML file into the database!</error>');
            $question = new ConfirmationQuestion('Continue with this action? ', false);

            if (!$helper->ask($input, $output, $question)) {
                return;
            }
        }


        $filenames = join(', ', $files);
        $output->writeln("<info>Database imported from $filenames</info>");
    }

    /**
     * Determine if files passed in exist and have a valid extension
     *
     * @param array           $files
     * @param OutputInterface $output
     *
     * @return boolean
     */
    private function isFilesValid(array $files, OutputInterface $output)
    {
        foreach ($files as $file) {
            // Check the file exists
            try {
                $fileObj = new File($file);
            } catch (FileNotFoundException $e) {
                $output->writeln("<error>File '$file' not found!</error>");
                return false;
            }

            // Check the file extension
            $ext = $fileObj->getExtension();
            if ($ext !== 'yml' && $ext !== 'yaml') {
                $output->writeln("<error>File '$file' has an invalid extension! Must be either '.yml' or '.yaml'.</error>");
                return false;
            }
        }

        return true;
    }
}
