<?php

namespace Bolt\Controller\Async;

use Bolt\Filesystem\Handler\FileInterface;
use Bolt\Response\TemplateResponse;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Async controller for Stack async routes.
 *
 * @author Gawain Lynch <gawain.lynch@gmail.com>
 * @author Carson Full <carsonfull@gmail.com>
 */
class Stack extends AsyncBase
{
    protected function addRoutes(ControllerCollection $c)
    {
        $c->post('/stack/add', 'add')
            ->assert('filename', '.*')
            ->bind('stack/add');

        $c->get('/stack/show', 'show')
            ->bind('stack/show');
    }

    /**
     * Add a file to the user's stack.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function add(Request $request)
    {
        $filename = $request->request->get('filename');

        $stack = $this->app['stack'];

        /** @var FileInterface|null $removed */
        $file = $stack->add($filename, $removed);

        $panel = $this->render('@bolt/components/stack/panel-item.twig', ['file' => $file]);
        $list = $this->render('@bolt/components/stack/list-item.twig', ['file' => $file]);

        $type = $file->getType();
        $type = !in_array($type, ['image', 'document']) ? 'other' : $type;

        return $this->json([
            'type'    => $type,
            'removed' => $removed ? $removed->getFullPath() : null,
            'panel'   => $panel->getContent(),
            'list'    => $list->getContent(),
        ]);
    }

    /**
     * Render a user's current stack.
     *
     * @param Request $request
     *
     * @return TemplateResponse
     */
    public function show(Request $request)
    {
        $count = $request->query->get('count', \Bolt\Stack::MAX_ITEMS);
        $options = $request->query->get('options');

        if ($options === 'ck') {
            $template = '@bolt/components/stack/ck.twig';
        } elseif ($options === 'list') {
            $template = '@bolt/components/stack/list.twig';
        } else {
            $template = '@bolt/components/stack/panel.twig';
        }

        $context = [
            'count'     => $count,
            'filetypes' => $this->getOption('general/accept_file_types'),
            'namespace' => $this->app['upload.namespace'],
            'canUpload' => $this->isAllowed('files:uploads'),
        ];

        return $this->render($template, ['context' => $context]);
    }
}
