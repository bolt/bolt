<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to repair/update database schema.
 */
class DatabaseRepair extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:update')
            ->addOption('dump-sql', null, InputOption::VALUE_NONE, 'Dump the SQL, do not execute the query')
            ->setDescription('Repair and/or update the database.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('dump-sql')) {
            $this->dumpSql();
            $this->io->note('Database SQL above was NOT applied to your database.');

            return 0;
        }

        $response = $this->app['schema']->check();
        if (!$response->hasResponses()) {
            $this->io->success('Your database is already up to date.');

            return 0;
        }
        $this->io->title('Database modifications required');
        if ($this->io->confirm('Would you like continue with the update')) {
            $response = $this->app['schema']->update();
            $this->io->note('Modifications made to the database');
            $this->io->listing($response->getResponseStrings());
            $this->io->success('Your database is now up to date.');

            $this->auditLog(__CLASS__, 'Database updated');
        }

        return 0;
    }

    /**
     * Dump the output.
     */
    private function dumpSql()
    {
        $this->app['schema']->check();

        $context = [
            'alters'  => $this->app['schema.comparator']->getAlters(),
            'creates' => $this->app['schema.comparator']->getCreates(),
        ];

        foreach ($context as $section) {
            foreach ($section as $sql) {
                $this->io->writeln($sql);
            }
        }
    }
}
