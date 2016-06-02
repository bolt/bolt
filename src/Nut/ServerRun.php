<?php

namespace Bolt\Nut;

use Symfony\Component\Console\Helper\ProcessHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\ProcessBuilder;

/**
 * Runs Bolt application using PHP built-in web server.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class ServerRun extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        parent::configure();
        $this
            ->setName('server:run')
            ->setDescription('Runs PHP built-in web server')
            ->addArgument('address', InputArgument::OPTIONAL, 'Address:port', '0.0.0.0')
            ->addOption('port', 'p', InputOption::VALUE_REQUIRED, 'Address port number', '8000')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $address = $input->getArgument('address');
        if (strpos($address, ':') === false) {
            $address .= ':' . $input->getOption('port');
        }

        if ($this->isOtherServerProcessRunning($address)) {
            $io->error(sprintf('A process is already listening on http://%s', $address));

            return 1;
        }

        $webDir = $this->app['resources']->getPath('web');
        $router = $webDir . '/index.php';

        $io->success(sprintf('Server running on http://%s', $address));
        $io->comment('Quit the server with CONTROL-C.');

        if (($process = $this->createServerProcess($io, $address, $webDir, $router)) === null) {
            return 1;
        }

        /** @var ProcessHelper $helper */
        $helper = $this->getHelper('process');
        $helper->run($output, $process, null, null, OutputInterface::VERBOSITY_VERBOSE);

        if (!$process->isSuccessful()) {
            $errorMessages = ['Built-in server terminated unexpectedly.'];

            if ($process->isOutputDisabled()) {
                $errorMessages[] = 'Run the command again with -v option for more details.';
            }

            $io->error($errorMessages);
        }

        return $process->getExitCode();
    }

    /**
     * @param SymfonyStyle $io
     * @param string       $address
     * @param string       $webDir
     * @param string       $router
     *
     * @return null|\Symfony\Component\Process\Process
     */
    protected function createServerProcess(SymfonyStyle $io, $address, $webDir, $router)
    {
        if (!file_exists($router)) {
            $io->error(sprintf('The router script "%s" does not exist', $router));

            return null;
        }

        $finder = new PhpExecutableFinder();
        if (($binary = $finder->find()) === false) {
            $io->error('Unable to find PHP binary to run server.');

            return null;
        }

        $builder = new ProcessBuilder([$binary, '-S', $address, '-t', $webDir, $router]);
        $builder->setTimeout(null);

        if ($io->getVerbosity() < OutputInterface::VERBOSITY_VERBOSE) {
            $builder->disableOutput();
        }

        return $builder->getProcess();
    }

    /**
     * Determines if another process is bound to the given address and port.
     *
     * @param string $address An address/port tuple
     *
     * @return bool
     */
    protected function isOtherServerProcessRunning($address)
    {
        $lockFile = $this->getLockFile($address);

        if (file_exists($lockFile)) {
            return true;
        }

        list($hostname, $port) = explode(':', $address);

        $fp = @fsockopen($hostname, $port, $errno, $errstr, 5);

        if ($fp !== false) {
            fclose($fp);

            return true;
        }

        return false;
    }

    /**
     * Determines the name of the lock file for a particular PHP web server process.
     *
     * @param string $address An address/port tuple
     *
     * @return string The filename
     */
    protected function getLockFile($address)
    {
        return sys_get_temp_dir() . '/' . strtr($address, '.:', '--') . '.pid';
    }
}
