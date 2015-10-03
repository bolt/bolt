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
        $filename = INSTALL_ROOT . '/app/config/' . $file;
        $parser = new Parser();

        if (is_readable($filename)) {
            return $parser->parse(file_get_contents($filename) . "\n");
        } else {
            throw new IOException($filename . ' is not readable!');
        }
    }

    /**
     * Read the config file and set 'canonical' and 'notfound'
     *
     * @return string
     */
    public function getUpdatedConfig()
    {
        $config = $this->readYaml('config.yml');

        $config['canonical'] = 'example.org';
        $config['notfound']  = 'resources/not-found';
        $config['changelog'] = ['enabled' => true];

        return $this->getYamlString($config, 5);
    }

    /**
     * Update the permissions.yml array that we'll use.
     *
     * @return string
     */
    public function getUpdatedPermissions()
    {
        return $this->getYamlString($this->getBasePermissions(), 3);
    }

    /**
     * Get the Lemmings worthy permissions.yml array that we'll use.
     *
     * @return string
     */
    public function getLemmingsPermissions()
    {
        $permissions = $this->getBasePermissions();
        $permissions['global']['dashboard'] = [];

        return $this->getYamlString($permissions, 3);
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
     *
     * @return string
     */
    private function getBasePermissions()
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
                'edit'             => ['editor', 'author'],
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

        return $permissions;
    }

    /**
     * Add a 'Resources' Contenttype
     *
     * ```
     * resources:
     *     name: Resourcess
     *     singular_name: Resource
     *     fields:
     *         title:
     *             type: text
     *             class: large
     *             group: content
     *         slug:
     *             type: slug
     *             uses: title
     *         body:
     *             type: html
     *             height: 300px
     *     default_status: published
     *     show_on_dashboard: false
     *     searchable: false
     *     viewless: true
     * ```
     *
     * @return string
     */
    public function getUpdatedContenttypes()
    {
        $contenttypes = $this->readYaml('contenttypes.yml');

        $contenttypes['resources'] = [
            'name'          => 'Resources',
            'singular_name' => 'Resource',
            'fields'        => [
                'title' => [
                    'type'  => 'text',
                    'class' => 'large',
                ],
                'slug' => [
                    'type' => 'slug',
                    'uses' => 'title',
                ],
                'body' => [
                    'type'   => 'html',
                    'height' => '300px'
                ]
            ],
            'default_status'    => 'published',
            'show_on_dashboard' => false,
            'searchable'        => false,
            'viewless'          => true
        ];

        return $this->getYamlString($contenttypes, 4);
    }

    /**
     * Read our taxonomy and sort the category options.
     *
     * @return string
     */
    public function getUpdatedTaxonomy()
    {
        $taxonomy = $this->readYaml('taxonomy.yml');

        $options = $taxonomy['categories']['options'];
        sort($options);
        $taxonomy['categories']['options'] = $options;

        return $this->getYamlString($taxonomy, 2);
    }

    /**
     * Read the menu file and add a menu for the Showcase listing
     *
     * @return string
     */
    public function getUpdatedMenu()
    {
        $menus = $this->readYaml('menu.yml');

        $menus['main'][] = ['label' => 'Showcases Listing', 'path' => 'showcases/'];

        return $this->getYamlString($menus, 5);
    }

    /**
     * Read the routing file and add a pagebinding route… Hackishly!
     *
     * @return string
     */
    public function getUpdatedRouting()
    {
        $filename = INSTALL_ROOT . '/app/config/routing.yml';

        $routing = file_get_contents($filename) . "\n";
        $routing .= "pagebinding:\n";
        $routing .= "    path: /{slug}\n";
        $routing .= "    defaults: { _controller: 'Bolt\Controllers\Frontend::record', 'contenttypeslug': 'page' }\n";
        $routing .= "    contenttype: pages\n";

        return $routing;
    }

    /**
     * Get the YAML in a string
     *
     * @param array   $input  The PHP value
     * @param integer $inline The level where you switch to inline YAML
     *
     * @return string
     */
    private function getYamlString(array $yaml, $depth)
    {
        $dumper = new Dumper();
        $out = $dumper->dump($yaml, $depth);

        return str_replace('{  }', '[ ]', $out);
    }
}
