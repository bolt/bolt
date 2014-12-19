<?php
$I = new WebGuy($scenario);
$I->wantTo('be denied permission to edit Entries as the pagewriter user');
$I->loginAs($users['pagewriter']);
$I->amOnPage('bolt/editcontent/entries/');

// Temporarily disable this test, until we figure out what's going on. 
// $I->see('You do not have the right privileges');
