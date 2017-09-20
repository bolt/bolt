<?php

namespace Bolt\Nut;

use Bolt\Storage\Migration\Export;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Nut database exporter command.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DatabaseExport extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:export')
            ->setDescription('Export the database records to a YAML or JSON file.')
            ->addOption('no-interaction', 'n', InputOption::VALUE_NONE, 'Do not ask for confirmation')
            ->addOption('contenttypes',   'c', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'One or more ContentTypes to export records for.')
            ->addOption('file',           'f', InputOption::VALUE_OPTIONAL, 'A YAML or JSON file to use for export data. Must end with .yml, .yaml or .json')
            ->addOption('directory',      'd', InputOption::VALUE_OPTIONAL, 'A destination directory. The command will automatically generate filenames.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Check if export file can be created
        $file = $input->getOption('file');
        $directory = $input->getOption('directory');
        if (empty($file) && empty($directory)) {
            throw new \RuntimeException('One of the --file or --directory options is required.');
        }

        // See if we're going to continue
        if ($this->checkContinue($input) === false) {
            return 0;
        }

        // If we are using the directory option, then we create the file and verify.
        if (empty($file) && isset($directory)) {
            $prefix = date('YmdHis');
            $file = rtrim($directory, '/') . '/' . $prefix . '.yml';
        }

        // Get the Bolt Export migration object
        $export = new Export($this->app);

        // Check the file extension is valid and writeable
        $export
            ->setMigrationFiles($file)
            ->checkMigrationFilesValid(false)
            ->checkMigrationFilesExist('export')
            ->checkMigrationFilesWriteable()
            ->checkContenttypeValid($input->getOption('contenttypes'))
            ->exportContenttypesRecords()
        ;

        if (!$export->getError()) {
            $this->io->success('Database exported to ' . $file);

            return 0;
        }

        foreach ($export->getErrorMessages() as $error) {
            $this->io->writeln("<error>$error</error>");
        }

        $this->io->error('Aborting export!');

        return 1;
    }

    /**
     * Check to see if we should continue.
     *
     * @param InputInterface $input
     *
     * @return boolean
     */
    private function checkContinue(InputInterface $input)
    {
        if ($input->getOption('no-interaction')) {
            return true;
        }
        $this->io->warning('This command operates on the current database, taking a backup is advised before export.');

        return $this->io->confirm('Are you sure you want to continue with the export');
    }
}
