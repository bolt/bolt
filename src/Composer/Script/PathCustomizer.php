<?php

namespace Bolt\Composer\Script;

use Bolt\Configuration\PathResolver;
use Bolt\Nut\Helper\Table;
use Bolt\Nut\Output\OverwritableOutput;
use Bolt\Nut\Output\OverwritableOutputInterface;
use Bolt\Nut\Style\NutStyle;
use Composer\IO\ConsoleIO;
use Composer\IO\IOInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Webmozart\PathUtil\Path;

/**
 * Interactively allows CLI user to modify PathResolver paths.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
final class PathCustomizer
{
    /** @var PathResolver */
    private $resolver;
    /** @var InputInterface */
    private $input;
    /** @var OverwritableOutputInterface */
    private $output;
    /** @var NutStyle */
    private $io;
    /** @var Table */
    private $pathsTable;

    /**
     * Constructor.
     *
     * @param PathResolver    $resolver
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    public function __construct(PathResolver $resolver, InputInterface $input, OutputInterface $output)
    {
        if ($output instanceof ConsoleOutputInterface) {
            $output = $output->getErrorOutput();
        }
        if (!$output instanceof OverwritableOutputInterface) {
            $output = new OverwritableOutput($output);
        }

        $this->resolver = $resolver;
        $this->input = $input;
        $this->output = $output;

        $this->io = new NutStyle($input, $output);
    }

    /**
     * Create PathCustomizer from Composer's IOInterface.
     *
     * @param PathResolver $resolver
     * @param IOInterface  $io
     *
     * @return PathCustomizer
     */
    public static function fromComposer(PathResolver $resolver, IOInterface $io)
    {
        $input = new ArrayInput([]);
        $input->setInteractive($io->isInteractive());

        if ($io instanceof ConsoleIO) {
            $ref = new \ReflectionProperty($io, 'output');
            $ref->setAccessible(true);
            $output = $ref->getValue($io);
        } else {
            $output = new NullOutput();
        }

        return new static($resolver, $input, $output);
    }

    /**
     * Run the customizer.
     */
    public function run()
    {
        if (!$this->input->isInteractive()) {
            return;
        }

        $cwd = getcwd();
        $info = <<<OUT

All paths are currently relative to the current working directory (`$cwd`)

Paths can have aliases to other paths. These are specified with a word surrounded by percents.
For example, if we defined `user` as `/Users/Me`, then `%user%/my-site` would resolve to `/Users/Me/my-site`

OUT;
        // Replace backticks with actual styles
        $info = preg_replace_callback('/`([^`]*)`/', function ($match) {
            return "<comment>{$match[1]}</comment>";
        }, $info);
        $this->output->writeln($info);

        do {
            $this->renderPaths();

            $name = $this->askPathToModify();
            if ($name === null) {
                break;
            }

            $this->askAndSetNewPathValue($name);
        } while (true);
    }

    /**
     * Render paths table.
     */
    protected function renderPaths()
    {
        if (!$this->pathsTable) {
            $this->pathsTable = new Table($this->output);
            $style = clone Table::getStyleDefinition('symfony-style-guide');
            $this->pathsTable->setStyle($style);

            $this->pathsTable->setHeaders(['#', 'Name', 'Path']);

            $style = clone $style;
            $style->setCellHeaderFormat('<info>%s</info>');
            $this->pathsTable->setColumnStyle(0, $style);
        }

        $i = 0;
        $rows = [];
        $root = $this->resolver->resolve('root');
        foreach ($this->resolver->names() as $name) {
            $raw = $this->resolver->raw($name);
            $resolved = $this->resolver->resolve($name);
            $resolved = Path::makeRelative($resolved, $root);
            if ($resolved === '') {
                $resolved = '.';
            }
            $path = "<comment>$raw</comment>";
            if ($raw !== $resolved) {
                $path .= " ($resolved)";
            }
            $rows[] = ['<info>' . ++$i . '</info>', "<info>$name</info>", $path];
        }
        $this->pathsTable->setRows($rows);

        $this->pathsTable->overwrite();
    }

    /**
     * Ask which path to modify.
     *
     * @return string|null The path name to modify or null to finish.
     */
    protected function askPathToModify()
    {
        $names = $this->resolver->names();
        $count = count($names);

        $question = new Question('Enter # or name to modify or empty to continue');
        $question->setValidator(function ($value) use ($names, $count) {
            if ($value === null) {
                return $value;
            }

            if (!is_numeric($value) && array_search($value, $names) !== false) {
                return $value;
            } elseif ($value > 0 && $value <= $count) {
                return $names[$value - 1];
            }

            throw new \Exception("Please enter a name or a number between 1 and $count.");
        });
        $question->setAutocompleterValues($names);

        return $this->io->askQuestionThenRemove($question);
    }

    /**
     * Ask for the new value for the given path name and set it.
     *
     * @param string $name
     */
    protected function askAndSetNewPathValue($name)
    {
        $previous = $this->resolver->raw($name);
        $question = new Question('Enter new value for ' . $name, $previous);
        $question->setValidator(function ($value) use ($name, $previous) {
            $this->resolver->define($name, $value);
            // verify valid path.
            try {
                $this->resolver->resolve($name);
            } catch (\Exception $e) {
                $this->resolver->define($name, $previous); // revert path change
                throw $e;
            }
        });
        $this->io->askQuestionThenRemove($question);
    }
}
