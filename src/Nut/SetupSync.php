<?php

namespace Bolt\Nut;

use Bolt\Filesystem\Handler\Directory;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Nut command to perform Bolt web asset sync.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class SetupSync extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('setup:sync')
            ->setDescription('Synchronise a Bolt install private asset directories with the web root.')
            ->addOption('themes', 't', InputOption::VALUE_NONE, 'Copy example themes from bolt/themes into the site theme base-directory.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var \Bolt\Configuration\Environment $environment */
        $environment = $this->app['config.environment'];

        if ($input->getOption('themes')) {
            $this->mirrorThemes();
        }

        $this->io->title('Synchronising Bolt asset directories with the web root');
        $response = $environment->syncAssets();
        if ($response === null) {
            $this->io->success('Directory synchronisation succeeded​.');

            return 0;
        }
        $errors = ['Directory synchronisation encountered problems:'];
        foreach ($response as $message) {
            $errors[] = $message;
        }
        $this->io->error($errors);

        return 1;
    }

    /**
     * Mirror the bolt/themes themes with the public theme directory.
     */
    private function mirrorThemes()
    {
        $filesystem = $this->app['filesystem'];
        $themes = $filesystem->find()
            ->directories()
            ->in('root://vendor/bolt/themes')
            ->depth(0)
        ;

        $confirm = $this->io->confirm('Continuing will copy/update the example themes into your installation, overwriting older copies. Is this OK?');
        if (!$confirm) {
            $this->io->note('Skipping theme copy');

            return;
        }

        foreach ($themes as $theme) {
            /** @var Directory $theme */
            $origin = $theme->getFullPath();
            $target = 'themes://' . $theme->getFilename();

            $this->io->title('Installing theme: ' . $theme->getFilename());
            $filesystem->mirror($origin, $target, ['override' => true]);
            $this->io->success('');
        }
    }
}
