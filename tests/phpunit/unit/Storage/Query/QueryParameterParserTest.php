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
        $expr = $p->parseValue();
        $this->assertEquals('5', $expr['value']);
        $this->assertEquals('eq', $expr['operator']);
        
        $p = new QueryParameterParser('ownerid', '<5');
        $expr = $p->parseValue();
        $this->assertEquals('5', $expr['value']);
        $this->assertEquals('lt', $expr['operator']);
                
        $p = new QueryParameterParser('ownerid', '!10');
        $expr = $p->parseValue();
        $this->assertEquals('10', $expr['value']);
        $this->assertEquals('neq', $expr['operator']);
    }
    
    public function testLikeValueParse()
    {
        $p = new QueryParameterParser('name', '%fred%');
        $expr = $p->parseValue();
        $this->assertEquals('%fred%', $expr['value']);
        $this->assertEquals('like', $expr['operator']);
        
        $p = new QueryParameterParser('name', 'fred%');
        $expr = $p->parseValue();
        $this->assertEquals('fred%', $expr['value']);
        $this->assertEquals('like', $expr['operator']);
        
        $p = new QueryParameterParser('name', '%fred');
        $expr = $p->parseValue();
        $this->assertEquals('%fred', $expr['value']);
        $this->assertEquals('like', $expr['operator']);
        
        $p = new QueryParameterParser('name', '!');
        $expr = $p->parseValue();
        $this->assertEquals('', $expr['value']);
        $this->assertEquals('isNotNull', $expr['operator']);
        
        $p = new QueryParameterParser('name', '[1,2,3]');
        $expr = $p->parseValue();
        $this->assertEquals('1,2,3', $expr['value']);
        $this->assertEquals('in', $expr['operator']);
    }
    
    public function testCompositeOrAnd()
    {
        $app = $this->getApp();
        $builder = $app['storage']->createQueryBuilder();
        
        $p = new QueryParameterParser('ownerid', '>1 && <4', $builder);
        $filter = $p->getFilter();
        $this->assertEquals('(ownerid > :ownerid_1) AND (ownerid < :ownerid_2)', $filter->getExpression());
        $this->assertEquals(['ownerid_1'=>'1','ownerid_2'=>'4'], $filter->getParameters());
        
        $p = new QueryParameterParser('ownerid', '>1||<4', $builder);
        $filter = $p->getFilter();
        $this->assertEquals('(ownerid > :ownerid_1) OR (ownerid < :ownerid_2)', $filter->getExpression());
        $this->assertEquals(['ownerid_1'=>'1','ownerid_2'=>'4'], $filter->getParameters());
        
        $p = new QueryParameterParser('id','>29 && <=37', $builder);
        $filter = $p->getFilter();
        $this->assertEquals('(id > :id_1) AND (id <= :id_2)', $filter->getExpression());
        $this->assertEquals(['id_1'=>'29','id_2'=>'37'], $filter->getParameters());

        $this->setExpectedException('Bolt\Exception\QueryParseException');
        $p = new QueryParameterParser('ownerid', '>1||<4 && <56', $builder);
        $filter = $p->getFilter();
    }
    
    public function testComplexOr()
    {
        $app = $this->getApp();
        $builder = $app['storage']->createQueryBuilder();
        
        $p = new QueryParameterParser('username ||| email', 'tester', $builder);
        $filter = $p->getFilter();
        $this->assertEquals('(username = :username_1) OR (email = :email_2)', $filter->getExpression());
        $this->assertEquals(['username_1'=>'tester','email_2'=>'tester'], $filter->getParameters());
        
        $p = new QueryParameterParser('username ||| email', 'tester ||| faker', $builder);
        $filter = $p->getFilter();
        $this->assertEquals('(username = :username_1) OR (email = :email_2)', $filter->getExpression());
        $this->assertEquals(['username_1'=>'tester','email_2'=>'faker'], $filter->getParameters());
    }
    
    public function testMissingBuilderError()
    {
        $p = new QueryParameterParser('username ||| email', 'tester');
        $this->setExpectedException('Bolt\Exception\QueryParseException');
        $filter = $p->getFilter();
    }
    
    public function testAddingCustomMatcher()
    {
        $app = $this->getApp();
        $builder = $app['storage']->createQueryBuilder();
        
        // In this test we'll make a custom matcher that allows a new syntax: username: '~test' as an alias for a like query
        
        $p = new QueryParameterParser('username', '~test', $builder);
        $p->addValueMatcher('\~(\w+)', ['value' => '%$1%', 'operator' => 'like'], true);
        $filter = $p->getFilter();
        $this->assertEquals('username LIKE :username_1', $filter->getExpression());
        $this->assertEquals(['%test%'], $filter->getParameters());
    }
    
    
}



