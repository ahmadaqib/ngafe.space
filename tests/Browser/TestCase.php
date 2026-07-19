<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Deliberately does NOT call withoutVite(): browser tests drive a real
    // Chromium instance against the built assets, so Alpine/Livewire's JS
    // must actually load from public/build (see Task 4.3, Docs/plan.md).
}
