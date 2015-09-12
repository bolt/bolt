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
class FieldSetTest extends BoltUnitTest
{
    public function testSetWithNormalValues()
    {
        $app = $this->getApp();
        $this->addSomeContent($app);
        $em = $app['storage'];
        $repo = $em->getRepository('showcases');
        $entity = $repo->create(['title'=> "This is a title" ]);
        $this->assertEquals("This is a title", $entity->getTitle());
    }
    
    public function testSetWithUpdatedValues()
    {
        $app = $this->getApp();
        
        $app['config']->set('theme/templatefields', [
            "record.twig" => [
                'newoption1' => [
                    "type" => 'text',
                    "label" => 'Section 1'
                ],
                'newoption2' => [
                    'type' => 'html',
                    'label' => 'Section 2'
                ],
                'newoption3' => [
                    'type' => 'image'
                ]
                
            ]
        ]);
        
        $em = $app['storage'];
        $repo = $em->getRepository('pages');
        $entity = $repo->find(1);
        $entity->setTemplateFields(['title'=>'']);
        
    }
    
    

    
}
