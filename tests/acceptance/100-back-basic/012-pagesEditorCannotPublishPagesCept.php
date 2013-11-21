<?php
$I = new WebGuy($scenario);
$I->wantTo('be denied "publish" permissions on Pages as pagewriter user');
$I->loginAs($users['pagewriter']);
$I->see('New Page');
$I->click('New Page');
$I->see('Actions for this Page');
$I->fillField('title', 'A page I made');
$I->fillField('teaser', 'Woop woop woop! Crazy nice stuff inside!');
$I->fillField('body', 'Take it, take it! I have three more of these!');

// make sure the page cannot be published by setting its status in the
// edit form
$I->dontSeeInField('status', 'Published');

// let's save this page anyway, because we'll be needing it...
$I->click('Save Page');

// also check that the "publish page" context menu option isn't shown
$I->amOnPage('bolt');
$I->dontSee('Publish Page');
