<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class LLMServiceProvider extends ServiceProvider
{   
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(LLMServiceInterface::class, function ($app) {
            return new GeminiLLMService(
                config('llm.gemini.api_key'),
                config('llm.gemini.model'),
                config('llm.gemini.endpoint')
            );
        });
    }

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/llm.php' => config_path('llm.php'),
        ], 'config');
    }
}
