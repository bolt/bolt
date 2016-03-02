<?php
namespace Bolt\Tests\Configuration\Mock;

/**
 * Class to mock functionality of config class and provide different data.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 */
class Config
{
    public $db1 = [
        'driver'   => 'pdo_mysql',
        'user'     => 'test',
        'password' => 'test',
        'dbname'   => 'test',
    ];

    public $value;

    public function get($value)
    {
        return $this->value;
    }

    public function mockRoot()
    {
        $this->value = $this->db1;
        $this->value['user'] = 'root';
        $this->value['password'] = '';
    }

    public function mockEmptyDb()
    {
        $this->value = $this->db1;
        $this->value['dbname'] = false;
    }

    public function mockEmptyUser()
    {
        $this->value = $this->db1;
        $this->value['user'] = false;
    }

    public function mockMysql()
    {
        $this->value = $this->db1;
    }

    public function mockPostgres()
    {
        $this->value = $this->db1;
        $this->value['driver'] = 'pdo_pgsql';
    }

    public function mockSqlite()
    {
        $this->value = $this->db1;
        $this->value['driver'] = 'pdo_sqlite';
        $this->value['path'] = 'test/bolt.db';
    }

    public function mockUnsupportedPlatform()
    {
        $this->value = $this->db1;
        $this->value['driver'] = 'mongodb';
    }

    public function mockSqliteMem()
    {
        $this->value = $this->db1;
        $this->value['driver'] = 'pdo_sqlite';
        $this->value['memory'] = true;
    }
}
