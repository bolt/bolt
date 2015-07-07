<?php
namespace Bolt\Tests\Storage\Query;

use Bolt\Tests\BoltUnitTest;
use Bolt\Storage\Query\QueryParameterParser;

/**
 * Class to test src/Storage/Query/QueryParameterParser.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class ContentQueryParserTest extends BoltUnitTest
{
    public function testValueParse()
    {
        $p = new QueryParameterParser('username', '5');
        $expr = $p->parse();
        $this->assertEquals('5', $expr['value']);
        $this->assertEquals('=', $expr['operator']);
        
        $p = new QueryParameterParser('ownerid', '<5');
        $expr = $p->parse();
        $this->assertEquals('5', $expr['value']);
        $this->assertEquals('<', $expr['operator']);
                
        $p = new QueryParameterParser('ownerid', '!10');
        $expr = $p->parse();
        $this->assertEquals('10', $expr['value']);
        $this->assertEquals('<>', $expr['operator']);
    }
    
}