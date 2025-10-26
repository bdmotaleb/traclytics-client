<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Traclytics API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Traclytics API. This should not need to be changed
    | unless you're using a custom or self-hosted instance.
    |
    */

    'base_url' => env('TRACLYTICS_BASE_URL', 'https://traclytics-api.sslwireless.com/api/v1'),

    /*
    |--------------------------------------------------------------------------
    | Project Key
    |--------------------------------------------------------------------------
    |
    | Your Traclytics project key. This is used to identify your project
    | when sending events to the API.
    |
    */

    'project_key' => env('TRACLYTICS_PROJECT_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Access Token
    |--------------------------------------------------------------------------
    |
    | Your Traclytics access token. This is used to authenticate your
    | requests to the API.
    |
    */

    'access_token' => env('TRACLYTICS_ACCESS_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | User ID Key
    |--------------------------------------------------------------------------
    |
    | The session/attribute key used to identify the user ID when tracking
    | events. If your user model uses a different field name (e.g., 'employee_id',
    | 'emp_id'), you can specify it here.
    |
    | Examples:
    | - 'id' (default)
    | - 'user_id'
    | - 'employee_id'
    | - 'emp_no'
    |
    */

    'user_id_key' => env('TRACLYTICS_USER_ID_KEY', 'id'),

    /*
    |--------------------------------------------------------------------------
    | HRIS Mode
    |--------------------------------------------------------------------------
    |
    | Indicates whether this is an HRIS (Human Resources Information System)
    | integration. When enabled, department information will be automatically
    | attached to tracked events.
    |
    */

    'is_hris' => env('TRACLYTICS_IS_HRIS', false),

    /*
    |--------------------------------------------------------------------------
    | Department Key
    |--------------------------------------------------------------------------
    |
    | The session/attribute key used to identify the user's department.
    | This is only used when 'is_hris' is set to true. Configure this to
    | match your user model's department field name.
    |
    | Examples:
    | - 'department' (default)
    | - 'dept'
    | - 'department_id'
    | - 'dept_code'
    |
    */

    'department_key' => env('TRACLYTICS_DEPARTMENT_KEY', 'department'),

    /*
    |--------------------------------------------------------------------------
    | Client Options
    |--------------------------------------------------------------------------
    |
    | Configuration options for the HTTP client behavior including retry
    | logic, timeouts, and backoff strategies.
    |
    */

    'client_options' => [
        
        /*
        |--------------------------------------------------------------------------
        | Maximum Retries
        |--------------------------------------------------------------------------
        |
        | The maximum number of retry attempts for failed requests.
        |
        */
        
        'maxRetries' => (int) env('TRACLYTICS_MAX_RETRIES', 3),

        /*
        |--------------------------------------------------------------------------
        | Initial Delay (milliseconds)
        |--------------------------------------------------------------------------
        |
        | The initial delay in milliseconds before the first retry attempt.
        |
        */
        
        'initialDelayMs' => (int) env('TRACLYTICS_INITIAL_DELAY_MS', 400),

        /*
        |--------------------------------------------------------------------------
        | Backoff Factor
        |--------------------------------------------------------------------------
        |
        | The multiplier applied to the delay between each retry attempt.
        |
        */
        
        'backoffFactor' => (float) env('TRACLYTICS_BACKOFF_FACTOR', 2.0),

        /*
        |--------------------------------------------------------------------------
        | Maximum Delay (milliseconds)
        |--------------------------------------------------------------------------
        |
        | The maximum delay in milliseconds between retry attempts.
        |
        */
        
        'maxDelayMs' => (int) env('TRACLYTICS_MAX_DELAY_MS', 8000),

        /*
        |--------------------------------------------------------------------------
        | Request Timeout (milliseconds)
        |--------------------------------------------------------------------------
        |
        | The maximum time in milliseconds to wait for a request to complete.
        |
        */
        
        'timeoutMs' => (int) env('TRACLYTICS_TIMEOUT_MS', 10000),
    ],

];

