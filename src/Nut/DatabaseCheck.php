<?php

namespace Bolt\Nut;

use SqlFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to perform a database consistency check command.
 */
class DatabaseCheck extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:check')
            ->setDescription('Check the database for missing tables and/or columns.')
            ->addOption('show-changes', 's', InputOption::VALUE_NONE, 'Show proposed schema changes')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Bolt\Storage\Database\Schema\SchemaCheck $response */
        $response = $this->app['schema']->check();

        if (!$response->hasResponses()) {
            $this->io->success('The database is OK.');

            return 0;
        }

        $this->io->title('Modifications required');
        $this->io->listing($response->getResponseStrings());
        $this->io->note("One or more fields/tables are missing from the Database. Please run 'nut database:update' to fix this.");

        if ($input->getOption('show-changes')) {
            $this->io->title('Proposed modifications');
            $this->showDiffs();
        }

        return 1;
    }

    /**
     * Render diffs.
     */
    protected function showDiffs()
    {
        $this->showCreates();
        $this->showAlterations();
    }

    /**
     * Display a section of tables to be created.
     */
    protected function showCreates()
    {
        $creates = $this->app['schema.comparator']->getCreates();
        if ($creates) {
            $this->io->section('Tables to be created');
            foreach ($creates as $tableName => $sql) {
                $this->io->writeln(\SqlFormatter::format($sql[0]));
            }
        }
    }

    /**
     * Display a section of tables to be altered.
     */
    protected function showAlterations()
    {
        $alters = $this->app['schema.comparator']->getAlters();
        if ($alters) {
            $this->io->section('Tables to be altered:');

            $tableCount = count($alters);
            foreach ($alters as $tableName => $sql) {
                $this->io->comment($tableName);
                foreach ($sql as $query) {
                    $this->io->text(SqlFormatter::highlight($query));
                }

                if (--$tableCount) {
                    $this->io->writeln('');
                }
            }
        }
    }
}
