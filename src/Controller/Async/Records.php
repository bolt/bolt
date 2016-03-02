<?php

namespace Bolt\Controller\Async;

use Bolt\Storage\ContentRequest\ListingOptions;
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

        $c->post('/content/{action}', 'action')
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
    public function action(Request $request)
    {
        $this->validateCsrfToken();

        $contentType = $request->get('contenttype');
        $actionData = $request->get('actions');
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
        $taxonomy = null;
        foreach (array_keys($this->getOption('taxonomy', [])) as $taxonomyKey) {
            if ($referer->query->get('taxonomy-' . $taxonomyKey)) {
                $taxonomy[$taxonomyKey] = $referer->query->get('taxonomy-' . $taxonomyKey);
            }
        }

        $options = (new ListingOptions())
            ->setOrder($referer->query->get('order'))
            ->setPage($referer->query->get('page_' . $contentType))
            ->setFilter($referer->query->get('filter'))
            ->setTaxonomies($taxonomy)
        ;

        $context = [
            'contenttype'     => $this->getContentType($contentType),
            'multiplecontent' => $this->app['storage.request.listing']->action($contentType, $options),
            'filter'          => array_merge((array) $taxonomy, (array) $options->getFilter()),
            'permissions'     => $this->getContentTypeUserPermissions($contentType, $this->users()->getCurrentUser()),
        ];

        return $this->render('@bolt/async/record_list.twig', ['context' => $context]);
    }
}
