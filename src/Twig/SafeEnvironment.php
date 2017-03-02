<?php

namespace Bolt\Twig;

use Bolt\Helpers\Deprecated;
use Twig_Environment as Environment;
use Twig_Extension_Sandbox as Sandbox;
use Twig_ExtensionInterface as ExtensionInterface;

/**
 * Wraps real Twig environment:
 * - render() and display() are called with sandbox enabled.
 * - Adding an extension here adds the tags/functions/filters in extension to the security policy whitelist.
 *
 * @deprecated since 3.3, will be removed in 4.0.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
class SafeEnvironment extends TwigEnvironmentWrapper
{
    protected $sandbox;

    /**
     * Constructor.
     *
     * @param Environment $env
     * @param Sandbox     $sandbox
     */
    public function __construct(Environment $env, Sandbox $sandbox)
    {
        Deprecated::method(3.3);

        parent::__construct($env);
        $this->sandbox = $sandbox;
    }

    /**
     * {@inheritdoc}
     */
    public function render($name, array $context = [])
    {
        Deprecated::method(3.3, 'Use Twig_Environment::render with sandbox enabled.');

        $weSandboxed = false;
        if (!$this->sandbox->isSandboxed()) {
            $weSandboxed = true;
            $this->sandbox->enableSandbox();
        }

        try {
            $result = $this->env->render($name, $context);
        } finally {
            if ($weSandboxed) {
                $this->sandbox->disableSandbox();
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function display($name, array $context = [])
    {
        Deprecated::method(3.3, 'Use Twig_Environment::display with sandbox enabled.');

        $weSandboxed = false;
        if (!$this->sandbox->isSandboxed()) {
            $weSandboxed = true;
            $this->sandbox->enableSandbox();
        }

        try {
            $this->env->display($name, $context);
        } finally {
            if ($weSandboxed) {
                $this->sandbox->disableSandbox();
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addExtension(ExtensionInterface $extension)
    {
        Deprecated::method(3.3, 'Use Twig_Environment::addExtension instead and add to sandbox security policy directly.');

        $policy = $this->sandbox->getSecurityPolicy();

        if (!$policy instanceof SecurityPolicy) {
            return;
        }

        foreach ($extension->getTokenParsers() as $tokenParser) {
            $policy->addAllowedTag($tokenParser->getTag());
        }

        foreach ($extension->getFunctions() as $function) {
            $policy->addAllowedFunction($function->getName());
        }

        foreach ($extension->getFilters() as $filter) {
            $policy->addAllowedFilter($filter->getName());
        }
    }
}
