# Traclytics Client

Lightweight PHP client for Traclytics API with Laravel support. Uses ext-curl. No extra dependencies.

## Features
- ✅ Simple and lightweight
- ✅ Laravel Service Provider with auto-discovery
- ✅ Configurable via environment variables or config files
- ✅ HRIS mode with automatic department tracking
- ✅ Custom user ID key support
- ✅ Automatic retry logic with exponential backoff
- ✅ Works with Laravel and plain PHP

## Quick Start

### Laravel Quick Start

#### 1. Install the Package
```bash
composer require bdmotaleb/traclytics-client
```

#### 2. Publish Configuration (Optional)
```bash
php artisan vendor:publish --provider="Traclytics\TraclyticsServiceProvider"
```

#### 3. Add Environment Variables
Add these to your `.env` file:
```env
TRACLYTICS_PROJECT_KEY=your-project-key
TRACLYTICS_ACCESS_TOKEN=your-access-token

TRACLYTICS_USER_ID_KEY=user_id
TRACLYTICS_IS_HRIS=false
TRACLYTICS_DEPARTMENT_KEY=department
TRACLYTICS_IS_ENABLE=true
```

#### 4. Start Tracking Events
```php
use Traclytics\Facades\Traclytics;

// In any controller, route, or service
Traclytics::track([
    'event_type' => 'user_login',
    'action_type' => 'authentication',
    'details' => [
        'method' => 'email',
    ]
]);
```

That's it! 🎉 Your events are now being tracked.

---

### Plain PHP Quick Start

#### 1. Install the Package
```bash
composer require bdmotaleb/traclytics-client
composer dump-autoload
```

#### 2. Set Environment Variables
Create a `.env` file or set environment variables:
```env
TRACLYTICS_PROJECT_KEY=your-project-key-here
TRACLYTICS_ACCESS_TOKEN=your-access-token-here

# Optional - defaults shown
TRACLYTICS_USER_ID_KEY=user_id
TRACLYTICS_IS_HRIS=false
TRACLYTICS_DEPARTMENT_KEY=department
TRACLYTICS_IS_ENABLE=true
```

#### 3. Start Tracking Events
```php
<?php
require_once 'vendor/autoload.php';

use Traclytics\Traclytics;

// Configure once - options are optional if using environment variables
Traclytics::configure([
    'projectKey' => getenv('TRACLYTICS_PROJECT_KEY'),
    'accessToken' => getenv('TRACLYTICS_ACCESS_TOKEN'),
    // Optional: override defaults via clientOptions
    // 'clientOptions' => [
    //     'userIdKey' => 'user_id',
    //     'isHris' => false,
    //     'departmentKey' => 'department',
    // ]
]);

// Track events
Traclytics::track([
    'event_type' => 'user_login',
    'action_type' => 'authentication',
    'details' => [
        'method' => 'email',
    ]
]);
```

That's it! 🎉 Your events are now being tracked.

**Requirements:** PHP extensions: `ext-curl`, `ext-json`.

---

## Usage Examples

### Laravel Usage

#### Using the Facade (Recommended):
```php
use Traclytics\Facades\Traclytics;

Route::get('/track', function () {
    return Traclytics::track([
        'event_type' => 'PGW-Report',
        'action_type' => 'view',
        'details' => [
            'page_name' => 'dashboard',
            'role' => 'admin',
            'location' => 'Dhaka',
        ],
    ]);
});
```

#### Using the Helper Function:
```php
traclytics_track([
    'event_type' => 'button_clicked',
    'action_type' => 'click',
    'details' => [
        'button_name' => 'subscribe',
        'location' => 'header',
    ],
]);
```

### Plain PHP Usage

#### Using Client Instance:
```php
<?php
use Traclytics\TraclyticsClient;

$client = new TraclyticsClient(
    'https://traclytics-api.sslwireless.com/api/v1',
    'your-project-key',
    'your-access-token',
    [
        'userIdKey' => 'user_id',
        'isHris' => false,
        'departmentKey' => 'department',
    ]
);

$result = $client->trackEvent([
    'event_type' => 'login',
    'action_type' => 'user_authentication',
    'details' => [
        'role' => 'admin',
        'location' => 'Dhaka',
    ]
]);
```

## Configuration Options

### Basic Configuration (Required)
```env
TRACLYTICS_PROJECT_KEY=your-project-key
TRACLYTICS_ACCESS_TOKEN=your-access-token
```

### Enable/Disable Tracking
Control whether events are sent to the Traclytics API:
```env
TRACLYTICS_IS_ENABLE=true   # Enable tracking (default)
TRACLYTICS_IS_ENABLE=false  # Disable tracking - events will be silently ignored
```

**Use Cases:**
- Set to `false` in development or staging environments to prevent test events from being tracked
- Set to `true` in production to enable tracking
- When disabled, all `track()` calls will return a success response without sending events to the API

### Advanced Configuration

#### Custom User ID Field
If your user model uses `employee_id` instead of `user_id`:
```env
TRACLYTICS_USER_ID_KEY=employee_id
```

**Examples:**
- `'user_id'` - Standard Laravel/PHP user ID (default)
- `'employee_id'` - For HR/employee systems
- `'emp_no'` - Custom employee number

#### HRIS Mode (HR Applications)
HRIS mode determines how department information is handled:

- **When HRIS mode is disabled** (`TRACLYTICS_IS_HRIS=false`, default): The client automatically detects and tracks department information from your application (Laravel Auth or session).
- **When HRIS mode is enabled** (`TRACLYTICS_IS_HRIS=true`): Department information is handled on the server side by the Traclytics API.

```env
TRACLYTICS_IS_HRIS=false  # Client-side department tracking (default)
TRACLYTICS_DEPARTMENT_KEY=department
```

**When to use:**
- Set `TRACLYTICS_IS_HRIS=false` (default) when you want the client to automatically detect and send department information
- Set `TRACLYTICS_IS_HRIS=true` when department information should be handled server-side by the Traclytics API

**Department Key Examples:**
- `'department'` - Standard department field
- `'dept'` - Abbreviated department field
- `'department_id'` - Department ID reference
- `'dept_code'` - Department code

#### Retry and Timeout Settings
```env
TRACLYTICS_MAX_RETRIES=3
TRACLYTICS_TIMEOUT_MS=10000
```

**Default Values:**
- `userIdKey`: `'user_id'`
- `isHris`: `false` (client-side department tracking enabled)
- `departmentKey`: `'department'`
- `isEnabled`: `true` (tracking enabled by default)
- `maxRetries`: `3`
- `timeoutMs`: `10000`

## What Gets Tracked Automatically?

The client automatically includes:
- ✅ **User ID** - Detected from Laravel Auth or session (configurable via `user_id_key`)
- ✅ **Platform** - Operating system (Windows, MacOS, Linux, Android, iOS)
- ✅ **Device** - Device type (Desktop, Mobile, Tablet)
- ✅ **Browser** - Browser name (Chrome, Firefox, Safari, Edge, etc.)
- ✅ **Department** - User's department (automatically detected when `TRACLYTICS_IS_HRIS=false`, which is the default. When `TRACLYTICS_IS_HRIS=true`, department is handled server-side)
- ✅ **Timestamp** - Event occurrence time

**Note:** All configuration options have sensible defaults, so you only need to set them if you want to override the defaults.

You only need to provide:
- Event type (`event_type`)
- Action type (`action_type`)
- Custom details (`details`)

## Error Handling

The client returns a standardized response format:

**Success Response:**
```json
{
    "status": "success",
    "code": 200,
    "data": { ... },
    "message": "Event tracked successfully"
}
```

**Error Response:**
```json
{
    "status": "failed",
    "code": 401,
    "data": null,
    "message": "Unauthorized",
    "errors": null
}
```

## Testing

Run the test suite:
```bash
composer test
```

Run with coverage:
```bash
composer test-coverage
```

## Support

- **Issues:** https://github.com/bdmotaleb/traclytics-client/issues
- **Source:** https://github.com/bdmotaleb/traclytics-client

## License

MIT License - see LICENSE file for details
