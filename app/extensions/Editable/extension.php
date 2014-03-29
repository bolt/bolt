<?php
namespace Editable;

require_once __DIR__ . '/src/EditableElement.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class Extension extends \Bolt\BaseExtension
{

    protected $authorized = false;

    protected $config;

    /**
     *
     * @return array
     */
    public function info()
    {
        return array(
            'name' => "Editable",
            'description' => "Edit content where it is",
            'tags' => array(
                'content',
                'editor',
                'admin',
                'tool'
            ),
            'type' => "Administrative Tool",
            'author' => "Rix Beck / Neologik Team",
            'link' => "http://www.neologik.hu",
            'email' => 'rix@neologik.hu',
            'version' => "0.2",

            'required_bolt_version' => "1.5.2",
            'highest_bolt_version' => "1.5.2",
            'first_releasedate' => "2014-03-31",
            'latest_releasedate' => "2014-03-31"
        );
    }

    /**
     * Initialize extension
     */
    public function initialize()
    {
        // @todo Is it a good idea to enable plugin on backend?
        $this->config = $this->getConfig();

        if (! isset($this->config['permissions']) || ! is_array($this->config['permissions'])) {
            $this->config['permissions'] = array(
                'root',
                'admin',
                'developer'
            );
        } else {
            $this->config['permissions'][] = 'root';
        }

        $currentUser = $this->app['users']->getCurrentUser();
        $currentUserId = $currentUser['id'];

        foreach ($this->config['permissions'] as $role) {
            if ($this->app['users']->hasRole($currentUserId, $role)) {
                $this->authorized = true;
                break;
            }
        }

        if ($this->authorized) {

            $editorjs = $this->config['editorjs'];
            $editorcss = $this->config['editorcss'];
            $startup = $this->config['startup'];

            $this->addJquery();
            $this->addCSS("assets/{$editorcss}");
            $this->addJavascript("assets/{$editorjs}", true);
            $this->addJavascript("assets/{$startup}", true);

            $this->app->post('/edit/saveit', array(
                $this,
                'saveit'
            ))
                ->method('POST')
                ->bind('saveit');
        }

        $this->addTwigFunction('editable', 'twigEditable');
    }

    public function saveit(Application $app, Request $request)
    {
        $rawdata = $request->request->get('editcontent');
        $data = json_decode($rawdata, true);
        $parameters = $data['parameters'];

        $element = new EditableElement($app);
        $element->id = $parameters['id'];
        $element->contenttypeslug = $parameters['contenttypeslug'];
        $element->token = $parameters['token'];
        $element->fieldname = $parameters['fieldname'];

        $contentprop = $element->getElementContentId();
        $content = $data[$contentprop];

        $result = $element->save($content);
        return json_encode((bool) $result);
    }

    // because of some caching issues always the same twig function was calling so
    // can't add different php functions with same name to be able to handle different cases
    public function twigEditable($fieldname, $record = null, $options = array())
    {
        $html = '';
        $record = $record ?  : $this->getDefaultRecord();

        if ($this->authorized) {
            if ($record && $record instanceof \Bolt\Content) {
                $element = new EditableElement($this->app);
                $element->applyRecord($record, $fieldname);
                $contentid = $element->getElementContentId();

                $encparms = htmlspecialchars(json_encode($element));
                $html = "<editable data-content_id=\"{$contentid}\"";
                $html .= $options ? "data-options='" . json_encode($options) . "'" : "";
                $html .= "data-parameters='{$encparms}'>" . $record->values[$fieldname] . "</editable>";
            }
        } else {
            $html = $record ? $record->values[$fieldname] : '';
        }
        return new \Twig_Markup($html, 'UTF-8');
    }

    protected function getDefaultRecord()
    {
        $globals = $this->app['twig']->getGlobals('record');

        if (! isset($globals['record'])) {
            return false;
        }

        return $globals['record'];
    }
}