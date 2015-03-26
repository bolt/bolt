<?php

namespace Bolt\Nut;

use Bolt\Database\Migration\Export;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Dumper;

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
            ->setDescription('[EXPERIMENTAL] Export the database records to YAML file')
            ->addOption('no-interaction', 'n', InputOption::VALUE_NONE, 'Do not ask for confirmation')
            ->addOption('contenttypes',   'c', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'One or more contenttypes to export records for.')
            ->addOption('file',           'f', InputOption::VALUE_REQUIRED, 'A YAML file to use for export data. Must end with .yml or .yaml');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Warn that this is experimental
        $output->writeln("<error>\n\nWARNING THIS IS AN EXPERIMENTAL FEATURE\n</error>\n");

        // Check if export file can be created
        $file = $input->getOption('file');
        if (empty($file)) {
            throw new \RuntimeException('The --file option is required.');
        }

        // Get the Bolt Export migration object
        $export = new Export($this->app);

        // Check the file extension is valid and writeable
        $ret = $export
            ->isMigrationFileValid($file)
            ->isMigrationFileWriteable($file)
            ->getError();

        if ($ret) {
            foreach ($export->getErrorMessages() as $error) {
                $output->writeln("<error>$error</error>");
            }

            return;
        }

        // Yes, no, maybe?
        if (!$input->getOption('no-interaction')) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Continue with this action? ', false);

            if (!$helper->ask($input, $output, $question)) {
                return;
            }
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

        // Export each Contenttype's records to the export file
        foreach ($this->contenttypes as $contenttype) {
            $this->exportContenttype($contenttype, $file, $output);
        }

        $contenttypes = join(' ', $contenttypes);
        $output->writeln("<info>Database exported to $file: $contenttypes</info>");
    }

    /**
     * Export a Contenttype's records to the export file.
     *
     * @param string          $contenttype
     * @param string          $file
     * @param OutputInterface $output
     *
     * @return boolean
     */
    private function exportContenttype($contenttype, $file, OutputInterface $output)
    {
        // Get all the records foe the contenttype
        $records = $this->app['storage']->getContent($contenttype);

        $output = array();
        foreach ($records as $record) {
            $values = $record->getValues();
            unset($values['id']);
            $output[$contenttype][] = $values;
        }

        // Get a new YAML dumper
        $dumper = new Dumper();

        // Generate the YAML string
        $yaml = $dumper->dump($output, 4);

        file_put_contents($file, $yaml, FILE_APPEND);
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
