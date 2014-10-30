<?php
$I = new WebGuy($scenario);
$I->wantTo('make sure the admin user can view all content types');
$I->loginAs($users['admin']);
$I->see('Page');
$I->see('Entries');
$I->see('Showcases');
