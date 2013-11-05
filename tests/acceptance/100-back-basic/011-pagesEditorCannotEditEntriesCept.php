<?php
$I = new WebGuy($scenario);
$I->wantTo('be denied permission to edit Entries as the pagewriter user');
$I->loginAs($users['pagewriter']);
$I->amOnPage('bolt/editcontent/entries/');
$I->see('You do not have the right privileges');
