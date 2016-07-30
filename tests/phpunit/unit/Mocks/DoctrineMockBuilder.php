<?php

namespace Bolt\Tests\Mocks;

/**
 * Mock Builder for Doctrine objects
 */
class DoctrineMockBuilder extends \PHPUnit_Framework_TestCase
{
    /**
     * @return \Doctrine\DBAL\Platforms\AbstractPlatform|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getDatabasePlatformMock()
    {
        $mock = $this->getAbstractMock(
            'Doctrine\DBAL\Platforms\AbstractPlatform',
            [
                'getName',
                'getReservedKeywordsClass',
            ]
        );

        $mock->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('mysql'));

        $mock->expects($this->any())
            ->method('getReservedKeywordsClass')
            ->will($this->returnValue('Doctrine\DBAL\Platforms\Keywords\MySQLKeywords'));

        return $mock;
    }

    /**
     * @return \Doctrine\DBAL\Connection|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getConnectionMock()
    {
        $mock = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'beginTransaction',
                    'commit',
                    'rollback',
                    'prepare',
                    'query',
                    'executeQuery',
                    'executeUpdate',
                    'getDatabasePlatform',
                    'createQueryBuilder',
                    'connect',
                    'insert',
                ]
            )
            ->getMock();

        $mock->expects($this->any())
            ->method('prepare')
            ->will($this->returnValue($this->getStatementMock()));

        $mock->expects($this->any())
            ->method('query')
            ->will($this->returnValue($this->getStatementMock()));

        $mock->expects($this->any())
            ->method('executeQuery')
            ->will($this->returnValue($this->getStatementMock()));

        $mock->expects($this->any())
            ->method('createQueryBuilder')
            ->will($this->returnValue($this->getQueryBuilderMock($mock)));

        $mock->expects($this->any())
            ->method('getDatabasePlatform')
            ->will($this->returnValue($this->getDatabasePlatformMock()));

        return $mock;
    }

    /**
     * @return \Doctrine\DBAL\Query\QueryBuilder
     */
    public function getQueryBuilderMock($connection)
    {
        $exprmock = $this->getMock('Doctrine\DBAL\Query\Expression\ExpressionBuilder', null, [$connection]);
        $mock = $this->getMock("Doctrine\\DBAL\\Query\\QueryBuilder", ['expr'], [$connection]);
        $mock->expects($this->any())
            ->method('expr')
            ->will($this->returnValue($exprmock));

        return $mock;
    }

    /**
     * @param mixed $returnValue
     *
     * @return \Doctrine\DBAL\Driver\Statement|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getStatementMock($returnValue = 1)
    {
        $mock = $this->getAbstractMock(
            'Bolt\Tests\Mocks\DoctrineDbalStatementInterface', // In case you run PHPUnit <= 3.7, use 'Mocks\DoctrineDbalStatementInterface' instead.
            [
                'bindValue',
                'execute',
                'rowCount',
                'fetchColumn',
            ]
        );

        $mock->expects($this->any())
            ->method('fetchColumn')
            ->will($this->returnValue($returnValue));

        return $mock;
    }

    /**
     * @param string $class   The class name
     * @param array  $methods The available methods
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getAbstractMock($class, array $methods)
    {
        return $this->getMockForAbstractClass(
            $class,
            [],
            '',
            true,
            true,
            true,
            $methods,
            false
        );
    }
}
