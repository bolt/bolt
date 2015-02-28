<?php

namespace Bolt\Filesystem\Plugin;

use Bolt\Library as Lib;
use League\Flysystem\FilesystemInterface;
use League\Flysystem\PluginInterface;
use utilphp\util;

class Browse implements PluginInterface
{
    /** @var FilesystemInterface */
    protected $filesystem;

    public function getMethod()
    {
        return 'browse';
    }

    public function setFilesystem(FilesystemInterface $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function handle($path)
    {
        $files = array();
        $folders = array();
        $list = $this->filesystem->listContents($path);

        $ignored = array(".", "..", ".DS_Store", ".gitignore", ".htaccess");

        foreach ($list as $entry) {
            if (in_array($entry['basename'], $ignored)) {
                continue;
            }

            if (!$this->filesystem->authorized($entry['path'])) {
                continue;
            }

            if ($entry['type'] === 'file') {
                try {
                    $url = $this->filesystem->url($entry['path']);
                } catch (\Exception $e) {
                    $url = $entry['path'];
                }

                // Ugh, for some reason the foldername for the theme is included twice. Why?
                // For now we 'fix' this with an ugly hack, replacing it. :-/
                // TODO: dig into Filesystem and figure out why this happens.
                $pathsegments = explode('/', $entry['path']);
                if (!empty($pathsegments[0])) {
                    $url = str_replace('/' . $pathsegments[0] . '/' . $pathsegments[0] . '/', '/' . $pathsegments[0] . '/', $url);
                }

                $files[$entry['path']] = array(
                    'path'         => $entry['dirname'],
                    'filename'     => $entry['basename'],
                    'newpath'      => $entry['path'],
                    'relativepath' => $entry['path'],
                    'writable'     => true,
                    'readable'     => false,
                    'type'         => isset($entry['extension']) ? $entry['extension'] : '',
                    'filesize'     => Lib::formatFilesize($entry['size']),
                    'modified'     => date("Y/m/d H:i:s", $entry['timestamp']),
                    'permissions'  => 'public',
                    'url'          => $url
                );

                /* **** Extra checks for files that can be resolved via PHP urlopen functions **** */
                try {
                    $files[$entry['path']]['permissions'] = $this->filesystem->getVisibility($entry['path']);
                } catch (\Exception $e) {
                }

                $fullfilename = $this->filesystem->getAdapter()->applyPathPrefix($entry['path']);
                if (is_readable($fullfilename)) {
                    $files[$entry['path']]['readable'] = true;

                    if (!empty($entry['extension']) && in_array($entry['extension'], array('gif', 'jpg', 'png', 'jpeg'))) {
                        $size = getimagesize($fullfilename);
                        $files[$entry['path']]['imagesize'] = sprintf("%s × %s", $size[0], $size[1]);
                    }

                    $files[$entry['path']]['permissions'] = util::full_permissions($fullfilename);
                }
            }

            if ($entry['type'] == 'dir') {
                $folders[$entry['path']] = array(
                    'path'       => $entry['dirname'],
                    'foldername' => $entry['basename'],
                    'newpath'    => $entry['path'],
                    'modified'   => date("Y/m/d H:i:s", $entry['timestamp']),
                    'writable'   => true
                );

                $fullfilename = $this->filesystem->getAdapter()->applyPathPrefix($entry['path']);

                /* **** Extra checks for files that can be resolved via PHP urlopen functions **** */
                if (is_readable($fullfilename)) {
                    if (!is_writable($fullfilename)) {
                        $folders[$entry['path']]['writable'] = false;
                    }
                }
            }
        }

        ksort($files);
        ksort($folders);

        return array($files, $folders);
    }
}
