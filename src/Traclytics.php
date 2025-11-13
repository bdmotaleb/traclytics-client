<?php

namespace Traclytics;

class Traclytics
{
    private static ?TraclyticsClient $client = null;

    /**
     * Configure a shared client instance. If not called, env vars are used lazily.
     * options: [
     *   'projectKey' => string,
     *   'accessToken' => string,
     *   'clientOptions' => array
     * ]
     */
    public static function configure(array $options = []): void
    {
        $baseUrl = 'https://traclytics-api.sslwireless.com/api/v1';
        $projectKey = $options['projectKey'] ?? (getenv('TRACLYTICS_PROJECT_KEY') ?: '');
        $accessToken = $options['accessToken'] ?? (getenv('TRACLYTICS_ACCESS_TOKEN') ?: '');
        $clientOptions = $options['clientOptions'] ?? [];

        // Read client options from environment variables if not provided
        if (!isset($clientOptions['userIdKey'])) {
            $clientOptions['userIdKey'] = getenv('TRACLYTICS_USER_ID_KEY') ?: 'user_id';
        }
        if (!isset($clientOptions['isHris'])) {
            $isHrisEnv = getenv('TRACLYTICS_IS_HRIS');
            $clientOptions['isHris'] = $isHrisEnv !== false ? filter_var($isHrisEnv, FILTER_VALIDATE_BOOLEAN) : false;
        }
        if (!isset($clientOptions['departmentKey'])) {
            $clientOptions['departmentKey'] = getenv('TRACLYTICS_DEPARTMENT_KEY') ?: 'department';
        }
        if (!isset($clientOptions['isEnabled'])) {
            $isEnabledEnv = getenv('TRACLYTICS_IS_ENABLE');
            $clientOptions['isEnabled'] = $isEnabledEnv !== false ? filter_var($isEnabledEnv, FILTER_VALIDATE_BOOLEAN) : true;
        }

        self::$client = new TraclyticsClient($baseUrl, $projectKey, $accessToken, $clientOptions);
    }

    /**
     * Manually set the shared client instance.
     */
    public static function using(TraclyticsClient $client): void
    {
        self::$client = $client;
    }

    private static function getClient(): TraclyticsClient
    {
        if (self::$client === null) {
            self::configure();
        }
        return self::$client;
    }

    /**
     * Shorthand similar to Log::info() style usage.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function track(array $payload): array
    {
        return self::getClient()->trackEvent($payload);
    }

    /**
     * Explicit method name kept for symmetry with the client.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function trackEvent(array $payload): array
    {
        return self::getClient()->trackEvent($payload);
    }
}


