<?php
$I = new WebGuy($scenario);
$I->wantTo('make sure the page editor user cannot see any content types except pages');
$I->loginAs($users['pagewriter']);
$I->see('View Pages');
$I->dontSee('View Entries');
$I->dontSee('View Kitchensinks');
$I->dontSee('View Dummies');

