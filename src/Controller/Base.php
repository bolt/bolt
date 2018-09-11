<?php

namespace Bolt\Controller;

use Bolt\AccessControl\Token\Token;
use Bolt\Common\Deprecated;
use Bolt\Response\TemplateResponse;
use Bolt\Response\TemplateView;
use Bolt\Routing\DefaultControllerClassAwareInterface;
use Bolt\Storage\Entity;
use Bolt\Storage\Repository;
use Bolt\Translation\Translator as Trans;
use Doctrine\DBAL\Exception\TableNotFoundException;
use Silex\Application;
use Silex\ControllerCollection;
use Silex\ControllerProviderInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

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
     * @param int    $statusCode The HTTP status code
     * @param string $message    The status message
     * @param array  $headers    An array of HTTP headers
     *
     * @throws HttpExceptionInterface
     */
    protected function abort($statusCode, $message = '', array $headers = [])
    {
        $this->app->abort($statusCode, $message, $headers);
    }

    /**
     * Renders a template.
     *
     * @param string|string[] $template Template name(s)
     * @param array           $context  Context variables
     * @param array           $globals  Global variables
     *
     * @return TemplateResponse|TemplateView
     */
    protected function render($template, array $context = [], array $globals = [])
    {
        $twig = $this->app['twig'];

        $template = $twig->resolveTemplate($template);

        if ($this->getOption('general/compatibility/twig_globals', true)) {
            foreach ($globals as $name => $value) {
                $twig->addGlobal($name, $value);
            }
        }
        $context += $globals;

        $this->addResolvedRoute($context, $template->getTemplateName());

        if ($this->getOption('general/compatibility/template_view', false)) {
            return new TemplateView($template->getTemplateName(), $context);
        }
        Deprecated::warn(
            'Returning a TemplateResponse from Bolt\Controller\Base::render',
            3.3,
            'Be sure no Response methods are used from return value and then set "compatibility/template_view"' .
            ' to true in config.yml. This changes render() to return a TemplateView instead.'
        );

        $content = $template->render($context);
        $response = new TemplateResponse($template->getTemplateName(), $context, $content);

        return $response;
    }

    /**
     * Update the route attributes to change the canonical URL generated.
     *
     * @param array  $context
     * @param string $template
     */
    private function addResolvedRoute(array $context, $template)
    {
        if (!isset($context['record'])) {
            return;
        }

        $content = $context['record'];
        $request = $this->app['request_stack']->getCurrentRequest();

        $homepage = $this->getOption('theme/homepage') ?: $this->getOption('general/homepage');
        $uriID = $content->contenttype['singular_slug'] . '/' . $content->get('id');
        $uriSlug = $content->contenttype['singular_slug'] . '/' . $content->get('slug');

        if (($uriID === $homepage || $uriSlug === $homepage) && ($template === $this->getOption('general/homepage_template'))) {
            $request->attributes->add(['_route' => 'homepage', '_route_params' => []]);

            return;
        }

        // In case we're previewing a record, this will override the `_route`, but keep the original
        // one, used to see if we need to disable the XSS protection header.
        // See PR https://github.com/bolt/bolt/pull/7458
        list($routeName, $routeParams) = $content->getRouteNameAndParams();
        if ($routeName) {
            /** @deprecated since 3.4 to be removed in 4.0 */
            $request->attributes->add([
                '_route'          => $routeName,
                '_route_params'   => $routeParams,
                '_internal_route' => $request->attributes->get('_route'),
            ]);
        }
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
    protected function createForm($type = FormType::class, $data = null, array $options = [])
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
    protected function createFormBuilder($type = FormType::class, $data = null, array $options = [])
    {
        return $this->app['form.factory']->createBuilder($type, $data, $options);
    }

    /**
     * Shortcut for {@see UrlGeneratorInterface::generate}.
     *
     * @param string $name          The name of the route
     * @param array  $params        An array of parameters
     * @param int    $referenceType The type of reference to be generated (one of the constants)
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
     * @return \Bolt\Storage\EntityManager|\Bolt\Legacy\Storage
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
     * Gets the flash logger.
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
     * Validates CSRF token and throws HttpException if not.
     *
     * @param string|null $value the token value or null to use "bolt_csrf_token" parameter from request
     * @param string      $id    the token ID
     *
     * @throws HttpExceptionInterface
     */
    protected function validateCsrfToken($value = null, $id = 'bolt')
    {
        if (!$this->isCsrfTokenValid($value, $id)) {
            $this->abort(Response::HTTP_BAD_REQUEST, Trans::__('general.phrase.something-went-wrong'));
        }
    }

    /**
     * Check if csrf token is valid.
     *
     * @param string|null $value the token value or null to use "bolt_csrf_token" parameter from request
     * @param string      $id    the token ID
     *
     * @return bool
     */
    protected function isCsrfTokenValid($value = null, $id = 'bolt')
    {
        $token = new CsrfToken($id, $value ?: $this->app['request_stack']->getCurrentRequest()->get('bolt_csrf_token'));

        return $this->app['csrf']->isTokenValid($token);
    }

    /**
     * Gets the \Bolt\Extensions object.
     *
     * @return \Bolt\Extension\Manager
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
     * @return bool
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
     * @param int|string|null $userId
     *
     * @return Entity\Users|false
     */
    protected function getUser($userId = null)
    {
        if ($userId === null) {
            /** @var Token $sessionAuth */
            if ($this->session()->isStarted() && $sessionAuth = $this->session()->get('authentication')) {
                return $sessionAuth->getUser();
            }

            return false;
        }
        /** @var Repository\UsersRepository $repo */
        $repo = $this->storage()->getRepository(Entity\Users::class);

        return $repo->getUser($userId);
    }

    /**
     * Shortcut for {@see \Bolt\AccessControl\Permissions::isAllowed}.
     *
     * @param string      $what
     * @param mixed       $user        the user to check permissions against
     * @param string|null $contenttype
     * @param int|null    $contentid
     *
     * @return bool
     */
    protected function isAllowed($what, $user = null, $contenttype = null, $contentid = null)
    {
        /** @var Token $sessionAuth */
        if ($user === null && $sessionAuth = $this->session()->get('authentication')) {
            $user = $sessionAuth->getUser()->toArray();
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
     * Shortcut for {@see \Bolt\Legacy\Storage::getContent()}.
     *
     * @param string $textQuery
     * @param array  $parameters
     * @param array  $pager
     * @param array  $whereParameters
     *
     * @return \Bolt\Legacy\Content|\Bolt\Legacy\Content[]
     *
     * @see \Bolt\Legacy\Storage::getContent()
     */
    protected function getContent($textQuery, $parameters = [], &$pager = [], $whereParameters = [])
    {
        $isLegacy = $this->getOption('general/compatibility/setcontent_legacy', true);
        if ($isLegacy) {
            return $this->storage()->getContent($textQuery, $parameters, $pager, $whereParameters);
        }
        $params = array_merge($parameters, $whereParameters);
        unset($params['log_not_found']); // New storage system removes this functionality from the query engine

        return $this->app['query']->getContentForTwig($textQuery, $params);
    }

    /**
     * Get the contenttype as an array, based on the given slug.
     *
     * @param string $slug
     *
     * @return bool|array
     */
    protected function getContentType($slug)
    {
        return $this->storage()->getContentType($slug);
    }

    /**
     * Helper to get a user's permissions for a ContentType.
     *
     * @param string             $contentTypeSlug
     * @param array|Entity\Users $user
     *
     * @return bool[]
     */
    protected function getContentTypeUserPermissions($contentTypeSlug, $user = null)
    {
        if ($user === null) {
            return $this->app['permissions']->getContentTypePermissions();
        }

        return $this->app['permissions']->getContentTypeUserPermissions($contentTypeSlug, $user);
    }

    /**
     * Shortcut for {@see \Bolt\Config::get}.
     *
     * @param string $path
     * @param mixed  $default
     *
     * @return string|int|array|null
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
