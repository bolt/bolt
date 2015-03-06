<?php

/**
 * Functionality to handle PHPUnit coverage comparision
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */

namespace Bolt\Tests;

use Guzzle\Http\Client as GuzzleClient;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessBuilder;
use Sirius\Validation\Rule\Integer;

include realpath(__DIR__ . '/../../vendor/autoload.php');

/**
 * Class for doing comparisons of PHPUnit code coverage reports
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class CoverageComparator
{
    /**
     * @var string Bolt's PHPUnit XML configuration file
     */
    private $xmlfile;

    /**
     *
     */
    public function __construct()
    {
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
                array(
                    '--configuration',
                    $this->xmlfile,
                    '--coverage-php',
                    $output,
                    $test))
            ->getProcess()
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
            die($process->getErrorOutput());
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
        $classCoverage = array();

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
                    $classCoverage[$namespace . $className] = array(
                        'namespace'         => $namespace,
                        'className '        => $className,
                        'methodsCovered'    => $coveredMethods,
                        'methodCount'       => $classMethods,
                        'statementsCovered' => $coveredClassStatements,
                        'statementCount'    => $classStatements,
                    );
                }
            }
        }

        return array(
            'classes'  => array(
                'total'  => $report->getNumClassesAndTraits(),
                'tested' => $report->getNumTestedClassesAndTraits()
            ),
            'methods'  => array(
                'total'  => $report->getNumMethods(),
                'tested' => $report->getNumTestedMethods()
            ),
            'lines'    => array(
                'total'  => $report->getNumExecutableLines(),
                'tested' => $report->getNumExecutedLines()
            ),
            'coverage' => $classCoverage
        );
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

        $increase = array(
            'classes'  => 0,
            'methods'  => 0,
            'lines'    => 0,
            'coverage' => array()
        );

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
    /** @var \Guzzle\Http\Client */
    private $client;

    /** @var \Symfony\Component\Process\ProcessBuilder */
    private $builder;

    /**
     *
     */
    public function __construct()
    {
        // Guzzle client
        $this->client = new GuzzleClient('https://api.github.com/repos/bolt/bolt/pulls/');

        // Symfony process
        $this->builder = new ProcessBuilder();

        // Assume that `git` is in the path
        $this->builder->setPrefix('git');
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
        $response = $this->client->get($pr)->send()->getBody();

        return json_decode($response);
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
        // Build the process
        $process = $this->builder->setArguments(array('remote', 'add', $name, $url))
            ->getProcess()
            ->enableOutput();

        // Run it
        $retval = $process->run();

        if (!$process->isSuccessful()) {
            die($process->getErrorOutput());
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
        // Build the process
        $process = $this->builder->setArguments(array('remote', 'remove', $name))
            ->getProcess()
            ->enableOutput();

        // Run it
        $retval = $process->run();

        if (!$process->isSuccessful()) {
            die($process->getErrorOutput());
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
            $this->builder->setArguments(array('checkout', '-b', $branch, "$name/$branch"));
        } else {
            $this->builder->setArguments(array('checkout', $branch));
        }

        // Build the process
         $process = $this->builder
            ->getProcess()
            ->enableOutput();

        // Run it
        $retval = $process->run();

        if (!$process->isSuccessful()) {
            die($process->getErrorOutput());
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
            $this->builder->setArguments(array('pull', $remote, $branch));
        } elseif ($branch) {
            $this->builder->setArguments(array('pull', $remote));
        } else {
            $this->builder->setArguments(array('pull', $remote));
        }

        // Build the process
         $process = $this->builder
            ->getProcess()
            ->enableOutput();

        // Run it
        $retval = $process->run();

        if (!$process->isSuccessful()) {
            die($process->getErrorOutput());
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
        $this->builder->setArguments(array('branch', '-D', $branch));

        // Build the process
         $process = $this->builder
            ->getProcess()
            ->enableOutput();

        // Run it
        $retval = $process->run();

        if (!$process->isSuccessful()) {
            die($process->getErrorOutput());
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
        $process = $this->builder->setArguments(array('fetch', '--all'))
            ->getProcess()
            ->enableOutput();

        // Run it
        $retval = $process->run();

        if (!$process->isSuccessful()) {
            die($process->getErrorOutput());
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

    /**
     *
     */
    public function __construct()
    {
        $this->args = $_SERVER['argv'];

        if (isset($this->args[1]) &&
            (isset($this->args[1]) === 'help' ||
                isset($this->args[1]) === '--help' ||
                isset($this->args[1]) === '-h')
        ) {
            $this->help();
        }

        // Get the PR number to test
        if (!isset($this->args[1]) || !is_numeric($this->args[1])) {
            die("Minimum of a GitHub PR number is required as first argument\n");
        }
        $this->prNum = $this->args[1];

        // Get the test, if any to run
        $this->test = null;
        if (isset($this->args[2])) {
            $this->test = 'tests/phpunit/' . str_replace('tests/phpunit/', '', $this->args[2]);
        }
    }

    public function run()
    {
        // PHPUnit coverage files
        $beforeFile = sys_get_temp_dir() . '/coverage-before.php';
        $afterFile  = sys_get_temp_dir() . '/coverage-after.php';

        // Classes
        $git = new Git();
        $comparator = new CoverageComparator();

        /*
         * Pull request data
        */
        $prDetails    = $git->getPr($prNum);
        $remoteName   = 'bolt-fork-' . $prDetails->head->repo->id;
        $remoteUrl    = $prDetails->head->repo->clone_url;
        $remoteBranch = $prDetails->head->ref;

        /*
         * Master test
         */
        // Checkout and update master
        $git->checkoutBranch('master');
        $git->pullBranch('upstream', 'master');

        // Run test
        if ($comparator->runPhpUnitCoverage($beforeFile, $test)) {
            die("Failed to run PHPUnit test against master\n");
        }

        /*
         * Remote tests
         */

        // Get PRs git remote and fetch all branches
        if ($git->addRemote($remoteName, $remoteUrl)) {
            $git->fetchAll();
        }

        // Checkout the PRs branch
        if ($git->checkoutBranch($remoteBranch, $remoteName)) {
            if ($comparator->runPhpUnitCoverage($afterFile, $test)) {
                $git->checkoutBranch('master');
                $git->removeBranch($remoteBranch);
                $git->delRemote($remoteName);

                die("Failed to run PHPUnit test against PR branch");
            }
        }

        // Clean up
        $git->checkoutBranch('master');
        $git->removeBranch($remoteBranch);
        $git->delRemote($remoteName);

        // Get the comparison of runs
        $compare = $comparator->compareCoverageStats($beforeFile, $afterFile);

        // Output results to STDOUT
        echo $comparator->formatCoverageStats($compare), "\n";
    }

    private function help()
    {
        echo "php tests/scripts/coverage.php [PR number] [test]\n\n";
        echo "Where:\n";
        echo "\t[PR number]\t- GitHub PR number (required)\n";
        echo "\t[test]\t\t- Directory or file to limit tests to (optional)\n";
        exit;
    }
}

$command = new CoverageCommand();
$command->run();

