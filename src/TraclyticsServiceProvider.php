<?php

namespace Traclytics;

use Illuminate\Support\ServiceProvider;

class TraclyticsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Merge package config with app config
        $this->mergeConfigFrom(
            __DIR__ . '/../config/traclytics.php',
            'traclytics'
        );

        // Register the TraclyticsClient as a singleton
        $this->app->singleton(TraclyticsClient::class, function ($app) {
            $config = $app['config']['traclytics'];

            // Merge client options with configuration keys
            $clientOptions = array_merge(
                $config['client_options'],
                [
                    'userIdKey' => $config['user_id_key'],
                    'isHris' => $config['is_hris'],
                    'departmentKey' => $config['department_key'],
                    'isEnabled' => $config['is_enabled'] ?? true,
                ]
            );

            // Use hardcoded base URL if not in config
            $baseUrl = $config['base_url'] ?? 'https://traclytics-api.sslwireless.com/api/v1';

            return new TraclyticsClient(
                $baseUrl,
                $config['project_key'] ?? '',
                $config['access_token'] ?? '',
                $clientOptions
            );
        });

        // Register the Traclytics facade accessor
        $this->app->singleton('traclytics', function ($app) {
            return $app->make(TraclyticsClient::class);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish the configuration file
        $this->publishes([
            __DIR__ . '/../config/traclytics.php' => config_path('traclytics.php'),
        ], 'traclytics-config');

        // Configure the static Traclytics class only if credentials are provided
        // This prevents errors during package discovery when .env is not loaded
        if ($this->app->bound('config')) {
            $config = $this->app['config']['traclytics'];
            
            // Only configure if we have credentials
            if (!empty($config['project_key']) && !empty($config['access_token'])) {
                $clientOptions = array_merge(
                    $config['client_options'] ?? [],
                    [
                        'userIdKey' => $config['user_id_key'] ?? 'id',
                        'isHris' => $config['is_hris'] ?? false,
                        'departmentKey' => $config['department_key'] ?? 'department',
                        'isEnabled' => $config['is_enabled'] ?? true,
                    ]
                );

                Traclytics::configure([
                    'projectKey' => $config['project_key'],
                    'accessToken' => $config['access_token'],
                    'clientOptions' => $clientOptions,
                ]);
            }
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [
            TraclyticsClient::class,
            'traclytics',
        ];
    }
}

