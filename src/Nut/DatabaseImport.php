<?php

namespace Bolt\Nut;

use Bolt\Storage\Migration\Import;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

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
            ->setDescription('[EXPERIMENTAL] Import database records from a YAML or JSON file')
            ->addOption('no-interaction', 'n', InputOption::VALUE_NONE, 'Do not ask for confirmation')
            ->addOption('file',           'f', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'A YAML or JSON file to use for import data. Must end with .yml, .yaml or .json')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Warn that this is experimental
        $output->writeln("<error>\n\nWARNING THIS IS AN EXPERIMENTAL FEATURE\n</error>\n");

        // Check if export file can be created
        $files = $input->getOption('file');
        if (empty($files)) {
            throw new \RuntimeException('The --file option is required.');
        }

        // See if we're going to continue
        if ($this->checkContinue($input, $output) === false) {
            return;
        }

        // Get the Bolt Import migration object
        $import = new Import($this->app);

        $import
            ->setMigrationFiles($files)
            ->checkMigrationFilesValid(true)
            ->checkMigrationFilesExist('import')
            ->importMigrationFiles()
        ;

        if ($import->getError()) {
            foreach ($import->getErrorMessages() as $error) {
                $output->writeln("<error>$error</error>");
            }

            $output->writeln("\n<error>Aborting import!</error>\n");

            return;
        }

        if ($import->getWarning()) {
            foreach ($import->getWarningMessages() as $warning) {
                $output->writeln("<comment>$warning</comment>");
            }

            return;
        }

        if ($import->getNotice()) {
            foreach ($import->getNoticeMessages() as $notice) {
                $output->writeln("<info>$notice</info>");
            }

            return;
        }

        // Report finish
        $filenames = join(', ', $files);
        $output->writeln("\n<info>Records imported from $filenames</info>");
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
        $question = new ConfirmationQuestion('<question>Are you sure you want to continue with the import?</question> ');

        if (!$confirm && !$helper->ask($input, $output, $question)) {
            return false;
        }

        return true;
    }
}
