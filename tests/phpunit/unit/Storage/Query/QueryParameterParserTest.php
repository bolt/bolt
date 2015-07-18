<?php
namespace Bolt\Tests\Storage\Query;

use Bolt\Tests\BoltUnitTest;
use Bolt\Storage\Query\QueryParameterParser;

/**
 * Class to test src/Storage/Query/QueryParameterParser.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class QueryParameterParserTest extends BoltUnitTest
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
    
    public function testLikeValueParse()
    {
        $p = new QueryParameterParser('name', '%fred%');
        $expr = $p->parse();
        $this->assertEquals('name', $expr['key']);
        $this->assertEquals('%fred%', $expr['value']);
        $this->assertEquals('LIKE', $expr['operator']);
        
        $p = new QueryParameterParser('name', 'fred%');
        $expr = $p->parse();
        $this->assertEquals('name', $expr['key']);
        $this->assertEquals('fred%', $expr['value']);
        $this->assertEquals('LIKE', $expr['operator']);
        
        $p = new QueryParameterParser('name', '%fred');
        $expr = $p->parse();
        $this->assertEquals('name', $expr['key']);
        $this->assertEquals('%fred', $expr['value']);
        $this->assertEquals('LIKE', $expr['operator']);
    }
    
    public function testCompositeOr()
    {
        $p = new QueryParameterParser('ownerid', '3||4');
        $expr = $p->parse();
        $this->assertEquals('ownerid', $expr['key']);
        $this->assertEquals('3,4', $expr['value']);
        $this->assertEquals('orX', $expr['operator']);
        
        $p = new QueryParameterParser('ownerid', 'fred || 4');
        $expr = $p->parse();
        $this->assertEquals('ownerid', $expr['key']);
        $this->assertEquals('fred,4', $expr['value']);
        $this->assertEquals('orX', $expr['operator']);
    }
    
}