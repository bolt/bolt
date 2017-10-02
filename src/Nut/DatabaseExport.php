<?php

namespace Bolt\Nut;

use Bolt\Collection\MutableBag;
use Bolt\Filesystem\Adapter\Local;
use Bolt\Filesystem\Filesystem;
use Bolt\Filesystem\Handler\JsonFile;
use Bolt\Filesystem\Handler\YamlFile;
use Bolt\Storage\Migration;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Webmozart\PathUtil\Path;

/**
 * Nut database exporter command.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class DatabaseExport extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('database:export')
            ->setDescription('Export the database records to a YAML or JSON file.')
            ->addOption('file',        'f', InputOption::VALUE_REQUIRED, 'A YAML or JSON file to use for export data. Must end with .yml, .yaml or .json')
            ->addOption('directory',   'd', InputOption::VALUE_REQUIRED, 'A destination directory. The command will automatically generate file names.')
            ->addOption('contenttype', 'c', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'ContentType name to export records for (can be used multiple times).')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get file & directory paths
        list($fileName, $dirPath) = $this->getResolvedPaths($input);
        $filesystem = new Filesystem(new Local($dirPath));
        /** @var JsonFile|YamlFile $file */
        $file = $filesystem->getFile($fileName);

        $this->io->warning('This command operates on the current database, taking a backup is advised before export.');
        if (!$this->io->confirm('Are you sure you want to continue with the export')) {
            return 1;
        }

        // Bag of ContentType names to export
        $exportContentTypes = (array) $input->getOption('contenttype') ?: array_keys($this->app['config']->get('contenttypes'));
        // Response bag
        $responseBag = MutableBag::fromRecursive(['error' => [], 'warning' => [], 'success' => []]);

        $migration = new Migration\Export($this->app['storage'], $this->app['query']);
        $exportData = $migration->run($exportContentTypes, $responseBag);

        // Dump the file
        $file->dump($exportData->toArrayRecursive(), ['inline' => 4]);

        $this->io->note('Exported:');
        $this->io->listing($responseBag->get('success')->toArray());

        $this->io->success('Database exported to ' . $fileName);

        return 0;
    }

    /**
     * @param InputInterface $input
     *
     * @throws RuntimeException
     *
     * @return array
     */
    private function getResolvedPaths(InputInterface $input)
    {
        $fileName = $input->getOption('file');
        $dirPath = $input->getOption('directory');

        if ($fileName === null && $dirPath === null) {
            throw new RuntimeException('Either the --file or --directory option is required.');
        }

        $fileName = $fileName ?: rtrim($dirPath, '/') . '/' . date('YmdHis') . '.yml';
        if (Path::isRelative($fileName)) {
            $fileName = Path::makeAbsolute($fileName, getcwd());
        }

        $dirPath = $dirPath ?: dirname($fileName);
        if (Path::isRelative($dirPath)) {
            $dirPath = Path::makeAbsolute($dirPath, getcwd());
        }

        return [basename($fileName), $dirPath];
    }
}
