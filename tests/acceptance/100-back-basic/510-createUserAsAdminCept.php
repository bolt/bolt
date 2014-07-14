<?php
$foobar = array(
    'username' => 'foobar',
    'password' => 'foobar123',
    );

$I = new WebGuy($scenario);
$I->wantTo('Create a user');
$I->loginAs($users['admin']);
$I->click('Users');
$I->click('New user');
$I->see("Create a new user");
$I->fillField('form[username]', $foobar['username']);
$I->fillField('form[password]', $foobar['password']);
$I->fillField('form[password_confirmation]', $foobar['password']);
$I->fillField('form[email]', 'foobar@example.org');
$I->fillField('form[displayname]', 'Foo Bar');
$I->click('input[type=submit]');
$I->see("has been saved");
$I->click('Logout');
$I->loginAs($foobar);
$I->see('Dashboard');
