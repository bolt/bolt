<?php
namespace Bolt\Controller;

use Bolt\Routing\DefaultControllerClassAwareInterface;
use Bolt\Storage\Entity;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
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
    protected function abort($statusCode, $message = '', array $headers = [])
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
    protected function render($template, array $variables = [], array $globals = [])
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
    protected function json($data = [], $status = 200, array $headers = [])
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
    protected function createForm($type = 'form', $data = null, array $options = [])
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
    protected function createFormBuilder($type = 'form', $data = null, array $options = [])
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
    protected function generateUrl($name, $params = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
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
    protected function redirectToRoute($route, array $parameters = [], $status = 302)
    {
        return $this->redirect($this->generateUrl($route, $parameters), $status);
    }

    /**
     * Returns the Entity Manager.
     *
     * @return \Bolt\Storage\EntityManager
     */
    protected function storage()
    {
        return $this->app['storage'];
    }

    /**
     * Returns the session.
     *
     * @return \Symfony\Component\HttpFoundation\Session\Session
     */
    protected function session()
    {
        return $this->app['session'];
    }

    /**
     * Gets the flash logger
     *
     * @return \Bolt\Logger\FlashLoggerInterface
     */
    protected function flashes()
    {
        return $this->app['logger.flash'];
    }

    /**
     * Returns the Authentication object.
     *
     * @return \Bolt\AccessControl\AccessChecker
     */
    protected function accessControl()
    {
        return $this->app['access_control'];
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
        return $this->users()->checkAntiCSRFToken($token);
    }

    /**
     * Gets the \Bolt\Extensions object.
     *
     * @return \Bolt\Extensions
     */
    protected function extensions()
    {
        return $this->app['extensions'];
    }

    /**
     * Gets the Bolt\Filesystem\Manager object.
     *
     * @return \Bolt\Filesystem\Manager
     */
    protected function filesystem()
    {
        return $this->app['filesystem'];
    }

    /**
     * Returns the Users object.
     *
     * @return \Bolt\Users
     */
    protected function users()
    {
        return $this->app['users'];
    }

    /**
     * Check to see if the user table exists and has records.
     *
     * @return boolean
     */
    protected function hasUsers()
    {
        try {
            $users = $this->app['users']->getUsers();
            if (empty($users)) {
                return false;
            }

            return true;
        } catch (TableNotFoundException $e) {
            return false;
        }
    }

    /**
     * Return current user or user by ID.
     *
     * @param integer|string|null $userId
     * @param boolean             $raw
     *
     * @return Entity\Users|null
     */
    protected function getUser($userId = null, $raw = false)
    {
        if ($userId === null) {
            if ($sessionAuth = $this->session()->get('authentication')) {
                return $sessionAuth->getUser();
            }

            return;
        }

        $repo = $this->storage()->getRepository('Bolt\Storage\Entity\Users');
        if (($userEntity = $repo->getUser($userId)) && !$raw) {
            $userEntity->setPassword('**dontchange**');
        }

        return $userEntity;
    }

    /**
     * Shortcut for {@see \Bolt\AccessControl\Permissions::isAllowed}
     *
     * @param string       $what
     * @param mixed        $user        The user to check permissions against.
     * @param string|null  $contenttype
     * @param integer|null $contentid
     *
     * @return boolean
     */
    protected function isAllowed($what, $user = null, $contenttype = null, $contentid = null)
    {
        if ($user === null && $user = $this->session()->get('authentication')) {
            $user = $user->getUser()->toArray();
        }

        return $this->app['permissions']->isAllowed($what, $user, $contenttype, $contentid);
    }

    /**
     * Return a repository.
     *
     * @param string $repository
     *
     * @return \Bolt\Storage\Repository
     */
    protected function getRepository($repository)
    {
        return $this->storage()->getRepository($repository);
    }

    /**
     * Shortcut for {@see \Bolt\Storage::getContent}
     *
     * @param string $textquery
     * @param array  $parameters
     * @param array  $pager
     * @param array  $whereparameters
     *
     * @return \Bolt\Legacy\Content|\Bolt\Legacy\Content[]
     */
    protected function getContent($textquery, $parameters = [], &$pager = [], $whereparameters = [])
    {
        return $this->storage()->getContent($textquery, $parameters, $pager, $whereparameters);
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
        return $this->storage()->getContentType($slug);
    }

    /**
     * Shortcut for {@see \Bolt\Config::get}.
     *
     * @param string $path
     * @param mixed  $default
     *
     * @return string|integer|array|null
     */
    protected function getOption($path, $default = null)
    {
        return $this->app['config']->get($path, $default);
    }

    /**
     * Get an array of query parameters used in the request.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function getRefererQueryParameters(Request $request)
    {
        $referer = $request->server->get('HTTP_REFERER');
        $request = Request::create($referer);

        return (array) $request->query->getIterator();
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

    /**
     * Return a new Query Builder.
     *
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    protected function createQueryBuilder()
    {
        return $this->app['db']->createQueryBuilder();
    }

    /**
     * @return \Bolt\Configuration\ResourceManager
     */
    protected function resources()
    {
        return $this->app['resources'];
    }
}
