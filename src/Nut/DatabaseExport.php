<?php

namespace Bolt\Nut;

use Bolt\Storage\Migration\Export;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * Nut database exporter command
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DatabaseExport extends BaseCommand
{
    protected function configure()
    {
        $this
            ->setName('database:export')
            ->setDescription('[EXPERIMENTAL] Export the database records to a YAML or JSON file.')
            ->addOption('no-interaction', 'n', InputOption::VALUE_NONE, 'Do not ask for confirmation')
            ->addOption('contenttypes',   'c', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'One or more contenttypes to export records for.')
            ->addOption('file',           'f', InputOption::VALUE_REQUIRED, 'A YAML or JSON file to use for export data. Must end with .yml, .yaml or .json')
        ;
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

        // See if we're going to continue
        if ($this->checkContinue($input, $output) === false) {
            return;
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

        if ($export->getError()) {
            foreach ($export->getErrorMessages() as $error) {
                $output->writeln("<error>$error</error>");
            }

            $output->writeln("\n<error>Aborting export!</error>\n");

            return 1;
        }

        $output->writeln("<info>Database exported to $file</info>");
    }

    /**
     * Check to see if we should continue.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return boolean
     */
    private function checkContinue(InputInterface $input, OutputInterface $output)
    {
        /** @var \Symfony\Component\Console\Helper\QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $confirm  = $input->getOption('no-interaction');
        $question = new ConfirmationQuestion('<question>Are you sure you want to continue with the export?</question> ');

        if (!$confirm && !$helper->ask($input, $output, $question)) {
            return false;
        }

        return true;
    }
}
