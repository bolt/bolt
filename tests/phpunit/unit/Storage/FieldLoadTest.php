<?php
namespace Bolt\Tests\Storage;

use Bolt\Legacy\Storage;
use Bolt\Tests\BoltUnitTest;
use Bolt\Tests\Mocks\LoripsumMock;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class to test src/Storage/Repository and field transforms for load and hydrate
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class FieldLoadTest extends BoltUnitTest
{
    public function testRelationsLoad()
    {
        $this->resetDb();
        $app = $this->getApp();
        $app['schema']->update();
        $this->addDefaultUser($app);
        $this->addSomeContent();
        $em = $app['storage'];
        $repo = $em->getRepository('showcases');

        $record = $repo->find(1);
        foreach ($record->relation['entries'] as $entry) {
            $this->assertNotEmpty($entry->getId());
            $this->assertNotEmpty($entry->getSlug());
        }
    }

    public function testTaxonomyLoad()
    {
        $app = $this->getApp();
        $em = $app['storage'];
        $repo = $em->getRepository('showcases');

        $record = $repo->find(1);
        $this->assertInstanceOf('Bolt\Storage\Collection\Taxonomy', $record->taxonomy['categories']);
        $this->assertInstanceOf('Bolt\Storage\Collection\Taxonomy', $record->taxonomy['tags']);
    }

    public function testRepeaterLoad()
    {
        $app = $this->getApp();
        $em = $app['storage'];
        $this->addSomeFields();
        $repo = $em->getRepository('showcases');
        $record = $repo->find(1);
        $this->assertInstanceOf('Bolt\Storage\Field\Collection\RepeatingFieldCollection', $record->repeater);
        $this->assertEquals(2, count($record->repeater));
        foreach ($record->repeater as $collection) {
            $this->assertInstanceOf('Bolt\Storage\Field\Collection\FieldCollection', $collection);
            foreach ($collection as $fieldValue) {
                $this->assertInstanceOf('Bolt\Storage\Entity\FieldValue', $fieldValue);
            }
        }
    }

    public function testGroupingTaxonomy()
    {
        $app = $this->getApp();
        $em = $app['storage'];
        $repo = $em->getRepository('pages');
        $record = $repo->find(3);
        $tax = $em->createCollection('Bolt\Storage\Entity\Taxonomy');
        $tax->setFromPost(['groups' => ['main']], $record);
        $record->setTaxonomy($tax);
        $repo->save($record);
        $recordSaved = $repo->find(3);
        $this->assertInstanceOf('Bolt\Storage\Collection\Taxonomy', $recordSaved->taxonomy['groups']);
        $this->assertInstanceOf('Bolt\Storage\Collection\Taxonomy', $recordSaved->getGroups());
        $this->assertEquals(1, count($recordSaved->getGroups()));
    }

    protected function addSomeContent()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/');
        $app['config']->set('taxonomy/categories/options', ['news']);
        $prefillMock = new LoripsumMock();
        $app['prefill'] = $prefillMock;

        $storage = new Storage($app);
        $storage->prefill(['showcases', 'entries', 'pages']);

        // We also set some relations between showcases and entries
        $showcases = $storage->getContent('showcases');
        $randEntries = $storage->getContent('entries/random/2');
        foreach ($showcases as $show) {
            foreach (array_keys($randEntries) as $key) {
                $show->setRelation('entries', $key);
                $storage->saveContent($show);
            }
        }
    }

    protected function addSomeFields()
    {
        $app = $this->getApp();
        $repo = $app['storage']->getRepository('showcases');
        $content = $repo->find(1);

        $repeat = [
            ['repeattitle' => 'Test', 'repeatimage' => ['file' => 'example.jpg', 'title' => 'Test Image']],
            ['repeattitle' => 'Test 2', 'repeatimage' => ['file' => 'example2.jpg', 'title' => 'Test Image 2']],
        ];
        $content->setRepeater($repeat);

        $repo->save($content);
    }
}
