<?php

namespace Bolt\Nut;

use Bolt\Exception\Database\DatabaseConnectionException;
use Bolt\Storage\Entity;
use Bolt\Storage\Repository\UsersRepository;
use Bolt\Translation\Translator as Trans;
use Bolt\Version;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to perform Bolt set-up (first time, or post-update) tasks.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SetupRun extends BaseCommand
{
    /** @var int */
    protected $step = 0;
    /** @var array */
    protected $firstUser;
    /** @var bool */
    protected $isDbSetup;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('setup:run')
            ->setDescription('Run initial Bolt site set-up tasks')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->reconcileDatabaseConfiguration($input->isInteractive());
        if ($this->isDbSetup) {
            $this->reconcileDatabaseSchema($output);
            $this->reconcileExtensionEnvironment($output);
            $this->reconcileRootUser($output);
        }

        return $this->finish();
    }

    /**
     * @param bool $isInteractive
     *
     * @throws DatabaseConnectionException
     *
     * @return bool
     */
    protected function reconcileDatabaseConfiguration($isInteractive)
    {
        $db = $this->app['db'];
        $confirmMessage = 'Continue with these database settings?';
        $hasSqliteFile = $this->app['filesystem']->has('app://database/bolt.db');
        $platform = $this->app['config']->get('general/database/driver');
        $isSqlite = (bool) strpos($platform, 'sqlite');
        $ask = false;
        $note = [];

        if ($isSqlite && !$hasSqliteFile) {
            $this->isDbSetup = false;
            $note[] = 'Your database is configured as Sqlite, but does not exist yet.';
            $note[] = 'This might not be your intended configuration.';
            $ask = true;
        }
        if ($isSqlite && $hasSqliteFile) {
            $this->isDbSetup = true;
            if (empty($db->getSchemaManager()->listTableNames())) {
                $this->isDbSetup = false;
                $note[] = 'Your database is configured as Sqlite, but is empty.';
                $note[] = 'This might not be your intended database.';
                $ask = true;
            }
        }
        try {
            if (!$isSqlite) {
                $db->connect();
                $this->isDbSetup = true;
            }
        } catch (DatabaseConnectionException $e) {
            $this->isDbSetup = false;
            $note[] = 'Can not connect to your database as configured';
            $ask = true;
            if ($this->io->isVeryVerbose()) {
                throw $e;
            }
        }

        if ($ask && $isInteractive) {
            $this->step(++$this->step, 'Checking Database Configuration');
            $this->io->note($note);
            $confirm = $this->io->confirm($confirmMessage, false);
            if ($confirm === false) {
                $this->io->note([
                    'Further database checks will be skipped this time!',
                    'Please update the "database" section in your config.yml file',
                ]);
            }

            return $this->isDbSetup = $confirm;
        }

        return true;
    }

    /**
     * Check the database schema exists is consistent with the configuration.
     *
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function reconcileDatabaseSchema(OutputInterface $output)
    {
        if ($this->isDbSetup === false) {
            return true;
        }
        $this->step(++$this->step, 'Checking Database Schema');
        $command = $this->getApplication()->find('database:check');
        $buffer = $this->getBufferedOutput($output, OutputInterface::VERBOSITY_QUIET);
        $input = new ArrayInput([
            '--no-interaction' => true,
        ]);

        $result = $command->run($input, $buffer);
        $this->writeBufferedOutput($buffer, $result);
        if ($result === 0) {
            return true;
        }

        $this->step(++$this->step, 'Updating Database Schema');
        $command = $this->getApplication()->find('database:update');
        $buffer = $this->getBufferedOutput($output, OutputInterface::VERBOSITY_QUIET);
        $input = new ArrayInput([
            '--no-interaction' => true,
        ]);

        $result = $command->run($input, $buffer);
        $this->writeBufferedOutput($buffer, $result);

        return $result === 0;
    }

    /**
     * Create first user, with the "root" role.
     *
     * Only executes when no users are present in the database, and must be run
     * after the database check/update.
     *
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function reconcileRootUser(OutputInterface $output)
    {
        if ($this->isDbSetup === false) {
            return true;
        }
        /** @var UsersRepository $repo */
        $repo = $this->app['storage']->getRepository(Entity\Users::class);
        if ($repo->count() > 0) {
            return true;
        }

        $this->step(++$this->step, 'Creating First User');
        if (!$this->io->confirm('Would you like to create the first user now?', false)) {
            return true;
        }

        $command = $this->getApplication()->find('user:add');
        $input = new ArrayInput([
            'role'     => ['root'],
            '--enable' => true,
        ]);
        $result = $command->run($input, $output);
        if ($result === 0) {
            $this->firstUser = $input->getArguments();
        }

        return $result === 0;
    }

    /**
     * Update/install the extension composer.json with latest Bolt specific
     * parameters, version numbers, etc.
     *
     * @param OutputInterface $output
     *
     * @return bool
     */
    protected function reconcileExtensionEnvironment(OutputInterface $output)
    {
        $this->step(++$this->step, 'Configuring Extension Environment');
        $command = $this->getApplication()->find('extensions:setup');
        $buffer = $this->getBufferedOutput($output, OutputInterface::VERBOSITY_QUIET);
        $input = new ArrayInput([
            '--no-interaction' => true,
        ]);

        $result = $command->run($input, $buffer);
        $this->writeBufferedOutput($buffer, $result);

        return $result === 0;
    }

    /**
     * Finishing step.
     *
     * Let new sites know set up is complete and login name.
     *
     * @return int
     */
    protected function finish()
    {
        if ($this->firstUser !== null) {
            $this->step(++$this->step, 'Setup Complete!');
            $this->writeWelcome();

            $branding = $this->app['config']->get('/general/branding/path', '/bolt');
            $this->io->listing([
                'You may now login to your new site with the user name "<question>' . $this->firstUser['username'] . '</question>" ' .
                'via your site URI: <question>' . $branding . '</question>',
            ]);

            return 0;
        }
        $this->step(++$this->step, 'Setup Updated!');
        $this->writeWelcome();

        return 0;
    }

    /**
     * Return a pre-configured output buffer, with the same formatter and
     * decorator in use for the passed output, with optionally separate
     * verbosity level.
     *
     * @param OutputInterface $output
     * @param int             $verbosity
     *
     * @return BufferedOutput
     */
    protected function getBufferedOutput(OutputInterface $output, $verbosity = null)
    {
        $buffer = new BufferedOutput();
        $buffer->setDecorated($output->isDecorated());
        $buffer->setFormatter($output->getFormatter());
        if ($verbosity === null) {
            $buffer->setVerbosity($output->getVerbosity());
        } else {
            $buffer->setVerbosity($verbosity);
        }

        return $buffer;
    }

    /**
     * Write buffered output to the console.
     *
     * Nothing will be written if the command result is 0 and verbose output is
     * not enabled.
     *
     * @param BufferedOutput $buffer
     * @param int            $result
     */
    protected function writeBufferedOutput(BufferedOutput $buffer, $result)
    {
        if ($result === 0 && $buffer->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            return;
        }

        $this->io->write($buffer->fetch());
    }

    /**
     * Write the current step text to output.
     *
     * @param int          $step
     * @param array|string $message
     */
    private function step($step, $message)
    {
        $step = 'Step ' . $step;
        $this->io->block($message, $step, 'fg=white;bg=blue', ' ', true);
    }

    /**
     * Write the welcome message.
     */
    private function writeWelcome()
    {
        $message = sprintf(
            "<info>%s</info> - %s <comment>%s</comment>.\n",
            Trans::__('nut.greeting'),
            Trans::__('nut.version'),
            Version::VERSION
        );
        $this->io->text($message);
    }
}
