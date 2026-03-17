<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        // Use a separate test database to prevent wiping production data.
        // The test DB is created automatically by the test suite.
        putenv('DB_DATABASE=stock_purchase_test');
        $_ENV['DB_DATABASE'] = 'stock_purchase_test';
        $_SERVER['DB_DATABASE'] = 'stock_purchase_test';

        parent::setUp();
    }
}
