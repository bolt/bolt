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
            $this->dumpSql($output);

            return $output->writeln(sprintf('<comment>%sDatabase SQL above was NOT applied to your database.</comment>', PHP_EOL));
        }

        $response = $this->app['schema']->update();
        if (!$response->hasResponses()) {
            return $output->writeln('<info>Your database is already up to date.</info>');
        }

        $output->writeln('<comment>Modifications made to the database:</comment>');
        foreach ($response->getResponseStrings() as $messages) {
            $output->writeln('<info> - ' . $messages . '</info>');
        }
        $this->auditLog(__CLASS__, 'Database updated');

        return $output->writeln('<info>Your database is now up to date.</info>');
    }

    /**
     * @param OutputInterface $output
     */
    private function dumpSql(OutputInterface $output)
    {
        $this->app['schema']->check();

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
