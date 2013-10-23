<?php
// Here you can initialize variables that will for your tests

$I = new WebGuy($scenario);
$I->haveInDatabase('bolt_users',
    array(
        'id' => '2',
        'username' => 'admin',
        'password' => '$P$DAjTrQBjYN2uv5xtN0pGDPWPUkcGll/',
        'email' => 'admin@example.org',
        'lastseen' => '1900-01-01',
        'lastip' => '',
        'displayname' => 'Admin',
        'userlevel' => '6',
        'contenttypes' => 'a:4:{i:0;s:7:"dummies";i:1;s:7:"entries";i:2;s:5:"pages";i:3;s:12:"kitchensinks";}',
        'stack' => 'a:0:{}',
        'enabled' => '1',
        'shadowpassword' => '',
        'shadowtoken' => '',
        'shadowvalidity' => '1900-01-01',
        'failedlogins' => '0',
        'throttleduntil' => '1900-01-01',
        ));

$adminUser = array(
    'username' => 'admin',
    'password' => 'admin1',
    );
