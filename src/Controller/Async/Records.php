<?php

namespace Bolt\Controller\Async;

use Bolt\Storage\Entity\Content;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Async controller for record manipulation routes.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 */
class Records extends AsyncBase
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->method('POST');

        $c->post('/content/{action}', 'modify')
            ->bind('contentaction');
    }

    /**
     * Perform an action on a Contenttype record.
     *
     * The action part of the POST request should take the form:
     * [
     *     contenttype => [
     *         id => [
     *             action => [field => value]
     *         ]
     *     ]
     * ]
     *
     * For example:
     * [
     *     'pages'   => [
     *         3 => ['modify' => ['status' => 'held']],
     *         5 => null,
     *         4 => ['modify' => ['status' => 'draft']],
     *         1 => ['delete' => null],
     *         2 => ['modify' => ['status' => 'published']],
     *     ],
     *     'entries' => [
     *         4 => ['modify' => ['status' => 'published']],
     *         1 => null,
     *         5 => ['delete' => null],
     *         2 => null,
     *         3 => ['modify' => ['title' => 'Drop Bear Attacks']],
     *     ]
     * ]
     *
     * @param Request $request Symfony Request
     *
     * @return Response
     */
    public function modify(Request $request)
    {
        if (!$this->checkAntiCSRFToken($request->get('bolt_csrf_token'))) {
            $this->app->abort(Response::HTTP_BAD_REQUEST, Trans::__('Something went wrong'));
        }

        $contentType = $request->get('contenttype');
        $actionData = $request->get('modifications');
        if ($actionData === null) {
            throw new \UnexpectedValueException('No content action data provided in the request.');
        }

        foreach ($actionData as $contentTypeSlug => $recordIds) {
            if (!$this->getContentType($contentTypeSlug)) {
// sprintf('Attempt to modify invalid ContentType: %s', $contentTypeSlug);
                continue;
            } else {
                $this->app['storage.request.modify']->action($contentTypeSlug, $recordIds);
            }
        }

        $referer = Request::create($request->server->get('HTTP_REFERER'));
        $order = $referer->query->get('order');
        $page = $referer->query->get('page');
        $filter = $referer->query->get('filter');
        $taxonomy = null;
        foreach (array_keys($this->getOption('taxonomy', [])) as $taxonomyKey) {
            if ($referer->query->get('taxonomy-' . $taxonomyKey)) {
                $taxonomy[$taxonomyKey] = $referer->query->get('taxonomy-' . $taxonomyKey);
            }
        }

        $context = [
            'contenttype'     => $this->getContentType($contentType),
            'multiplecontent' => $this->app['storage.request.listing']->action($contentType, $order, $page, $taxonomy, $filter),
            'filter'          => array_merge((array) $taxonomy, (array) $filter),
            'permissions'     => $this->getContentTypeUserPermissions($contentType, $this->users()->getCurrentUser())
        ];

        return $this->render('@bolt/overview/overview.twig', $context);
    }
}
