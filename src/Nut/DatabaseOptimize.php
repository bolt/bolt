<?php

namespace Bolt\Nut;

use Bolt\Collection\MutableBag;
use Bolt\Nut\Helper\ContainerHelper;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to optimize the running database.
 */
class DatabaseOptimize extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:optimize')
            ->setDescription('Optimize the database.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->assertMaintenanceMode();

        /** @var ContainerHelper $helper */
        $helper = $this->getHelper('container');
        $app = $helper->getContainer();
        /** @var Connection $db */
        $db = $app['db'];
        $updates = $this->getPlatformUpdates();

        $this->io->warning('You must ensure that you have database backups');
        if (!$this->io->confirm('<error>Do you have current backups of your database?</error> ', false)) {
            $this->io->note('Aborting!');

            return 1;
        }

        $this->io->title('Database Optimization');
        $this->io->note('The following database queries will be run:');
        $this->io->listing($updates->toArray());

        if (!$this->io->confirm('Continue? ', false)) {
            $this->io->note('Aborting!');

            return 1;
        }

        $progressBar = $this->io->createProgressBar();
        if ($this->io->isDebug()) {
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %message%');
        }
        $progressBar->start(count($updates));
        foreach ($updates as $update) {
            $progressBar->setMessage("Running: '$update;'");
            $progressBar->advance();
            $db->exec($update);
            $db->close();
        }
        $progressBar->finish();
        $this->io->writeln(PHP_EOL);

        $this->io->success('Database optimisation complete');
        $this->auditLog(__CLASS__, 'Database optimised');

        return 0;
    }

    /**
     * @throws RuntimeException
     *
     * @return MutableBag
     */
    private function getPlatformUpdates()
    {
        /** @var ContainerHelper $helper */
        $helper = $this->getHelper('container');
        $app = $helper->getContainer();
        $platform = $app['db']->getDatabasePlatform();
        $platformName = $platform->getName();
        $schemaTables = $app['schema']->getSchemaTables();
        $tables = MutableBag::from([]);
        /** @var Table $table */
        foreach ($schemaTables as $table) {
            $tables[] = $table->getName();
        }

        if ($platformName === 'sqlite') {
            return MutableBag::from(['VACUUM', 'ANALYZE']);
        }

        if ($platformName === 'postgresql') {
            $updates = MutableBag::from(['VACUUM(FULL, ANALYZE)']);
            /** @var string $table */
            foreach ($tables as $table) {
                $updates[] = 'REINDEX TABLE ' . $table;
            }

            return $updates;
        }

        if ($platformName === 'mysql') {
            return MutableBag::from([
                'ANALYZE TABLE ' . $tables->join(', '),
                'OPTIMIZE TABLE ' . $tables->join(', '),
            ]);
        }

        throw new RuntimeException(sprintf('Unsupported platform: %s', $platformName));
    }

    /**
     * Ensure the site is in maintenance mode.
     *
     * @throws RuntimeException
     */
    private function assertMaintenanceMode()
    {
        /** @var ContainerHelper $helper */
        $helper = $this->getHelper('container');
        $app = $helper->getContainer();
        /** @var \Bolt\Config $config */
        $config = $app['config'];
        if ($config->get('general/maintenance_mode') !== true) {
            throw new RuntimeException('Site is not in maintenance mode!');
        }
    }
}
