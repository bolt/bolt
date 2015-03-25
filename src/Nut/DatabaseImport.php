<?php

namespace Bolt\Nut;

use Bolt\Helpers\Arr;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Parser;

/**
 * Nut database importer command
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DatabaseImport extends BaseCommand
{
    /** @var array YAML to import to records */
    private $yaml = array();

    /** @var array Contenttypes in use */
    private $contenttypes = array();

    protected function configure()
    {
        $this
            ->setName('database:import')
            ->setDescription('[EXPERIMENTAL] Import database records from a YAML file')
            ->addOption('no-interaction', 'n', InputOption::VALUE_NONE, 'Do not ask for confirmation')
            ->addOption('file',           'f', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'A YAML file to use for import data. Must end with .yml or .yaml');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Warn that this is experimental
        $output->writeln("<error>\n\nWARNING THIS IS AN EXPERIMENTAL FEATURE\n</error>");

        $files = $input->getOption('file');
        if (empty($files)) {
            throw new \RuntimeException('The --files option is required.');
        }

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

        // Read the YAML from each file
        foreach ($files as $file) {
            if (!$this->readYaml($file, $output)) {
                return;
            }
        }

        // Check the contenttypes for the requested import
        if (!$this->isContenttypesValid($output)) {
            return;
        }

        // Import each record from each import file's contenttype
        foreach ($this->yaml as $file => $data) {
            foreach ($data as $contenttypeslug => $records) {
                foreach ($records as $recordvalues) {
                    $this->importRecord($contenttypeslug, $recordvalues, $output);
                }
            }
        }

        // Report finish
        $filenames = join(', ', $files);
        $output->writeln("<info>Database imported from $filenames</info>");
    }

    /**
     * Import a given record
     *
     * @param string          $contenttypeslug
     * @param array           $values
     * @param OutputInterface $output
     */
    private function importRecord($contenttypeslug, array $values, OutputInterface $output)
    {
        // Get a status
        if (isset($values['status'])) {
            $status = $values['status'];
        } else {
            $status = $this->contenttypes[$contenttypeslug]['default_status'];
        }

        // Transform the 'publish' action to a 'published' status
        $status = $status === 'publish' ? 'published' : $status;

        // Insist on a title field
        if (!isset($values['title'])) {
            $output->writeln("<question>Skipping record with empty title field…</question>");
            return;
        }

        // Set up default meta
        $meta = array(
            'slug'        => isset($values['slug']) ? $values['slug'] : substr($this->app['slugify']->slugify($values['title']), 0, 127),
            'datecreated' => date('Y-m-d H:i:s'),
            'datepublish' => $status == 'published' ? date('Y-m-d H:i:s') : null,
            'ownerid'     => 1
        );

        // Test to see if a Contenttype record with this slug exists
        if ($this->isRecordInExistence($contenttypeslug, $values['slug'])) {
            $output->writeln("<question>Skipping record with the slug '{$values['slug']}' as a matching record already exists…</question>");
            return;
        }

        $values = Arr::mergeRecursiveDistinct($values, $meta);

        $record = $this->app['storage']->getEmptyContent($contenttypeslug);
        $record->setValues($values);

        $id = $this->app['storage']->saveContent($record);

        if ($id === false) {
            $output->writeln("<error>Failed to imported record with title: {$values['title']}.</error>");

            return false;
        } else {
            $output->writeln("<info>Imported record with title: {$values['title']}.</info>");

            return $id;
        }
    }

    /**
     * Test is a record already exists
     *
     * @param string $contenttypeslug
     * @param string $slug
     *
     * @return boolean
     */
    private function isRecordInExistence($contenttypeslug, $slug)
    {
        $record = $this->app['storage']->getContent("$contenttypeslug/$slug");
        if (empty($record)) {
            return false;
        }

        return true;
    }

    /**
     * Check Contenttype in the import files exists
     *
     * @param OutputInterface $output
     *
     * @return array|null
     */
    private function isContenttypesValid(OutputInterface $output)
    {
        foreach ($this->yaml as $file => $data) {
            if (!is_array($data)) {
                $output->writeln("<error>File '$file' has malformed Contenttype import data!</error>");
                return false;
            }

            foreach (array_keys($data) as $contenttypeslug) {
                $contenttype = $this->app['storage']->getContentType($contenttypeslug);

                if (empty($contenttype)) {
                    $output->writeln("<error>File '$file' has invalid contenttype '$contenttypeslug'!</error>");
                    return false;
                }

                if (!isset($this->contenttypes[$contenttypeslug])) {
                    $this->contenttypes[$contenttypeslug] = $contenttype;
                }
            }
        }

        return true;
    }

    /**
     * Read a YAML file
     *
     * @param string          $file
     * @param OutputInterface $output
     *
     * @return array
     */
    private function readYaml($file, $output)
    {
        $parser = new Parser();

        if (is_readable($file)) {
            try {
                $this->yaml[$file] = $parser->parse(file_get_contents($file) . "\n");
                return true;
            } catch (ParseException $e) {
                $output->writeln("<error>File '$file' has invalid YAML!</error>");
                return false;
            }
        } else {
            $output->writeln("<error>File '$file' not readable!</error>");
            return false;
        }
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
