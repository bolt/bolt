<?php
namespace Bolt\Controller\Async;

use Bolt\Response\BoltResponse;
use Silex\ControllerCollection;
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
        $c->get('/stack/add/{filename}', 'addStack')
            ->assert('filename', '.*')
            ->bind('stack/add');

        $c->get('/stack/show', 'showStack')
            ->bind('stack/show');
    }

    /**
     * Add a file to the user's stack.
     *
     * @param string $filename
     *
     * @return true
     */
    public function addStack($filename)
    {
        $this->stack()->add($filename);

        return true;
    }

    /**
     * Render a user's current stack.
     *
     * @param Request $request
     *
     * @return BoltResponse
     */
    public function showStack(Request $request)
    {
        $count = $request->query->get('items', 10);
        $options = $request->query->get('options', false);

        $context = [
            'stack'     => $this->stack()->listitems($count),
            'filetypes' => $this->stack()->getFileTypes(),
            'namespace' => $this->app['upload.namespace'],
            'canUpload' => $this->isAllowed('files:uploads')
        ];

        switch ($options) {
            case 'minimal':
                $twig = '@bolt/components/stack-minimal.twig';
                break;

            case 'list':
                $twig = '@bolt/components/stack-list.twig';
                break;

            case 'full':
            default:
                $twig = '@bolt/components/panel-stack.twig';
                break;
        }

        return $this->render($twig, ['context' => $context]);
    }

    /**
     * @return \Bolt\Stack
     */
    protected function stack()
    {
        return $this->app['stack'];
    }
}
