<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    
    /**
     * Ensure all database transactions are properly closed.
     * This helps prevent "There is already an active transaction" errors
     * in tests that use database transactions.
     */
    protected function tearDown(): void 
    {
        // Check if there are any active transactions and roll them back
        try {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
                Log::info('Rolled back a lingering database transaction in tests');
            }
        } catch (\Exception $e) {
            Log::error('Error rolling back transactions: ' . $e->getMessage());
        }
        
        parent::tearDown();
    }
    
    /**
     * Clean up before each test to ensure no lingering transactions
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Check for any lingering transactions before starting the test
        try {
            while (DB::transactionLevel() > 0) {
                DB::rollBack();
                Log::warning('Found and rolled back a lingering transaction before test');
            }
        } catch (\Exception $e) {
            Log::error('Error rolling back pre-test transactions: ' . $e->getMessage());
        }
    }
}
