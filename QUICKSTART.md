# Traclytics PHP Client - Quick Start Guide

Get started with Traclytics in 5 minutes or less!

## Laravel Quick Start

### 1. Install the Package
```bash
composer require bdmotaleb/traclytics-client
```

### 2. Publish Configuration (Optional)
```bash
php artisan vendor:publish --tag=traclytics-config
```

### 3. Add Environment Variables
Add these to your `.env` file:
```env
TRACLYTICS_PROJECT_KEY=your-project-key-here
TRACLYTICS_ACCESS_TOKEN=your-access-token-here
```

### 4. Start Tracking Events
```php
use Traclytics\Facades\Traclytics;

// In any controller, route, or service
Traclytics::track([
    'event_type' => 'user_login',
    'details' => [
        'method' => 'email',
    ]
]);
```

That's it! 🎉 Your events are now being tracked.

---

## Plain PHP Quick Start

### 1. Install the Package
```bash
composer require bdmotaleb/traclytics-client
```

### 2. Autoload Classes
```bash
composer dump-autoload
```

### 3. Set Environment Variables
Create a `.env` file or set environment variables:
```env
PROJECT_KEY=your-project-key-here
ACCESS_TOKEN=your-access-token-here
```

### 4. Start Tracking Events
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
    'details' => [
        'method' => 'email',
    ]
]);
```

That's it! 🎉 Your events are now being tracked.

### Alternative: Direct Client Usage
```php
<?php
require_once 'src/TraclyticsClient.php';

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

---

## Configuration Options

### Basic Configuration (Required)
```env
TRACLYTICS_PROJECT_KEY=your-project-key
TRACLYTICS_ACCESS_TOKEN=your-access-token
```

### Advanced Configuration (Optional)

#### Custom User ID Field
If your user model uses `employee_id` instead of `user_id`:
```env
TRACLYTICS_USER_ID_KEY=employee_id
```

#### HRIS Mode (HR Applications)
Automatically track department information:
```env
TRACLYTICS_IS_HRIS=true
TRACLYTICS_DEPARTMENT_KEY=department
```

#### Retry and Timeout Settings
```env
TRACLYTICS_MAX_RETRIES=3
TRACLYTICS_TIMEOUT_MS=10000
```

---

## What Gets Tracked Automatically?

The client automatically includes:

- ✅ **User ID** - From Laravel Auth or session
- ✅ **Platform** - Windows, MacOS, Linux, Android, iOS
- ✅ **Device** - Desktop, Mobile, Tablet
- ✅ **Browser** - Chrome, Firefox, Safari, Edge, etc.
- ✅ **Timestamp** - When the event occurred
- ✅ **Department** - When HRIS mode is enabled

You only need to provide:
- Event name (`event`)
- Custom details (`details`)

---

## Need More Help?

- 📚 [Full Documentation](README.md)
- 🐛 [Report Issues](https://github.com/bdmotaleb/traclytics-client/issues)

---

## Next Steps

1. ✅ Install the package
2. ✅ Configure credentials
3. ✅ Track your first event
4. 📊 View events in your Traclytics dashboard

Happy tracking! 🎯
