<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Nut command to repair/update database schema
 */
class DatabaseRepair extends BaseCommand
{
    /**
     * @see \Symfony\Component\Console\Command\Command::configure()
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
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('dump-sql')) {
            return $this->dumpSql($output);
        }

        $response = $this->app['schema']->update();

        if (!$response->hasResponses()) {
            $output->writeln('<info>Your database is already up to date.</info>');
        } else {
            $output->writeln('<comment>Modifications made to the database:</comment>');
            foreach ($response->getResponseStrings() as $messages) {
                $output->writeln('<info> - ' . $messages . '</info>');
            }
            $output->writeln('<info>Your database is now up to date.</info>');

            $this->auditLog(__CLASS__, 'Database updated');
        }
    }

    private function dumpSql(OutputInterface $output)
    {
        $check = $this->app['schema']->check();

        $context = [
            'alters'  => $this->app['schema.comparator']->getAlters(),
            'creates' => $this->app['schema.comparator']->getCreates(),
        ];

        foreach ($context as $section) {
            foreach ($section as $sql) {
                $output->writeln($sql);
            }
        }
    }
}
