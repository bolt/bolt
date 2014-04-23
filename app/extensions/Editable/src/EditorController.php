<?php
namespace Editable;

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Bolt\Content;

/**
 * Class for Editor specific functions.
 * Different kind of editors can be used for in-place editing. They may have different format markup in the page
 * and may handle its save requests differently from frontend.
 *
 * @author Rix
 *
 */
abstract class EditorController
{

    protected $extension;

    public function __construct(Extension $ext)
    {
        $this->extension = $ext;
    }

    /**
     * Implement any initialization related tasks here.
     * This will be called from extension::initialize()
     *
     * @param Application $app
     */
    public function initialize(Application $app)
    {}

    /**
     * Save request handler implementation required here
     *
     * @param Application $app
     * @param Request $request
     */
    abstract public function save(Application $app, Request $request);

    /**
     * Html markup builder implementation goes here.
     * It is called from twig for generating editable element markup.
     *
     * @param EditableElement $element
     * @param Content $record
     * @param array $options
     */
    abstract public function getHtml(EditableElement $element, Content $record, array $options = null);
}
