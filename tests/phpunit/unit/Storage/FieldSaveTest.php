<?php
namespace Bolt\Tests\Storage;

use Bolt\Tests\BoltUnitTest;
use Bolt\Storage\EntityManager;
use Bolt\Storage\ContentRepository;
use Bolt\Entity\Content;
use Bolt\Tests\Mocks\LoripsumMock;
use Bolt\Storage;
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
        $app['integritychecker']->repairTables();
        $this->addSomeContent();
        $em = new EntityManager($app['db'], $app['dispatcher'], $app['storage.metadata']);
        $repo = $em->getRepository('showcases');
        
        $record = $repo->find(1);
                                
        foreach ($record->entries as $entry) {
            $this->assertNotEmpty($entry->id);
            $this->assertNotEmpty($entry->slug);
        }
        
        $record->setEntries([]);
        $em->save($record);
        
              

        
    }
    
    public function testTaxonomySave()
    {
        $app = $this->getApp();
        $app['integritychecker']->repairTables();
        $em = new EntityManager($app['db'], $app['dispatcher'], $app['storage.metadata']);
        $repo = $em->getRepository('showcases');
        
        $record = $repo->find(1);
        $this->assertTrue(is_array($record->categories));
        $this->assertTrue(is_array($record->tags));
        
    }
    
    protected function addSomeContent()
    {
        $app = $this->getApp();
        $app['request'] = Request::create('/');
        $app['config']->set('taxonomy/categories/options', array('news'));
        $prefillMock = new LoripsumMock();
        $app['prefill'] = $prefillMock;

        $storage = new Storage($app);
        $storage->prefill(array('showcases', 'entries', 'pages'));
        
        // We also set some relations between showcases and entries
        $showcases = $storage->getContent("showcases");
        $randEntries = $storage->getContent("entries/random/2");
        foreach ($showcases as $show) {
            foreach ($randEntries as $key=>$entry) {
                $show->setRelation('entries', $key);
                $storage->saveContent($show);
            }
            
        }
        
    }
    
    

    
}
