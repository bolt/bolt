<?php
$I = new WebGuy($scenario);
$I->wantTo('log into the backend as Admin');
$I->amOnPage('/bolt/login');
$I->fillField('username', $adminUser['username']);
$I->fillField('password', $adminUser['password']);
$I->click('Log on');
$I->see('Dashboard');
