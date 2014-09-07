<?php
/**
 * JSONAccess extension for Bolt.
 *
 * @author Tobias Dammers <tobias@twokings.nl>
 */

namespace JSONAccess;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Extension extends \Bolt\AbstractExtension
{
    public function info()
    {
        return array(
            'name' => "JSONAccess",
            'description' => "Provides JSON access to Bolt data structures",
            'author' => "Tobias Dammers",
            'link' => "http://bolt.cm",
            'version' => "0.1",
            'required_bolt_version' => "1.2.0",
            'highest_bolt_version' => "1.4.0",
            'type' => "General",
            'first_releasedate' => null,
            'latest_releasedate' => null,
            'priority' => 10
        );
    }

    public function initialize()
    {
        $this->app->get("/json/{contenttype}/", array($this, 'json_list'))
                  ->bind('json_list');
        $this->app->get("/json/{contenttype}/{slug}", array($this, 'json'))
                  ->assert('slug', '[a-zA-Z0-9_\-]+')
                  ->bind('json');
    }

    private function clean_item($item, $type = 'list-fields') {
        $contenttype = $item->contenttype['slug'];
        if (isset($this->config['contenttypes'][$contenttype][$type])) {
            $fields = $this->config['contenttypes'][$contenttype][$type];
        }
        else {
            $fields = array_keys($item->contenttype['fields']);
        }
        // Always include the ID in the set of fields
        array_unshift($fields, 'id');
        $fields = array_unique($fields);
        $values = array();
        foreach ($fields as $field) {
            $values[$field] = $item->values[$field];
        }
        return $values;
    }

    private function clean_list_item($item) { return $this->clean_item($item, 'list-fields'); }
    private function clean_full_item($item) { return $this->clean_item($item, 'item-fields'); }

    public function json_list(Request $request, $contenttype)
    {
        if (!array_key_exists($contenttype, $this->config['contenttypes'])) {
            return $this->app->abort(404, 'Not found');
        }
        $options = array();
        if ($limit = $request->get('limit')) {
            $limit = intval($limit);
            if ($limit >= 1) {
                $options['limit'] = $limit;
            }
        }
        if ($page = $request->get('page')) {
            $page = intval($page);
            if ($page >= 1) {
                $options['page'] = $page;
            }
        }
        if ($order = $request->get('order')) {
            if (!preg_match('/^([a-zA-Z][a-zA-Z0-9_\\-]*)\\s*(ASC|DESC)?$/', $order, $matches)) {
                return $this->app->abort(400, 'Invalid request');
            }
            $options['order'] = $order;
        }
        $items = $this->app['storage']->getContent($contenttype, $options);

        // If we don't have any items, this can mean one of two things: either
        // the content type does not exist (in which case we'll get a non-array
        // response), or it exists, but no content has been added yet.
        if (!is_array($items)) {
            throw new \Exception("Configuration error: $contenttype is configured as a JSON end-point, but doesn't exist as a content type.");
        }
        if (empty($items)) {
            $items = array();
        }

        $items = array_values($items);
        $items = array_map(array($this, 'clean_list_item'), $items);
        $response = $this->app->json(array($contenttype => $items));
        if ($callback = $request->get('callback')) {
            $response->setCallback($callback);
        }
        return $response;
    }

    public function json(Request $request, $contenttype, $slug)
    {
        if (!array_key_exists($contenttype, $this->config['contenttypes'])) {
            return $this->app->abort(404, 'Not found');
        }
        $item = $this->app['storage']->getContent("$contenttype/$slug");
        if (!$item) {
            return $this->app->abort(404, 'Not found');
        }
        $values = $this->clean_full_item($item);
        $response = $this->app->json($values);
        if ($callback = $request->get('callback')) {
            $response->setCallback($callback);
        }
        return $response;
    }
}

