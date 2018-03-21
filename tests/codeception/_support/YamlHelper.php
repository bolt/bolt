<?php

namespace Codeception\Module;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;

/**
 * YAML Helper class.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class YamlHelper extends \Codeception\Module
{
    /**
     * Read a YAML file.
     *
     * @param string $file
     *
     * @throws IOException
     *
     * @return array
     */
    private function readYaml($file)
    {
        $fileName = INSTALL_ROOT . '/' . $file;
        $parser = new Parser();

        if (is_readable($fileName)) {
            return $parser->parse(file_get_contents($fileName) . "\n");
        }

        throw new IOException($fileName . ' is not readable!');
    }

    /**
     * Read the config file and set 'canonical' and 'notfound'.
     *
     * @return string
     */
    public function getUpdatedConfig()
    {
        $config = $this->readYaml('app/config/config.yml');

        $config['canonical'] = 'example.org';
        $config['notfound'] = 'resources/not-found';
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
     * @return array
     */
    private function getBasePermissions()
    {
        $permissions = $this->readYaml('app/config/permissions.yml');

        $permissions['contenttype-all'] = [
            'edit'             => ['developer', 'admin', 'chief-editor'],
            'create'           => ['developer', 'admin', 'chief-editor'],
            'publish'          => ['developer', 'admin', 'chief-editor'],
            'depublish'        => ['developer', 'admin', 'chief-editor'],
            'delete'           => ['developer', 'admin'],
            'change-ownership' => ['developer', 'admin'],
        ];

        $permissions['contenttype-default'] = [
            'view'             => ['anonymous'],
            'create'           => ['editor'],
            'edit'             => ['editor'],
            'change-ownership' => ['owner'],
        ];

        $permissions['contenttypes'] = [
            'homepage'     => [
                'create'           => ['editor'],
                'edit'             => ['editor'],
                'publish'          => ['editor'],
            ],
            'pages'     => [
                'create'           => ['editor'],
                'edit'             => ['editor', 'author'],
                'change-ownership' => ['owner'],
            ],
            'entries'   => [
                'view'             => ['admin'],
                'create'           => ['admin'],
            ],
            'showcases' => [
                'create'           => ['admin'],
                'edit'             => ['admin', 'editor'],
                'publish'          => ['admin'],
                'change-ownership' => [],
            ],
        ];

        return $permissions;
    }

    /**
     * Add a 'Resources' Contenttype.
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
    public function getUpdatedContentTypes()
    {
        $contentTypes = $this->readYaml('app/config/contenttypes.yml');

        $contentTypes['resources'] = [
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
                    'height' => '300px',
                ],
            ],
            'default_status'    => 'published',
            'show_on_dashboard' => false,
            'searchable'        => false,
            'viewless'          => true,
        ];

        return $this->getYamlString($contentTypes, 4);
    }

    /**
     * Read our taxonomy and sort the category options.
     *
     * @return string
     */
    public function getUpdatedTaxonomy()
    {
        $taxonomy = $this->readYaml('app/config/taxonomy.yml');

        $options = $taxonomy['categories']['options'];
        sort($options);
        $taxonomy['categories']['options'] = $options;

        return $this->getYamlString($taxonomy, 2);
    }

    /**
     * Read the menu file and add a menu for the Showcase listing.
     *
     * @return string
     */
    public function getUpdatedMenu()
    {
        $menus = $this->readYaml('app/config/menu.yml');

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

        $routing = [
            'pagebinding:',
            '    path: /{slug}',
            '    defaults:',
            '        _controller: controller.frontend:record',
            '        contenttypeslug: page',
            '    contenttype: pages',
            '',
            file_get_contents($filename),
            '',
        ];

        return implode("\n", $routing);
    }

    public function getUpdatedTheme()
    {
        $theme = $this->readYaml('theme/base-2018/theme.yml');

        /**
         * Disabled as currently unsupported due to problems in test due to
         * |first filter in base-2016:
         *
         * @see https://github.com/bolt/bolt/blob/v3.2.16/theme/base-2016/partials/_sub_fields.twig#L104
         */
        unset($theme['templatefields']['extrafields.twig']);

        $theme['templatefields']['page.twig'] = [
            'text'        => ['type' => 'text'],
            'html'        => ['type' => 'html'],
            'textarea'    => ['type' => 'textarea'],
            'markdown'    => ['type' => 'markdown'],
            'geolocation' => ['type' => 'geolocation'],
            'video'       => ['type' => 'video'],
            'image'       => [
                'type'       => 'image',
                'attrib'     => 'title',
                'extensions' => ['gif', 'jpg', 'png'],
            ],
            'imagelist' => ['type' => 'imagelist'],
            'file'      => ['type' => 'file'],
            'filelist'  => ['type' => 'filelist'],
            'checkbox'  => ['type' => 'checkbox'],
            /**
             * Disabled as currently unsupported due to bug in persistence
             */
            //'date' => [
            //    'type'    => 'date',
            //    'default' => 'first day of last month',
            //],
            //'datetime'  => [
            //    'type'    => 'datetime',
            //    'default' => '2000-01-01',
            //],
            'integer' => [
                'type'  => 'integer',
                'index' => true,
            ],
            'float'      => ['type' => 'float'],
            'select_map' => [
                'type'   => 'select',
                'values' => [
                    'unknown'  => 'Unknown',
                    'home'     => 'Home',
                    'business' => 'Business',
                ],
            ],
            'select_list' => [
                'type'   => 'select',
                'values' => ['foo', 'bar', 'baz'],
            ],
            'select_multi' => [
                'type'     => 'select',
                'values'   => ['A-tuin', 'Donatello', 'Rafael', 'Leonardo', 'Michelangelo', 'Koopa', 'Squirtle'],
                'multiple' => true,
            ],
            'select_record' => [
                'type'         => 'select',
                'values'       => 'pages/id,title',
                'sort'         => 'title',
            ],
            'select_record_single' => [
                'type'         => 'select',
                'values'       => 'pages/title',
                'sort'         => 'title',
            ],
            'select_record_keys' => [
                'type'         => 'select',
                'values'       => 'pages/title',
                'sort'         => 'title',
                'keys'         => 'slug',
            ],
            /**
             * Disabled as currently unsupported due to problems in extension
             * fields, and in test due to |first filter in base-2016:
             *
             * @see https://github.com/bolt/bolt/blob/v3.2.16/theme/base-2016/partials/_sub_fields.twig#L104
             */
            //'repeater' => [
            //    'type'   => 'repeater',
            //    'limit'  => 3,
            //    'fields' => [
            //        'repeat_title' => ['type' => 'text'],
            //        'repeat_image' => [
            //            'type'       => 'image',
            //            'extensions' => ['gif', 'jpg', 'png'],
            //        ],
            //        'repeat_html' => ['type' => 'html'],
            //    ],
            //],
        ];

        return $this->getYamlString($theme, 6);
    }

    /**
     * Get the YAML in a string.
     *
     * @param array   $input  The PHP value
     * @param int $inline The level where you switch to inline YAML
     * @param mixed   $depth
     *
     * @return string
     */
    private function getYamlString(array $input, $inline)
    {
        $dumper = new Dumper();
        $out = $dumper->dump($input, $inline);

        return str_replace('{  }', '[ ]', $out);
    }
}
