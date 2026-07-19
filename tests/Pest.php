<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Browser\TestCase as BrowserTestCase;

/*
| Only the functional-style Browser suite needs a uses() binding: the rest
| of the codebase is written as PHPUnit-style classes that already extend
| Tests\TestCase directly and are unaffected by this file.
*/
uses(BrowserTestCase::class, RefreshDatabase::class)->in('Browser');
