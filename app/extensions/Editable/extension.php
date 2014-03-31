<?php
namespace Editable;

require_once __DIR__ . '/src/ExtensionHelper.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Bolt\Content;

class Extension extends ExtensionHelper
{

    protected $controller;

    /**
     * Initialize extension
     */
    public function initialize()
    {
        parent::initialize();
        // @todo Is it a good idea to enable plugin on backend?

        if ($this->authorized) {
            $this->registerLoader();

            $config = $this->config;
            $this->controller = $this->createController($config['editor']);
            $this->controller->initialize($this->app);

            $this->addAssets($config);

            $this->app->post('/editable/save', array(
                $this,
                'save'
            ))
                ->method('POST')
                ->bind('saveit');
        }

        $this->addTwigFunction('editable', 'twigEditable');
    }

    /**
     * Builds and adds html assets are defined in config.yml
     */
    protected function addAssets($config)
    {
        $name = $config['editor'];
        $styles = $config['styles'] ?  : array();
        $scripts = $config['scripts'] ?  : array();
        $editor = $name . '.js';

        $this->addJquery();
        $this->addJavascript("{$name}/{$editor}", true);

        foreach ($styles as $item) {
            $this->addCSS("{$item}.css");
        }

        foreach ($scripts as $item) {
            $this->addJavascript("{$item}.js", true);
        }
    }

    /**
     * Receives save http request
     *
     * @param Application $app
     * @param Request $request
     * @return string
     */
    public function save(Application $app, Request $request)
    {
        $result = $this->controller->save($app, $request);
        return json_encode($result);
    }

    // because of some caching issues always the same twig function was calling so
    // can't add different php functions with same name to be able to handle different cases
    /**
     *
     * @param string $fieldname
     * @param string $record
     * @param unknown $options
     * @return \Twig_Markup
     */
    public function twigEditable($fieldname, $record = null, $options = array())
    {
        $html = '';
        $record = $record ?  : $this->getDefaultRecord();

        if ($this->authorized) {
            if ($record && $record instanceof \Bolt\Content) {
                $element = new EditableElement($this->app);
                $element->applyRecord($record, $fieldname);
                $html = $this->controller->getHtml($element, $record, $options);
            }
        } else {
            $html = $record ? $record->values[$fieldname] : '';
        }
        return new \Twig_Markup($html, 'UTF-8');
    }

    /**
     * Gets actual record in template context
     *
     * @return boolean Bolt\Content if no current record in template context
     */
    protected function getDefaultRecord()
    {
        $globals = $this->app['twig']->getGlobals('record');

        if (! isset($globals['record'])) {
            return false;
        }

        return $globals['record'];
    }
}
