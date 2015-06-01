<?php

namespace Bolt\Controllers;

use Silex\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @deprecated Use {@see \Bolt\Controller\Frontend} instead
 */
class Frontend
{
    /**
     * @deprecated Use {@see \Bolt\Controller\Frontend} instead
     *
     * @param Request     $request
     * @param Application $app
     *
     * @return null|RedirectResponse
     */
    public function before(Request $request, Application $app)
    {
        return $this->frontend($app)->before($request);
    }

    /**
     * @deprecated Use {@see \Bolt\Controller\Frontend} instead
     *
     * @param \Silex\Application $app
     *
     * @return string
     */
    public function homepage(Application $app)
    {
        $response = $this->frontend($app)->homepage();
        return $this->render($app, $response->getTemplateName(), 'homepage');
    }

    /**
     * @deprecated Use {@see \Bolt\Controller\Frontend} instead
     *
     * @param Application $app
     * @param string      $contenttypeslug
     * @param string      $slug
     *
     * @return string
     */
    public function record(Application $app, $contenttypeslug, $slug = '')
    {
        $response = $this->frontend($app)->record($app['request'], $contenttypeslug, $slug);
        $globals = $response->getGlobalContext();
        return $this->render($app, $response->getTemplateName(), $globals['record']->getTitle());
    }

    /**
     * @deprecated Use {@see \Bolt\Controller\Frontend} instead
     *
     * @param Request     $request
     * @param Application $app
     * @param string      $contenttypeslug
     *
     * @return string
     */
    public function preview(Request $request, Application $app, $contenttypeslug)
    {
        $response = $this->frontend($app)->preview($request, $contenttypeslug);
        return $this->render($app, $response->getTemplateName(), $contenttypeslug);
    }

    /**
     * @deprecated Use {@see \Bolt\Controller\Frontend} instead
     *
     * @param Application $app
     * @param string      $contenttypeslug
     *
     * @return string
     */
    public function listing(Application $app, $contenttypeslug)
    {
        $response = $this->frontend($app)->listing($app['request'], $contenttypeslug);
        return $this->render($app, $response->getTemplateName(), $contenttypeslug);
    }

    /**
     * @deprecated Use {@see \Bolt\Controller\Frontend} instead
     *
     * @param Application $app
     * @param string      $taxonomytype
     * @param string      $slug
     *
     * @return string
     */
    public function taxonomy(Application $app, $taxonomytype, $slug)
    {
        $response = $this->frontend($app)->taxonomy($app['request'], $taxonomytype, $slug);
        return $this->render($app, $response->getTemplateName(), $taxonomytype);
    }

    /**
     * @deprecated Use {@see \Bolt\Controller\Frontend} instead
     *
     * @param Request     $request
     * @param Application $app
     *
     * @return string
     */
    public function search(Request $request, Application $app)
    {
        $response = $this->frontend($app)->search($request);
        return $this->render($app, $response->getTemplateName(), 'search');
    }

    /**
     * @deprecated Use {@see \Bolt\Controller\Frontend} instead
     *
     * @param Application $app
     * @param string      $template
     *
     * @return string
     */
    public function template(Application $app, $template)
    {
        $response = $this->frontend($app)->template($template);
        return $this->render($app, $response->getTemplateName(), $template);
    }

    /**
     * @deprecated Use {@see \Bolt\Controller\Frontend} instead
     *
     * @param Application $app
     * @param string      $template
     * @param string      $title
     *
     * @return string
     */
    protected function render(Application $app, $template, $title)
    {
        try {
            return $app['twig']->render($template);
        } catch (\Twig_Error_Loader $e) {
            $error = sprintf(
                'Rendering %s failed: %s',
                $title,
                $e->getMessage()
            );
            // Log it
            $app['logger.system']->error($error, ['event' => 'twig']);
            // Set the template error
            $this->setTemplateError();
            // Abort ship
            throw new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, $error);
        }
    }

    /**
     * @deprecated
     */
    protected function setTemplateError()
    {
    }

    /**
     * @param Application $app
     *
     * @return \Bolt\Controller\Frontend
     */
    protected function frontend(Application $app)
    {
        return $app['controller.frontend'];
    }
}
