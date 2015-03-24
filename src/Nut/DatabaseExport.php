<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Nut database exporter command
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DatabaseExport extends BaseCommand
{
    /** @var array Contenttypes in use */
    private $contenttypes = array();

    protected function configure()
    {
        $this
            ->setName('database:export')
            ->setDescription('Export the database records to YAML file')
            ->addOption('no-interaction', 'n', InputOption::VALUE_NONE, 'Do not ask for confirmation')
            ->addOption('contenttypes',   'c', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'One or more contenttypes to export records for.')
            ->addOption('file',           'f', InputOption::VALUE_REQUIRED, 'A YAML file to use for export data. Must end with .yml or .yaml');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('no-interaction')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Continue with this action? ', false);

            if (!$helper->ask($input, $output, $question)) {
                return;
            }
        }

        // Check if export file can be created
        $file = $input->getOption('file');
        if (!$this->isFileWriteable($file, $output)) {
            return;
        }

        // Ensure any requests contenttypes requests are valid
        $contenttypes = $input->getOption('contenttypes');
        if (!empty($contenttypes) && !$this->isContenttypesValid($contenttypes, $output)) {
            return;
        }

        // If no Contenttypes were passed in, grab 'em all
        if (empty($contenttypes)) {
            $this->contenttypes = $this->app['storage']->getContentTypes();
        }

        $contenttypes = join(' ', $contenttypes);
        $output->writeln("<info>Database exported to $file: $contenttypes</info>");
    }

    /**
     * Check/create target export file
     *
     * @param string          $file
     * @param OutputInterface $output
     *
     * @return boolean
     */
    private function isFileWriteable($file, OutputInterface $output)
    {
        $fs = new Filesystem();

        if ($fs->exists($file)) {
            $output->writeln("<error>Specified export file '$file' already exists! Aborting export.</error>");
            return false;
        }

        try {
            $fs->touch($file);
        } catch (IOException $e) {
            $output->writeln("<error>Specified export file '$file' can not be created! Aborting export.</error>");
            return false;
        }

        return true;
    }

    /**
     * Check Contenttype requested exists
     *
     * @param array           $contenttypes
     * @param OutputInterface $output
     *
     * @return boolean
     */
    private function isContenttypesValid(array $contenttypes, OutputInterface $output)
    {
        foreach ($contenttypes as $contenttypeslug) {
            $contenttype = $this->app['storage']->getContentType($contenttypeslug);

            if (empty($contenttype)) {
                $output->writeln("<error>The requested Contenttype '$contenttypeslug' doesn't exist! Aborting export.</error>");
                return false;
            }

            if (!isset($this->contenttypes[$contenttypeslug])) {
                $this->contenttypes[$contenttypeslug] = $contenttype;
            }
        }

        return true;
    }
}
