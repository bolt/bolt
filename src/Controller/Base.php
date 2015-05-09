<?php
namespace Bolt\Controller;

use Bolt\Routing\DefaultControllerClassAwareInterface;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Base class for all controllers which mainly provides shortcut methods for
 * application services.
 *
 * @author Carson Full <carsonfull@gmail.com>
 */
abstract class Base implements ControllerProviderInterface
{
    /** @var Application */
    protected $app;

    public function connect(Application $app)
    {
        $this->app = $app;

        $c = $app['controllers_factory'];
        if ($c instanceof DefaultControllerClassAwareInterface) {
            $c->setDefaultControllerClass($this);
        }
        $this->addRoutes($c);

        return $c;
    }

    abstract protected function addRoutes(ControllerCollection $c);

    /**
     * Shortcut to abort the current request by sending a proper HTTP error.
     *
     * @param integer $statusCode The HTTP status code
     * @param string  $message    The status message
     * @param array   $headers    An array of HTTP headers
     */
    protected function abort($statusCode, $message = '', array $headers = array())
    {
        $this->app->abort($statusCode, $message, $headers);
    }

    /**
     * Renders a template
     *
     * @param string $template  the template name
     * @param array  $variables array of context variables
     * @param array  $globals   array of global variables
     *
     * @return \Bolt\Response\BoltResponse
     */
    protected function render($template, array $variables = array(), array $globals = array())
    {
        return $this->app['render']->render($template, $variables, $globals);
    }

    /**
     * Convert some data into a JSON response.
     *
     * @param mixed $data    The response data
     * @param int   $status  The response status code
     * @param array $headers An array of response headers
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    protected function json($data = array(), $status = 200, array $headers = array())
    {
        return new JsonResponse($data, $status, $headers);
    }

    /**
     * Creates and returns a Form instance from the type of the form.
     *
     * @param string|FormTypeInterface $type    The built type of the form
     * @param mixed                    $data    The initial data for the form
     * @param array                    $options Options for the form
     *
     * @return Form
     */
    protected function createForm($type = 'form', $data = null, array $options = array())
    {
        return $this->app['form.factory']->create($type, $data, $options);
    }

    /**
     * Returns a form builder.
     *
     * @param string|FormTypeInterface $type    The type of the form
     * @param mixed                    $data    The initial data
     * @param array                    $options The options
     *
     * @return FormBuilderInterface The form builder
     */
    protected function createFormBuilder($type = 'form', $data = null, array $options = array())
    {
        return $this->app['form.factory']->createBuilder($type, $data, $options);
    }

    /**
     * Shortcut for {@see UrlGeneratorInterface::generate}
     *
     * @param string $name          The name of the route
     * @param array  $params        An array of parameters
     * @param bool   $referenceType The type of reference to be generated (one of the constants)
     *
     * @return string
     */
    protected function generateUrl($name, $params = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        /** @var UrlGeneratorInterface $generator */
        $generator = $this->app['url_generator'];
        return $generator->generate($name, $params, $referenceType);
    }

    /**
     * Redirects the user to another URL.
     *
     * @param string $url    The URL to redirect to
     * @param int    $status The status code (302 by default)
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function redirect($url, $status = 302)
    {
        return new RedirectResponse($url, $status);
    }

    /**
     * Returns a RedirectResponse to the given route with the given parameters.
     *
     * @param string $route      The name of the route
     * @param array  $parameters An array of parameters
     * @param int    $status     The status code to use for the Response
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function redirectToRoute($route, array $parameters = array(), $status = 302)
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }

    /**
     * Returns the session.
     *
     * @return \Symfony\Component\HttpFoundation\Session\Session
     */
    protected function getSession()
    {
        return $this->app['session'];
    }

    /**
     * Adds a flash message to the current session for type.
     *
     * @param string $type    The type
     * @param string $message The message
     */
    protected function addFlash($type, $message)
    {
        $this->getSession()->getFlashBag()->add($type, $message);
    }

    /**
     * Shortcut for {@see \Bolt\Users::isAllowed}
     *
     * @param string      $what
     * @param string|null $contenttype
     * @param int|null    $contentid
     *
     * @return bool
     */
    protected function isAllowed($what, $contenttype = null, $contentid = null)
    {
        return $this->app['users']->isAllowed($what, $contenttype, $contentid);
    }

    /**
     * Shortcut for {@see \Bolt\Users::checkAntiCSRFToken}
     *
     * @param string $token
     *
     * @return bool
     */
    protected function checkAntiCSRFToken($token = '')
    {
        return $this->app['authentication']->checkAntiCSRFToken($token);
    }

    /**
     * Gets the \Bolt\Extensions object.
     *
     * @return \Bolt\Extensions
     */
    protected function getExtensions()
    {
        return $this->app['extensions'];
    }

    /**
     * Gets the Bolt\Filesystem\Manager object.
     *
     * @return \Bolt\Filesystem\Manager
     */
    protected function getFilesystemManager()
    {
        return $this->app['filesystem'];
    }

    /**
     * Return current user or user by ID
     *
     * @param int|null $id
     *
     * @return array
     */
    protected function getUser($id = null)
    {
        if ($id === null) {
            return $this->app['users']->getCurrentUser();
        }
        return $this->app['users']->getUser($id);
    }

    /**
     * Returns the Users object.
     *
     * @return \Bolt\Users
     */
    protected function getUsers()
    {
        return $this->app['users'];
    }

    /**
     * Returns the Authentication object.
     *
     * @return \Bolt\AccessControl\Authentication
     */
    protected function getAuthentication()
    {
        return $this->app['authentication'];
    }

    /**
     * Shortcut for {@see \Bolt\Storage::getContent}
     *
     * @param string $textquery
     * @param array  $parameters
     * @param array  $pager
     * @param array  $whereparameters
     *
     * @return \Bolt\Content|\Bolt\Content[]
     */
    protected function getContent($textquery, $parameters = array(), &$pager = array(), $whereparameters = array())
    {
        return $this->app['storage']->getContent($textquery, $parameters, $pager, $whereparameters);
    }

    /**
     * Get the contenttype as an array, based on the given slug.
     *
     * @param string $slug
     *
     * @return boolean|array
     */
    protected function getContentType($slug)
    {
        return $this->app['storage']->getContentType($slug);
    }

    /**
     * Shortcut for {@see \Bolt\Config::get}.
     *
     * @param string $path
     * @param string $default
     *
     * @return string|integer|array|null
     */
    protected function getOption($path, $default = null)
    {
        return $this->app['config']->get($path, $default);
    }

    /**
     * Return the Bolt\TemplateChooser provider.
     *
     * @return \Bolt\TemplateChooser
     */
    protected function templateChooser()
    {
        return $this->app['templatechooser'];
    }
}
