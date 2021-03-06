<?php
/*
* File:     LaravelServiceProvider.php
* Category: Provider
* Author:   M. Goldenbaum
* Created:  19.01.17 22:21
* Updated:  -
*
* Description:
*  -
*/

namespace Vasim\IMAP\Providers;

use Illuminate\Support\ServiceProvider;
use Vasim\IMAP\Client;
use Vasim\IMAP\ClientManager;

/**
 * Class LaravelServiceProvider
 *
 * @package Vasim\IMAP\Providers
 */
class LaravelServiceProvider extends ServiceProvider {

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot() {
        $this->publishes([
            __DIR__.'/../../config/imap.php' => config_path('imap.php'),
        ]);
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    public function register() {
        $this->app->singleton(ClientManager::class, function($app) {
            return new ClientManager($app);
        });

        $this->app->singleton(Client::class, function($app) {
            return $app[ClientManager::class]->account();
        });

        $this->mergeConfigFrom(__DIR__.'/../../config/imap.php', 'imap');

    }
}