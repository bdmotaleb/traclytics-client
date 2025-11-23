# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.0] - 2024-XX-XX

### Added
- Added `userNameKey` configuration option for custom user name field detection
- Added `detectUserName()` method with Laravel Auth and session support
- Automatically include `user_name` field in tracked events
- Added `TRACLYTICS_USER_NAME_KEY` environment variable support
- Updated README with `userNameKey` documentation and examples

### Changed
- Updated user agent string to `TraclyticsClient/1.3.0`

## [1.2.0] - Previous Release

### Added
- Custom user ID key support via `userIdKey` option
- HRIS mode support for department tracking
- Enable/disable tracking via `isEnabled` option

## [1.1.0] - Previous Release

### Added
- Initial release
- Laravel Service Provider with auto-discovery
- Automatic retry logic with exponential backoff
- Automatic platform, device, and browser detection

