<?php

namespace Authenticate;

/**
 * Twig functions
 */
class VisitorsTwigExtension extends \Twig_Extension
{
    private $twig = null;
    private $controller = null;

    public function __construct(Controller $controller)
    {
        $this->controller = $controller;
    }

    public function initRuntime(\Twig_Environment $environment)
    {
        $this->twig = $environment;
    }

    /**
     * Return the name of the extension
     */
    public function getName()
    {
        return 'visitors';
    }

    /**
     * The functions we add
     */
    public function getFunctions()
    {
        return array(
            'knownvisitor' =>  new \Twig_Function_Method($this, 'checkvisitor'),
            'showvisitorlogin' =>  new \Twig_Function_Method($this, 'showvisitorlogin'),
            'showvisitorlogout' =>  new \Twig_Function_Method($this, 'showvisitorlogout'),
            'showvisitorprofile' =>  new \Twig_Function_Method($this, 'showvisitorprofile'),
            'getvisitorprofile' =>  new \Twig_Function_Method($this, 'getvisitorprofile'),
            'settingslist' => new \Twig_Function_Method($this, 'settingslist'),
        );
    }

    public function checkvisitor()
    {
        return $this->controller->checkvisitor();
    }

    public function showvisitorlogin()
    {
        return $this->controller->showvisitorlogin();
    }

    public function showvisitorlogout($label = "Logout")
    {
        return $this->controller->showvisitorlogout($label);
    }

    public function showvisitorprofile()
    {
        return $this->controller->showvisitorprofile();
    }

    public function getvisitorprofile($id)
    {
        return $this->controller->getvisitorprofile($id);
    }

    public function settingslist()
    {
        return $this->controller->settingsList();
    }
}
