## bdmotaleb/event-api-client (PHP)

Lightweight PHP client for Traclytics API with Laravel support. Uses ext-curl. No extra dependencies.

### Features
- ✅ Simple and lightweight
- ✅ Laravel Service Provider with auto-discovery
- ✅ Configurable via environment variables or config files
- ✅ HRIS mode with automatic department tracking
- ✅ Custom user ID key support
- ✅ Automatic retry logic with exponential backoff
- ✅ Works with Laravel and plain PHP

### Install
```bash
composer require bdmotaleb/event-api-client
```

### Configuration

#### For Laravel Applications

The package uses Laravel's auto-discovery feature and is automatically registered.

**Step 1:** (Optional) Publish the configuration file
```bash
php artisan vendor:publish --provider="Traclytics\TraclyticsServiceProvider"
```

**Step 2:** Configure your environment variables in `.env`:
```env
# Required
TRACLYTICS_PROJECT_KEY=your-project-key
TRACLYTICS_ACCESS_TOKEN=your-access-token

TRACLYTICS_USER_ID_KEY=user_id
TRACLYTICS_IS_HRIS=false
TRACLYTICS_DEPARTMENT_KEY=department
```

**Step 3:** (Optional) Customize `config/traclytics.php` for advanced configuration.

#### For Plain PHP Applications

Set environment variables (recommended):
```env
PROJECT_KEY="your-project-key"
ACCESS_TOKEN="your-access-token"
```

**Requirements:** Ensure PHP extensions are enabled: `ext-curl`, `ext-json`.

After installing, if you add this package to an existing app, run:
```bash
composer dump-autoload
```

### Usage

#### Laravel Usage

**Using the Facade (Recommended):**
```php
use Traclytics\Facades\Traclytics;

// In a controller or route
Route::get('/track', function () {
    return Traclytics::track([
        'event_type' => 'page_view',
        'details' => [
            'page_name' => 'dashboard',
            'role' => 'admin',
            'location' => 'Dhaka',
        ],
    ]);
});
```

**Using the Helper Function:**
```php
// Track in one line - defaults are auto-populated
traclytics_track([
    'event_type' => 'button_clicked',
    'details' => [
        'button_name' => 'subscribe',
        'location' => 'header',
    ],
]);
```

#### Plain PHP Usage

**Using Static Class:**
```php
<?php
use Traclytics\Traclytics;

// Track events anywhere
Traclytics::track([
    'event_type' => 'user_login',
    'details' => [
        'login_method' => 'email',
    ],
]);
```

**Using Client Instance:**
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

### Configuration Options

#### `userIdKey` (default: 'user_id')
Specifies which field to use for user identification. Useful when your user model uses a different field name.

**Examples:**
- `'user_id'` - Standard Laravel/PHP user ID
- `'employee_id'` - For HR/employee systems
- `'emp_no'` - Custom employee number

#### `isHris` (required)
Enable HRIS (Human Resources Information System) mode to automatically track department information with each event.

**Values:**
- `true` - Enable HRIS mode (tracks department information)
- `false` - Disable HRIS mode (standard tracking)

**When to use:**
- HR applications
- Employee management systems
- Any application that needs to track user departments

#### `departmentKey` (required)
Specifies which field contains the department information. Only used when `isHris` is enabled.

**Examples:**
- `'department'` - Standard department field
- `'dept'` - Abbreviated department field
- `'department_id'` - Department ID reference
- `'dept_code'` - Department code
### Error Handling

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

### Automatic Features

The client automatically includes the following information with each tracked event:

- **User ID** - Detected from Laravel Auth or session (configurable via `user_id_key`)
- **Platform** - Operating system (Windows, MacOS, Linux, Android, iOS)
- **Device** - Device type (Desktop, Mobile, Tablet)
- **Browser** - Browser name (Chrome, Firefox, Safari, Edge, etc.)
- **Department** - User's department (when `is_hris` is enabled)
- **Timestamp** - Event occurrence time

### Testing

Run the test suite:
```bash
composer test
```

Run with coverage:
```bash
composer test-coverage
```

### Support

- **Issues:** https://github.com/bdmotaleb/event-api-client/issues

### License

MIT License - see LICENSE file for details
