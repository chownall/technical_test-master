<?php

namespace App\Providers;

use App\Contracts\HttpClientInterface;
use App\Services\GithubRepositoryProvider;
use App\Services\GitlabRepositoryProvider;
use App\Services\GuzzleHttpClient;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Enregistrer l'implémentation HTTP Client
        $this->app->bind(HttpClientInterface::class, GuzzleHttpClient::class);
        
        // Enregistrer les providers de repositories
        $this->app->bind(GithubRepositoryProvider::class);
        $this->app->bind(GitlabRepositoryProvider::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
