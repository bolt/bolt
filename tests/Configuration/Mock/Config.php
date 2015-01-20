<?php
namespace Bolt\Tests\Configuration\Mock;

use Bolt\Application;

/**
 * Class to mock functionality of config class and provide different data.
 *
 * @author Ross Riley <riley.ross@gmail.com>
 *
 */
class Config
{
    
    public $db1 = array(
        'driver'=>'mysql',
        'username'=>'test',
        'password'=>'test' ,
        'databasename'=> 'test'   
    );
    
    public $value;
    
   
    
    public function get($value) 
    {
        return $this->value;
    }
    
    
    public function mockRoot() 
    {
        $this->value = $this->db1;
        $this->value['username'] = 'root';
        $this->value['password'] = '';
    }
    
    public function mockEmptyDb() 
    {
        $this->value = $this->db1;
        $this->value['databasename'] = false;
    }
    
    public function mockEmptyUser() 
    {
        $this->value = $this->db1;
        $this->value['username'] = false;
    }
    
    public function mockMysql() 
    {
        $this->value = $this->db1;
    }
    
    public function mockPostgres() 
    {
        $this->value = $this->db1;
        $this->value['driver'] = "postgres";
    }
    
    public function mockSqlite() 
    {
        $this->value = $this->db1;
        $this->value['driver'] = "sqlite";
    }
    
    public function mockBadDb() 
    {
        $this->value = $this->db1;
        $this->value['driver'] = "mongodb";
    }
    
    public function mockNoDriver() 
    {
        $this->value = $this->db1;
        unset($this->value['driver']);
    }
    
    public function mockSqliteMem()
    {
        $this->value = $this->db1;
        $this->value['driver'] = "sqlite";
        $this->value['memory'] = true;
    }
    
    

   
}
