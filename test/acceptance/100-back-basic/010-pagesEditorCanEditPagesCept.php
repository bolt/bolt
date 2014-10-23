<?php
$I = new WebGuy($scenario);
$I->wantTo('create and edit Pages as pagewriter user');
$I->loginAs($users['pagewriter']);
$I->see('New Page');
$I->click('New Page');
$I->see('Actions for this Page');
$I->fillField('title', 'A page I made');
$I->fillField('teaser', 'Woop woop woop! Crazy nice stuff inside!');
$I->fillField('body', 'Take it, take it! I have three more of these!');
$I->click('Save Page');
$I->see('A page I made');
$I->see('Woop woop woop');
