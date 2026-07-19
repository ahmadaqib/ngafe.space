<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // CI runs PHP tests before the frontend build. Feature tests assert
        // rendered behavior, not Vite's manifest integration.
        $this->withoutVite();
    }
}
