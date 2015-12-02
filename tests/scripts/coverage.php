<?php

/**
 * Functionality to handle PHPUnit coverage comparision
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */

namespace Bolt\Tests;

use Guzzle\Http\Client as GuzzleClient;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Yaml\Parser;

include realpath(__DIR__ . '/../../vendor/autoload.php');

/**
 * Class for doing comparisons of PHPUnit code coverage reports
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class CoverageComparator
{
    /** @var \Symfony\Component\Console\Output\ConsoleOutput */
    private $output;

    /** @var string Bolt's PHPUnit XML configuration file */
    private $xmlfile;

    /**
     *
     */
    public function __construct()
    {
        // Output
        $this->output = new ConsoleOutput();

        $this->xmlfile = realpath(__DIR__ . '/../../phpunit.xml.dist');
    }

    /**
     * Perform a PHPUnit run and output coverage results
     *
     * @param string $output Target PHP file to output serialized resultst to
     * @param string $test   Test directory or file to run
     *
     * @return integer
     */
    public function runPhpUnitCoverage($output, $test = null)
    {
        // Composer bundled PHPUnit binary
        $bin = realpath(__DIR__ . '/../../vendor/phpunit/phpunit/composer/bin/phpunit');

        $builder = new ProcessBuilder();
        $builder->setPrefix($bin);

        // Build the process
        $process = $builder
            ->setArguments(
                [
                    '--configuration',
                    $this->xmlfile,
                    '--coverage-php',
                    $output,
                    $test
                ])
            ->getProcess()
            ->setTimeout(0)
            ->enableOutput();

        try {
            // Run it
            $retval = $process->run(
                function ($type, $buffer) {
                    echo $buffer;
                }
            );
        } catch (ProcessFailedException $e) {
            echo $e->getMessage();
        }

        if (!$process->isSuccessful()) {
            $this->output->write('<error>' . $process->getErrorOutput() . '</error>', true);
        }

        return $retval;
    }

    /**
     * Extract the data from the PHPUnit code coverage
     *
     * @param PHP_CodeCoverage $codeCoverage
     *
     * @return array
     */
    private function getCoverageStats(\PHP_CodeCoverage $codeCoverage)
    {
        $report = $codeCoverage->getReport();
        $classCoverage = [];

        foreach ($report as $item) {
            if (!$item instanceof \PHP_CodeCoverage_Report_Node_File) {
                continue;
            }

            $classes = $item->getClassesAndTraits();

            foreach ($classes as $className => $class) {
                $classStatements        = 0;
                $coveredClassStatements = 0;
                $coveredMethods         = 0;
                $classMethods           = 0;

                foreach ($class['methods'] as $method) {
                    if ($method['executableLines'] == 0) {
                        continue;
                    }

                    $classMethods++;
                    $classStatements        += $method['executableLines'];
                    $coveredClassStatements += $method['executedLines'];
                    if ($method['coverage'] == 100) {
                        $coveredMethods++;
                    }
                }

                if (!empty($class['package']['namespace'])) {
                    $namespace = '\\' . $class['package']['namespace'] . '::';
                } elseif (!empty($class['package']['fullPackage'])) {
                    $namespace = '@' . $class['package']['fullPackage'] . '::';
                } else {
                    $namespace = '';
                }

                if ($coveredClassStatements != 0) {
                    $classCoverage[$namespace . $className] = [
                        'namespace'         => $namespace,
                        'className '        => $className,
                        'methodsCovered'    => $coveredMethods,
                        'methodCount'       => $classMethods,
                        'statementsCovered' => $coveredClassStatements,
                        'statementCount'    => $classStatements,
                    ];
                }
            }
        }

        return [
            'classes'  => [
                'total'  => $report->getNumClassesAndTraits(),
                'tested' => $report->getNumTestedClassesAndTraits()
            ],
            'methods'  => [
                'total'  => $report->getNumMethods(),
                'tested' => $report->getNumTestedMethods()
            ],
            'lines'    => [
                'total'  => $report->getNumExecutableLines(),
                'tested' => $report->getNumExecutedLines()
            ],
            'coverage' => $classCoverage
        ];
    }

    /**
     * Compare two sets of results and return the increased stats
     *
     * @param string $beforeFile
     * @param string $afterFile
     *
     * @return array
     */
    public function compareCoverageStats($beforeFile, $afterFile)
    {
        // Before
        $codeCoverage = unserialize(file_get_contents($beforeFile));
        $before = $this->getCoverageStats($codeCoverage);

        // After
        $codeCoverage = unserialize(file_get_contents($afterFile));
        $after = $this->getCoverageStats($codeCoverage);

        $increase = [
            'classes'  => 0,
            'methods'  => 0,
            'lines'    => 0,
            'coverage' => []
        ];

        $deltaBefore = $before['classes']['total'] - $before['classes']['tested'];
        $deltaAfter = $after['classes']['total'] - $after['classes']['tested'];
        if ($deltaAfter > $deltaBefore) {
            $increase['classes'] = $deltaAfter - $deltaBefore;
        }

        $deltaBefore = $before['methods']['total'] - $before['methods']['tested'];
        $deltaAfter = $after['methods']['total'] - $after['methods']['tested'];
        if ($deltaAfter > $deltaBefore) {
            $increase['methods'] = $deltaAfter - $deltaBefore;
        }

        $deltaBefore = $before['lines']['total'] - $before['lines']['tested'];
        $deltaAfter = $after['lines']['total'] - $after['lines']['tested'];
        if ($deltaAfter > $deltaBefore) {
            $increase['lines'] = $deltaAfter - $deltaBefore;
        }

        foreach ($after['coverage'] as $class => $data) {
            // Methods
            $deltaBefore = $data['methodsCovered'] - $before['coverage'][$class]['methodsCovered'];
            $deltaAfter = $data['methodCount'] - $before['coverage'][$class]['methodCount'];
            if ($deltaAfter > $deltaBefore) {
                $increase['coverage'][$class]['methods'] = $deltaAfter - $deltaBefore;
            }

            // Statments
            $deltaBefore = $data['statementsCovered'] - $before['coverage'][$class]['statementsCovered'];
            $deltaAfter = $data['statementCount'] - $before['coverage'][$class]['statementCount'];
            if ($deltaAfter > $deltaBefore) {
                $increase['coverage'][$class]['statements'] = $deltaAfter - $deltaBefore;
            }
        }

        return $increase;
    }

    /**
     * Format the stats
     *
     * @param array $stats
     *
     * @return string
     */
    public function formatCoverageStats(array $stats)
    {
        $coverage = <<<EOF
### Changes in code coverage

#### Totals
| Classes | Methods | Lines |
|---------|---------|-------|
| {$stats['classes']} | {$stats['methods']} | {$stats['lines']} |


EOF;

        $coverage .= <<<EOF
#### Individual Classes
| Class | Methods | Statements |
|-------|---------|------------|

EOF;

        foreach ($stats['coverage'] as $class => $data) {
            $coverage .= <<<EOF
| {$class} | {$data['methods']} | {$data['statements']} |

EOF;
        }

        return $coverage;
    }
}

/**
 * Class for interacting with GIT
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Git
{
    /** @var \Symfony\Component\Console\Output\ConsoleOutput */
    private $output;

    /** @var \Guzzle\Http\Client */
    private $client;

    /** @var \Symfony\Component\Process\ProcessBuilder */
    private $builder;

    /** @var array */
    private $config;

    /**
     *
     */
    public function __construct()
    {
        // Output
        $this->output = new ConsoleOutput();

        // Config
        $this->getConfig();

        // Guzzle client
        $this->client = new GuzzleClient('https://api.github.com/repos/bolt/bolt/pulls/', $this->guzzleDefaults);

        // Symfony process
        $this->builder = new ProcessBuilder();

        // Assume that `git` is in the path
        $this->builder->setPrefix('git');
    }

    public function getConfig()
    {
        $filename = __DIR__ . '/config.yml';
        if (is_readable($filename)) {
            $parser = new Parser();
            $this->config = $parser->parse(file_get_contents($filename) . "\n");
        }

        $this->guzzleDefaults = [];
        if (isset($this->config['github']['token'])) {
            $this->guzzleDefaults = ['query' => ['access_token' => $this->config['github']['token']]];
        }
    }

    /**
     * Get a GitHub PR's JSON
     *
     * @param integer $pr
     *
     * @return stdClass
     */
    public function getPr($pr)
    {
        $response = $this->client->get($pr, null, $this->guzzleDefaults)->send();
        $remaining = (string) $response->getHeader('X-RateLimit-Remaining');
        $reset = date('Y-m-d H:i:s', (string) $response->getHeader('X-RateLimit-Reset'));
        $this->output->write("<question>GitHub hourly requests remaining: {$remaining}. Reset at {$reset}</question>", true);

        return json_decode($response->getBody());
    }

    /**
     * Add a git remote to the current git repo
     *
     * @param string $name
     * @param string $url
     *
     * @return integer
     */
    public function addRemote($name, $url)
    {
        $this->output->write("<info>Adding $name as a remote</info>", true);

        // Build the process
        $process = $this->builder->setArguments(['remote', 'add', $name, $url])
            ->getProcess()
            ->enableOutput();

        // Run it
        $retval = $process->run();

        if (!$process->isSuccessful()) {
            $this->output->write('<error>' . $process->getErrorOutput() . '</error>', true);
        }

        echo $process->getOutput(), "\n";

        return $retval;
    }

    /**
     * Delete a git remote from the current git repo
     *
     * @param string $name
     *
     * @return integer
     */
    public function delRemote($name)
    {
        $this->output->write("<info>Deleting $name as a remote</info>", true);

        // Build the process
        $process = $this->builder->setArguments(['remote', 'remove', $name])
            ->getProcess()
            ->enableOutput();

        // Run it
        $retval = $process->run();

        if (!$process->isSuccessful()) {
            $this->output->write('<error>' . $process->getErrorOutput() . '</error>', true);
        }

        echo $process->getOutput(), "\n";

        return $retval;
    }

    /**
     * Checkout a remote's branch
     *
     * @param string $branch Branch
     * @param string $name   Remote name
     *
     * @return integer
     */
    public function checkoutBranch($branch, $name = null)
    {
        if ($name) {
            $this->output->write("<info>Checking out $branch $name/$branch</info>", true);
            $this->builder->setArguments(['checkout', '-b', $branch, "$name/$branch"]);
        } else {
            $this->output->write("<info>Checking out $branch</info>", true);
            $this->builder->setArguments(['checkout', $branch]);
        }

        // Build the process
         $process = $this->builder
            ->getProcess()
            ->enableOutput();

        // Run it
        $retval = $process->run();

        if (!$process->isSuccessful()) {
            $this->output->write('<error>' . $process->getErrorOutput() . '</error>', true);
        }

        echo $process->getOutput(), "\n";

        return $retval;
    }

    /**
     * Pull a remote branch
     *
     * @param string $repo   Repository name
     * @param string $branch Branch name
     *
     * @return integer
     */
    public function pullBranch($remote = null, $branch = null)
    {
        if ($remote && $branch) {
            $this->output->write("<info>Pulling $remote $branch</info>", true);
            $this->builder->setArguments(['pull', $remote, $branch]);
        } elseif ($remote) {
            $this->output->write("<info>Pulling $remote</info>", true);
            $this->builder->setArguments(['pull', $remote]);
        } else {
            $this->output->write('<info>Pulling branches remote</info>', true);
            $this->builder->setArguments(['pull']);
        }

        // Build the process
         $process = $this->builder
            ->getProcess()
            ->enableOutput();

        // Run it
        $retval = $process->run();

        if (!$process->isSuccessful()) {
            $this->output->write('<error>' . $process->getErrorOutput() . '</error>', true);
        }

        echo $process->getOutput(), "\n";

        return $retval;
    }

    /**
     * Checkout a remote's branch
     *
     * @param string $branch Branch
     *
     * @return integer
     */
    public function removeBranch($branch)
    {
        $this->output->write("<info>Removing $branch</info>", true);

        $this->builder->setArguments(['branch', '-D', $branch]);

        // Build the process
         $process = $this->builder
            ->getProcess()
            ->enableOutput();

        // Run it
        $retval = $process->run();

        if (!$process->isSuccessful()) {
            $this->output->write('<error>' . $process->getErrorOutput() . '</error>', true);
        }

        echo $process->getOutput(), "\n";

        return $retval;
    }

    /**
     * Fetch all remote branches
     *
     * @return integer
     */
    public function fetchAll()
    {
        $this->output->write('<info>Fetching all</info>', true);

        $process = $this->builder->setArguments(['fetch', '--all'])
            ->getProcess()
            ->enableOutput();

        // Run it
        $retval = $process->run();

        if (!$process->isSuccessful()) {
            $this->output->write('<error>' . $process->getErrorOutput() . '</error>', true);
        }

        echo $process->getOutput(), "\n";

        return $retval;
    }
}

/**
 * The command
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class CoverageCommand
{
    /** @var integer */
    private $prNum;

    /** @var string */
    private $test = null;

    /** @var array */
    private $args;

    /** @var \Symfony\Component\Console\Output\ConsoleOutput */
    private $output;

    /** @var Git */
    private $git;

    /** @var CoverageComparator */
    private $comparator;

    /** @var string */
    private $beforeFile;

    /** @var string */
    private $afterFile;

    /**
     *
     */
    public function __construct()
    {
        // Make sure we don't run out of time
        set_time_limit(0);

        // Output
        $this->output = new ConsoleOutput();

        // Options
        $this->getOpts();

        // Git
        $this->git = new Git();

        // CoverageComparator
        $this->comparator = new CoverageComparator();

        // PHPUnit coverage files
        $this->beforeFile = sys_get_temp_dir() . '/coverage-before.php';
        $this->afterFile  = sys_get_temp_dir() . '/coverage-after.php';
    }

    /**
     * Run Forrest, RUN!
     */
    public function run()
    {
        // Sync and test master
        if (!$this->runTestsMaster()) {
            exit(1);
        }

        // Checkout and test PR banch
        if (!$this->runTestsFork()) {
            exit(1);
        }

        // Get the comparison of runs
        $compare = $this->comparator->compareCoverageStats($this->beforeFile, $this->afterFile);

        // Output results to STDOUT
        echo $this->comparator->formatCoverageStats($compare), "\n";
    }

    /**
     * Output help text
     */
    private function help()
    {
        $this->output->write([
            '<info>php tests/scripts/coverage.php [PR number] [test]</info>',
            '<info>Where:</info>',
            '<info>    [PR number] - GitHub PR number (required)</info>',
            '<info>    [test]      - Directory or file to limit tests to (optional)</info>'
        ], true);
        exit;
    }

    /**
     * Set our opts from argv
     */
    private function getOpts()
    {
        $this->args = $_SERVER['argv'];

        // Display simple help if requested
        if (isset($this->args[1]) &&
            ($this->args[1] === 'help' ||
                $this->args[1] === '--help' ||
                $this->args[1] === '-h')
        ) {
            $this->help();
        }

        // Get the PR number to test
        if (!isset($this->args[1]) || !is_numeric($this->args[1])) {
            $this->output->write('<error>Minimum of a GitHub PR number is required as first argument</error>', true);
            exit(1);
        }
        $this->prNum = $this->args[1];

        // Get the test, if any to run
        $this->test = null;
        if (isset($this->args[2])) {
            $this->test = 'tests/phpunit/' . str_replace('tests/phpunit/', '', $this->args[2]);
        }
    }

    /**
     * Master test run
     *
     * @return boolean
     */
    private function runTestsMaster()
    {
        // Checkout and update master
        $this->git->checkoutBranch('master');
        $this->git->pullBranch('upstream', 'master');

        // Run test
        if ($this->comparator->runPhpUnitCoverage($this->beforeFile, $this->test) !== 0) {
            $this->output->write('<error>Failed to run PHPUnit test against master</error>', true);

            return false;
        }

        return true;
    }

    /**
     * Remote fork/branch tests
     *
     * @return boolean
     */
    private function runTestsFork()
    {
        $prDetails    = $this->git->getPr($this->prNum);
        $remoteName   = 'bolt-fork-' . $prDetails->head->repo->id;
        $remoteUrl    = $prDetails->head->repo->clone_url;
        $remoteBranch = $prDetails->head->ref;

        // Get PRs git remote and fetch all branches
        if ($this->git->addRemote($remoteName, $remoteUrl) === 0) {
            $this->git->fetchAll();
        } else {
            $this->output->write('<error>Failed to add the remote</error>', true);
            $this->git->delRemote($remoteName);

            return false;
        }

        // Checkout the PRs branch
        if ($this->git->checkoutBranch($remoteBranch, $remoteName) === 0) {
            if ($this->comparator->runPhpUnitCoverage($this->afterFile, $this->test) !== 0) {
                $this->output->write('<error>Failed to run PHPUnit test against PR branch</error>', true);
            }

            $result = true;
        } else {
            $result = false;
            $this->output->write('<error>Failed to checkout remote branch</error>', true);
        }

        // Clean up
        $this->git->checkoutBranch('master');
        $this->git->removeBranch($remoteBranch);
        $this->git->delRemote($remoteName);

        return $result;
    }
}

$command = new CoverageCommand();
$command->run();
