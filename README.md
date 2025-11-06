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
PROJECT_KEY=your-project-key-here
ACCESS_TOKEN=your-access-token-here
```

#### 3. Start Tracking Events
```php
<?php
require_once 'vendor/autoload.php';

use Traclytics\Traclytics;

// Configure once
Traclytics::configure([
    'projectKey' => getenv('PROJECT_KEY'),
    'accessToken' => getenv('ACCESS_TOKEN'),
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

### Advanced Configuration

#### Custom User ID Field
If your user model uses `employee_id` instead of `user_id`:
```env
TRACLYTICS_USER_ID_KEY=employee_id
```

**Examples:**
- `'user_id'` - Standard Laravel/PHP user ID
- `'employee_id'` - For HR/employee systems
- `'emp_no'` - Custom employee number

#### HRIS Mode (HR Applications)
Automatically track department information:
```env
TRACLYTICS_IS_HRIS=true
TRACLYTICS_DEPARTMENT_KEY=department
```

**When to use:**
- HR applications
- Employee management systems
- Any application that needs to track user departments

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

## What Gets Tracked Automatically?

The client automatically includes:
- ✅ **User ID** - Detected from Laravel Auth or session (configurable via `user_id_key`)
- ✅ **Platform** - Operating system (Windows, MacOS, Linux, Android, iOS)
- ✅ **Device** - Device type (Desktop, Mobile, Tablet)
- ✅ **Browser** - Browser name (Chrome, Firefox, Safari, Edge, etc.)
- ✅ **Department** - User's department (when `is_hris` is enabled)
- ✅ **Timestamp** - Event occurrence time

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
