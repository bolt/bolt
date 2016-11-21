<?php
namespace Bolt\Tests\Storage\Query;

use Bolt\Storage\Query\QueryParameterParser;
use Bolt\Tests\BoltUnitTest;

/**
 * Class to test src/Storage/Query/QueryParameterParser.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class QueryParameterParserTest extends BoltUnitTest
{
    public function testValueParse()
    {
        $p = new QueryParameterParser();
        $expr = $p->parseValue('5');
        $this->assertEquals('5', $expr['value']);
        $this->assertEquals('eq', $expr['operator']);

        $p = new QueryParameterParser();
        $expr = $p->parseValue('<5');
        $this->assertEquals('5', $expr['value']);
        $this->assertEquals('lt', $expr['operator']);

        $p = new QueryParameterParser();
        $expr = $p->parseValue('!10');
        $this->assertEquals('10', $expr['value']);
        $this->assertEquals('neq', $expr['operator']);
    }

    public function testLikeValueParse()
    {
        $p = new QueryParameterParser();
        $expr = $p->parseValue('%fred%');
        $this->assertEquals('%fred%', $expr['value']);
        $this->assertEquals('like', $expr['operator']);

        $p = new QueryParameterParser();
        $expr = $p->parseValue('fred%');
        $this->assertEquals('fred%', $expr['value']);
        $this->assertEquals('like', $expr['operator']);

        $p = new QueryParameterParser();
        $expr = $p->parseValue('%fred');
        $this->assertEquals('%fred', $expr['value']);
        $this->assertEquals('like', $expr['operator']);

        $p = new QueryParameterParser();
        $expr = $p->parseValue('!');
        $this->assertEquals('', $expr['value']);
        $this->assertEquals('isNotNull', $expr['operator']);

        $p = new QueryParameterParser();
        $expr = $p->parseValue('[1,2,3]');
        $this->assertEquals(3, count($expr['value']));
        $this->assertEquals('in', $expr['operator']);
    }

    public function testCompositeOrAnd()
    {
        $app = $this->getApp();
        $expr = $app['storage']->createExpressionBuilder();

        $p = new QueryParameterParser($expr);
        $filter = $p->getFilter('ownerid', '>1 && <4');
        $this->assertEquals('(ownerid > :ownerid_1) AND (ownerid < :ownerid_2)', $filter->getExpression());
        $this->assertEquals(['ownerid_1' => '1', 'ownerid_2' => '4'], $filter->getParameters());

        $p = new QueryParameterParser($expr);
        $filter = $p->getFilter('ownerid', '>1||<4');
        $this->assertEquals('(ownerid > :ownerid_1) OR (ownerid < :ownerid_2)', $filter->getExpression());
        $this->assertEquals(['ownerid_1' => '1', 'ownerid_2' => '4'], $filter->getParameters());

        $p = new QueryParameterParser($expr);
        $filter = $p->getFilter('id', '>29 && <=37');
        $this->assertEquals('(id > :id_1) AND (id <= :id_2)', $filter->getExpression());
        $this->assertEquals(['id_1' => '29', 'id_2' => '37'], $filter->getParameters());

        $this->setExpectedException('Bolt\Exception\QueryParseException');
        $p = new QueryParameterParser($expr);
        $filter = $p->getFilter('ownerid', '>1||<4 && <56');
    }

    public function testComplexOr()
    {
        $app = $this->getApp();
        $expr = $app['storage']->createExpressionBuilder();

        $p = new QueryParameterParser($expr);
        $filter = $p->getFilter('username ||| email', 'tester');
        $this->assertEquals('(username = :username_1) OR (email = :email_2)', $filter->getExpression());
        $this->assertEquals(['username_1' => 'tester', 'email_2' => 'tester'], $filter->getParameters());

        $p = new QueryParameterParser($expr);
        $filter = $p->getFilter('username ||| email', 'tester ||| faker');
        $this->assertEquals('(username = :username_1) OR (email = :email_2)', $filter->getExpression());
        $this->assertEquals(['username_1' => 'tester', 'email_2' => 'faker'], $filter->getParameters());
    }

    public function testMissingBuilderError()
    {
        $p = new QueryParameterParser();
        $this->setExpectedException('Bolt\Exception\QueryParseException');
        $filter = $p->getFilter('username ||| email', 'tester');
    }

    public function testAddingCustomMatcher()
    {
        $app = $this->getApp();
        $expr = $app['storage']->createExpressionBuilder();

        // In this test we'll make a custom matcher that allows a new syntax: username: '~test' as an alias for a like query

        $p = new QueryParameterParser($expr);
        $p->addValueMatcher('\~(\w+)', ['value' => '%$1%', 'operator' => 'like'], true);
        $filter = $p->getFilter('username', '~test');
        $this->assertEquals('username LIKE :username_1', $filter->getExpression());
        $this->assertEquals(['%test%'], array_values($filter->getParameters()));
    }
}
