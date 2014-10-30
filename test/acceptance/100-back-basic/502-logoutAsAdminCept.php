<?php
$I = new WebGuy($scenario);
$I->wantTo('log out of the backend as Admin');
$I->loginAs($users['admin']);
$I->see('Dashboard');
$I->click('Logout');
$I->see('You have been logged out');
$I->amOnPage('bolt');
$I->see('Please log on');
