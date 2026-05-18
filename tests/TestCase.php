<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Testy nie potrzebują rzeczywistego Vite build — bez tego @vite() w bladach
        // wybucha w CI, gdzie nie odpalamy `npm run build`.
        $this->withoutVite();
    }
}
