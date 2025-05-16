<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\LLM\LLMServiceInterface;
use App\Services\LLM\MockLLMService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LLMServiceInterface::class, function ($app) {
            // Use the mock service by default for testing
            if ($app->environment('testing')) {
                return new MockLLMService();
            }
            
            // You could add logic to choose the real implementation based on env vars
            return new MockLLMService(); // Default to mock for now
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
