<?php

namespace Codeception\Module;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;

/**
 * YAML Helper class
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class YamlHelper extends \Codeception\Module
{
    /**
     * Read a YAML file from app/config/
     *
     * @param string $file
     *
     * @throws IOException
     *
     * @return array
     */
    private function readYaml($file)
    {
        $filename = PROJECT_ROOT . '/app/config/' . $file;
        $parser = new Parser();

        if (is_readable($filename)) {
            return $parser->parse(file_get_contents($filename) . "\n");
        } else {
            throw new IOException($filename . ' is not readable!');
        }
    }

    /**
     * Update the permissions.yml array that we'll use.
     *
     * The intended layout is presently:
     *
     * ```
     *     contenttype-all:
     *         edit: [ developer, admin, chief-editor ]
     *         create: [ developer, admin, chief-editor ]
     *         publish: [ developer, admin, chief-editor ]
     *         depublish: [ developer, admin, chief-editor ]
     *         delete: [ developer, admin ]
     *         change-ownership: [ developer, admin ]
     *
     *     contenttype-default:
     *         view: [ anonymous ]
     *         create: [ editor ]
     *         edit: [ editor ]
     *         change-ownership: [ owner ]
     *
     *     contenttypes:
     *         pages:
     *             create: [ editor ]
     *             edit: [ editor ]
     *             change-ownership: [ owner ]
     *         entries:
     *             view: [ admin ]
     *             create: [ admin ]
     *         showcases:
     *             create: [ admin ]
     *             edit: [ admin, editor ]
     *             publish: [ admin ]
     *             change-ownership: [ ]
     * ```
     * @return string
     */
    public function getUpdatedPermissions()
    {
        $permissions = $this->readYaml('permissions.yml');

        $permissions['contenttype-all'] = [
            'edit'             => ['developer', 'admin', 'chief-editor'],
            'create'           => ['developer', 'admin', 'chief-editor'],
            'publish'          => ['developer', 'admin', 'chief-editor'],
            'depublish'        => ['developer', 'admin', 'chief-editor'],
            'delete'           => ['developer', 'admin'],
            'change-ownership' => ['developer', 'admin']
        ];

        $permissions['contenttype-default'] = [
            'view'             => ['anonymous'],
            'create'           => ['editor'],
            'edit'             => ['editor'],
            'change-ownership' => ['owner']
        ];

        $permissions['contenttypes'] = [
            'pages'     => [
                'create'           => ['editor'],
                'edit'             => ['editor'],
                'change-ownership' => ['owner']
            ],
            'entries'   => [
                'view'             => ['admin'],
                'create'           => ['admin']
            ],
            'showcases' => [
                'create'           => ['admin'],
                'edit'             => ['admin', 'editor'],
                'publish'          => ['admin'],
                'change-ownership' => []
            ]
        ];

        $dumper = new Dumper();
        $out = $dumper->dump($permissions, 3);

        return str_replace('{  }', '[ ]' , $out);
    }
}