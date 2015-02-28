<?php
namespace Bolt\Tests\Logger;

use Bolt\Logger\ChangeLogItem;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Logger/ChangeLogItem.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ChangeLogItemTest extends BoltUnitTest
{
    public function testSetup()
    {
        $app = $this->getApp();
        $cl = new ChangeLogItem($app, array('id' => 5, 'title' => 'test'));
    }

    public function testGetters()
    {
        $app = $this->getApp();
        $cl = new ChangeLogItem($app, array('id' => 5, 'title' => 'test'));
        $this->assertTrue(isset($cl->mutation_type));
        $this->assertFalse(isset($cl->nonexistent));
        $this->assertEquals(5, $cl->id);
        $this->setExpectedException('InvalidArgumentException');
        $test = $cl->nonexistent;

        $users = $this->getMock('Bolt\Users', array('getUser'), array($app));
        $users->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue(array('displayname' => 'Tester', 'username' => 'test')));

        $app['users'] = $users;

        $cl = new ChangeLogItem($app, array(
            'id'          => 5,
            'date'        => date('Y-m'),
            'title'       => 'test',
            'mutation'    => 'UPDATE',
            'ownerid'     => 1,
            'diff'        => '{"title":["test","test2"]}',
            'contenttype' => 'showcases',
            'contentid'   => 1,
            'comment'     => 'a test'
        ));
        $this->assertEquals('test', $cl->title);
        $this->assertEquals(1, $cl->contentid);
        $this->assertEquals('showcases', $cl->contenttype);
        $this->assertEquals('a test', $cl->comment);
    }

    public function testGetMutationType()
    {
        $app = $this->getApp();
        $users = $this->getMock('Bolt\Users', array('getUser'), array($app));
        $users->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue(array('displayname' => 'Tester', 'username' => 'test')));

        $app['users'] = $users;

        $cl = new ChangeLogItem($app, array(
            'id'          => 5,
            'date'        => date('Y-m'),
            'title'       => 'test',
            'mutation'    => 'UPDATE',
            'ownerid'     => 1,
            'diff'        => '{"title":["test","test2"]}',
            'contenttype' => 'showcases',
            'contentid'   => 1,
        ));

        $this->assertEquals('UPDATE', $cl->mutation_type);
        $this->assertEquals(date('Y-m'), $cl->date);
    }

    public function testStandardChangeField()
    {
        $app = $this->getApp();
        $users = $this->getMock('Bolt\Users', array('getUser'), array($app));
        $users->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue(array('displayname' => 'Tester', 'username' => 'test')));

        $app['users'] = $users;

        $cl = new ChangeLogItem($app, array(
            'id'            => 5,
            'date'          => date('Y-m-d'),
            'title'         => 'test',
            'mutation_type' => 'UPDATE',
            'ownerid'       => 1,
            'contenttype'   => 'showcases',
            'contentid'     => 1,
            'diff'          => '{"title":["test","test2"]}',
            'comment'       => 'test update'
        ));

        $changes = $cl->changedfields;
        $this->assertEquals('test', $changes['title']['before']['raw']);
        $this->assertEquals('test2', $changes['title']['after']['raw']);
    }

    public function testHtmlChangeField()
    {
        $app = $this->getApp();

        $cl = new ChangeLogItem($app, array(
            'id'            => 5,
            'date'          => date('Y-m-d'),
            'title'         => 'test',
            'mutation_type' => 'UPDATE',
            'contenttype'   => 'showcases',
            'contentid'     => 1,
            'diff'          => '{"body":["<p>test</p>","<p>test2</p>"]}',
            'comment'       => 'test update'
        ));

        $changes = $cl->changedfields;
        $this->assertEquals('<p>test</p>', $changes['body']['before']['raw']);
        $this->assertEquals('<p>test2</p>', $changes['body']['after']['raw']);
    }

    public function testVideoChangeField()
    {
        $app = $this->getApp();

        $cl = new ChangeLogItem($app, array(
            'id'            => 5,
            'date'          => date('Y-m-d'),
            'title'         => 'test',
            'mutation_type' => 'UPDATE',
            'contenttype'   => 'showcases',
            'contentid'     => 1,
            'diff'          => '{"video":["{\"url\":\"http://example.com\"}","{\"url\":\"http://example.com/2\"}"]}',
            'comment'       => 'test update'
        ));

        $changes = $cl->changedfields;
        $this->assertEquals('http://example.com', $changes['video']['before']['render']['url']);
        $this->assertEquals('http://example.com/2', $changes['video']['after']['render']['url']);
    }

    public function testGeolocationChangeField()
    {
        $app = $this->getApp();

        $cl = new ChangeLogItem($app, array(
            'id'            => 5,
            'date'          => date('Y-m-d'),
            'title'         => 'test',
            'mutation_type' => 'UPDATE',
            'contenttype'   => 'showcases',
            'contentid'     => 1,
            'diff'          => '{"geolocation":["{\"address\":\"1 My Street\"}","{\"address\":\"2 My Street\"}"]}',
            'comment'       => 'test geo'
        ));

        $changes = $cl->changedfields;
        $this->assertEquals('1 My Street', $changes['geolocation']['before']['render']['address']);
        $this->assertEquals('2 My Street', $changes['geolocation']['after']['render']['address']);
    }

    public function testImagelistChangeField()
    {
        $app = $this->getApp();

        $cl = new ChangeLogItem($app, array(
            'id'            => 5,
            'date'          => date('Y-m-d'),
            'title'         => 'test',
            'mutation_type' => 'UPDATE',
            'contenttype'   => 'showcases',
            'contentid'     => 1,
            'diff'          => '{"imagelist":["{\"filename\":\"test.jpg\"}","{\"filename\":\"test2.jpg\"}"]}',
            'comment'       => 'test imagelist'
        ));

        $changes = $cl->changedfields;
        $this->assertEquals('test.jpg', $changes['imagelist']['before']['render']['filename']);
        $this->assertEquals('test2.jpg', $changes['imagelist']['after']['render']['filename']);
    }

    public function testImageChangeField()
    {
        $app = $this->getApp();

        $cl = new ChangeLogItem($app, array(
            'id'            => 5,
            'date'          => date('Y-m-d'),
            'title'         => 'test',
            'mutation_type' => 'UPDATE',
            'contenttype'   => 'showcases',
            'contentid'     => 1,
            'diff'          => '{"image":["{\"file\":\"test.jpg\"}","{\"file\":\"test2.jpg\"}"]}',
            'comment'       => 'test imagelist'
        ));

        $changes = $cl->changedfields;
        $this->assertEquals('test.jpg', $changes['image']['before']['render']['file']);
        $this->assertEquals('test2.jpg', $changes['image']['after']['render']['file']);
    }

    public function testSelectChangeField()
    {
        $app = $this->getApp();

        $cl = new ChangeLogItem($app, array(
            'id'            => 5,
            'date'          => date('Y-m-d'),
            'title'         => 'test',
            'mutation_type' => 'UPDATE',
            'contenttype'   => 'showcases',
            'contentid'     => 1,
            'diff'          => '{"multiselect":["[\"val1\",\"val2\"]","[\"val3\",\"val4\"]"]}',
            'comment'       => 'test multiselect'
        ));

        $changes = $cl->changedfields;
        $this->assertEquals('val1', $changes['multiselect']['before']['render'][0]);
        $this->assertEquals('val3', $changes['multiselect']['after']['render'][0]);
    }

    public function testOffsets()
    {
        $app = $this->getApp();
        $cl = new ChangeLogItem($app, array('id' => 5, 'title' => 'test', 'mutation_type' => 'UPDATE'));

        $this->assertEquals(5, $cl['id']);
        $this->assertTrue(isset($cl['id']));
        $cl['id'] = 6;
        $this->assertEquals(6, $cl['id']);
        unset($cl['id']);
        $this->assertFalse(isset($cl['id']));
    }
}
