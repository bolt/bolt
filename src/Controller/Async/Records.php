<?php

namespace Bolt\Controller\Async;

use Bolt\Storage\Entity\Content;
use Bolt\Translation\Translator as Trans;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;

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
        $actionData = $request->get('modifications');

        if ($actionData === null) {
            throw new \UnexpectedValueException('No content action data provided in the request.');
        }

        foreach ($actionData as $contentTypeSlug => $recordIds) {
            if (!$this->getContentType($contentTypeSlug)) {
// sprintf('Attempt to modify invalid ContentType: %s', $contentTypeSlug);
                continue;
            } else {
                $this->modifyContentType($contentTypeSlug, $recordIds);
            }
        }

        return $response;
    }

    /**
     * Modify an individual ContentType's records.
     *
     * @param string $contentTypeSlug
     * @param array  $recordIds
     */
    protected function modifyContentType($contentTypeSlug, array $recordIds)
    {
        foreach ($recordIds as $recordId => $actions) {
            if ($actions === null) {
                continue;
            }

            foreach ($actions as $action => $fieldData) {
                if ($action === 'delete') {
                    return $this->deleteRecord($contentTypeSlug, $recordId);
                } else {
                    return $this->modifyRecord($contentTypeSlug, $recordId, $fieldData);
                }
            }
        }
    }

    /**
     * Execute the deletion of a record.
     *
     * @param string $contentTypeSlug
     * @param array  $recordIds
     */
    protected function deleteRecord($contentTypeSlug, $recordId)
    {
        if (!$this->isAllowed("contenttype:$contentTypeSlug:delete:$recordId")) {
            return;
        }

        $repo = $this->getRepository($contentTypeSlug);
        if ($entity = $repo->find($recordId)) {
            $repo->delete($entity);
        }
    }
}
