<?php
$I = new WebGuy($scenario);
$I->wantTo('make sure the page editor user cannot see any content types except pages');
$I->loginAs($users['pagewriter']);
$I->see('View Pages');
$I->dontSee('Edit Entries');
$I->dontSee('Edit Showcases');
$I->dontSee('Edit Dummies');
