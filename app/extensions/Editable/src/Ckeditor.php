<?php
namespace Editable;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Bolt\Content;

class Ckeditor extends EditorController
{

    /**
     *
     * @see EditorController::initialize()
     */
    public function initialize(Application $app)
    {
        $weblib = 'view/lib';
        $webbase = ((BOLT_COMPOSER_INSTALLED) ? '/bolt-public/' : $app['paths']['app']) . $weblib;
        $filebase = BOLT_WEB_DIR . ((BOLT_COMPOSER_INSTALLED) ? '/bolt-public/' : $app['paths']['app']) . $weblib;
        $this->extension->addResourcePath($filebase, $webbase);


         // Fallback to view/js for eg. jquery-ui if necessary

        /*
         * $webjs = 'view/js';
         * $webbase = ((BOLT_COMPOSER_INSTALLED) ? '/bolt-public/' : '/') . $webjs;
         * $filebase = BOLT_WEB_DIR . ((BOLT_COMPOSER_INSTALLED) ? '/bolt-public/' : '/') . $webjs;
         * $this->extension->addResourcePath($filebase, $webbase);
         */
    }

    /**
     *
     * @see EditorController::save();
     */
    public function save(Application $app, Request $request)
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

        $id = $element->save($content);
        return $id;
    }

    /**
     *
     * @see EditorController::getHtml();
     */
    public function getHtml(EditableElement $element, Content $record, array $options = null)
    {
        $contentid = $element->getElementContentId();
        $encparms = htmlspecialchars(json_encode($element));
        $html = "<section class=\"bolt-ext-editable\" data-content_id=\"{$contentid}\" contenteditable=\"true\"";
        $html .= $options ? ' data-options="' . htmlspecialchars(json_encode($options)) . '"' : '';
        $html .= " data-parameters=\"{$encparms}\">" . $record->values[$element->fieldname] . "</section>";
        return $html;
    }
}
