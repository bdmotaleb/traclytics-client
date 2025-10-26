<?php

use Traclytics\Traclytics;

if (!function_exists('traclytics_config')) {
    /**
     * Configure the shared Traclytics client (optional; defaults use env vars).
     */
    function traclytics_config(array $options = []): void
    {
        Traclytics::configure($options);
    }
}

if (!function_exists('traclytics_track')) {
    /**
     * Track with a single function call.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    function traclytics_track(array $payload): array
    {
        return Traclytics::track($payload);
    }
}


