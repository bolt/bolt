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
class FieldSaveTest extends BoltUnitTest
{
    public function testRelationsSave()
    {
        $app = $this->getApp();
        $app['schema']->repairTables();
        $this->addSomeContent();
        $em = $app['storage'];
        $repo = $em->getRepository('showcases');

        $record = $repo->find(1);

        foreach ($record->relation['entries'] as $entry) {
            $this->assertNotEmpty($entry->id);
            $this->assertNotEmpty($entry->slug);
        }

        $record->setRelation([]);
        $em->save($record);

        // Test that there are no relations now on a fresh search
        $record1 = $repo->find(1);
        $this->assertNull($record1->relation);
    }

    public function testTaxonomySave()
    {
        $app = $this->getApp();
        $em = $app['storage'];
        $repo = $em->getRepository('showcases');

        $record = $repo->find(1);

        $this->assertTrue(is_array($record->taxonomy['categories']));
        $this->assertTrue(is_array($record->taxonomy['tags']));

        $record->setTaxonomy(['categories' => []]);
        $repo->save($record);

        // Test that there are no relations now on a fresh search
        $record1 = $repo->find(1);
        $this->assertEquals(0, count($record1->taxonomy['categories']));
    }
    
    public function testEntityCreateTaxonomySave()
    {
        $app = $this->getApp();
        $em = $app['storage'];
        $repo = $em->getRepository('showcases');
        
        $newEntity = $repo->create(['title' => 'Testing', 'slug' => 'testing', 'status' => 'published']);
        $newEntity->setTaxonomy(['categories' => ['news', 'events']]);
        $repo->save($newEntity);
        
        $savedEntity = $repo->find($newEntity->getId());
        $this->assertEquals(2, count($savedEntity->getCategories()));
    }

    protected function addSomeContent()
    {
        $app = $this->getApp();
        $this->addDefaultUser($app);
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
}
