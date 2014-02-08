<?php
/**
 * A slightly useless extension to test/demonstrate how to make use of Bolt's
 * automatic table updates for an extension's own tables.
 *
 * This extension implements a CRUD cycle for "waffle orders", exposed
 * on the "/waffle" endpoint. Users can provide a name and a number of waffles,
 * and these orders will be stored and displayed in descending order.
 *
 * When you activate this extension, the database check will add a new table to
 * your database.
 *
 * @author Tobias Dammers <tobias@twokings.nl>
 */

namespace WaffleOrders;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\DBAL\Schema\Schema;

class Extension extends \Bolt\BaseExtension
{
    public function info()
    {
        return array(
            'name' => "TestRegisterTable",
            'description' => "Test-drives the new automatic extension table creation functionality",
            'author' => "Tobias Dammers",
            'link' => "http://bolt.cm",
            'version' => "0.1",
            'required_bolt_version' => "1.4.4",
            'type' => "General",
            'first_releasedate' => null,
            'latest_releasedate' => null,
            'priority' => 10
        );
    }

    public $my_table_name;

    public function initialize()
    {
        $prefix = $this->app['config']->get('general/database/prefix', "bolt_");
        $this->my_table_name = $prefix . 'waffle_orders';
        $me = $this;
        $this->app['integritychecker']->registerExtensionTable(
            function(Schema $schema) use ($me) {
                $table = $schema->createTable($me->my_table_name);
                $table->addColumn("id", "integer", array('autoincrement' => true));
                $table->setPrimaryKey(array("id"));
                $table->addColumn("customer_name", "string", array("length" => 64));
                $table->addIndex(array('customer_name'));
                $table->addColumn("num_waffles_ordered", "integer");
                return $table;
            });
        $this->app->get("/waffles", array($this, 'show_waffles'))->bind('show_waffles');
        $this->app->post("/waffles/add", array($this, 'add_waffles'))->bind('add_waffles');
        $this->app->post("/waffles/clear", array($this, 'clear_waffles'))->bind('clear_waffles');
    }

    public function show_waffles(Request $request, $errors = null)
    {
        $waffles = $this->app['db']->fetchAll(
            'SELECT customer_name, num_waffles_ordered FROM ' .
            $this->my_table_name .
            ' ORDER BY id DESC LIMIT 100');
        $template_vars = array('waffles' => $waffles);
        if (is_array($errors)) {
            $template_vars['errors'] = $errors;
        }
        if ($request->getMethod() === 'POST') {
            $keys = array('customer_name', 'num_waffles_ordered');
            foreach ($keys as $key) {
                $template_vars['postData'][$key] = $request->get($key);
            }
        }
        return $this->render('waffles.twig', $template_vars);
    }

    public function clear_waffles(Request $request)
    {
        $rows_deleted = $this->app['db']->executeUpdate('DELETE FROM ' . $this->my_table_name);
        return $this->app->redirect('/waffles');
    }

    public function add_waffles(Request $request)
    {
        $customer_name = trim($request->get('customer_name'));
        $num_waffles_ordered = intval($request->get('num_waffles_ordered'));
        $errors = array();

        if (empty($customer_name)) {
            $errors['customer_name'] = 'Please provide a name';
        }
        if ($num_waffles_ordered <= 0) {
            $errors['num_waffles_ordered'] = 'You must order at least one waffle';
        }
        if ($num_waffles_ordered > 100) {
            $errors['num_waffles_ordered'] = 'Sorry, we don\'t have this many waffles';
        }

        if (empty($errors)) {
            $rows_affected = $this->app['db']->executeUpdate('INSERT INTO ' .
                $this->my_table_name .
                ' (customer_name, num_waffles_ordered) ' .
                ' VALUES (:customer_name, :num_waffles_ordered) ',
                array(
                    ':customer_name' => $customer_name,
                    ':num_waffles_ordered' => $num_waffles_ordered,
                    ));
            if ($rows_affected === 1) {
                return $this->app->redirect('/waffles');
            }
            else {
                $errors['general'] = 'Sorry, something went wrong';
            }
        }
        return $this->show_waffles($request, $errors);
    }

    private function render($template, $data) {
        $this->app['twig.loader.filesystem']->addPath(dirname(__FILE__) . '/templates');
        return $this->app['render']->render($template, $data);
    }

}

