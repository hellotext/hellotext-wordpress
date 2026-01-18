# Hellotext WordPress Plugin - Development Guide

## Overview

This guide covers development setup, testing, code standards, and contribution guidelines for the Hellotext WordPress plugin.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Development Setup](#development-setup)
- [Project Structure](#project-structure)
- [Code Standards](#code-standards)
- [Testing](#testing)
- [Debugging](#debugging)
- [Build & Release](#build--release)
- [Contributing](#contributing)

## Prerequisites

### Required Software

- **PHP**: 8.2.12 or higher
- **Composer**: Latest version
- **WordPress**: 5.0 or higher
- **WooCommerce**: 5.0 or higher
- **Local Development Environment**:
  - Local by Flywheel
  - MAMP/XAMPP
  - Docker (wp-env)
  - Or similar

## Development Setup

### 1. Clone the Repository

```bash
cd wp-content/plugins/
git clone https://github.com/hellotext/hellotext-wordpress.git
cd hellotext-wordpress
```

### 2. Install Dependencies

```bash
composer install
```

This installs:
- Pest (testing framework)
- Mockery (mocking library)
- WordPress & WooCommerce stubs (for IDE autocomplete)

### 3. Configure Environment

Create or modify your WordPress configuration to set environment variables.

**Option A: wp-config.php**

```php
// Add before "That's all, stop editing!"
$_ENV['APP_ENV'] = 'development';
$_ENV['HELLOTEXT_API_URL'] = 'https://api-dev.hellotext.com';
```

**Option B: .htaccess**

```apache
SetEnv APP_ENV development
SetEnv HELLOTEXT_API_URL https://api-dev.hellotext.com
```

**Option C: Server Configuration**

For Local by Flywheel, add to site configuration or use `.env` file if supported.

### 4. Activate Plugin

1. Navigate to WordPress admin → Plugins
2. Activate "Hellotext"
3. Configure with development Business ID and Access Token

### 5. Enable Debugging

In `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

This logs errors to `wp-content/debug.log`.

## Code Standards

### PHP Standards

- **PHP Version**: 8.2+
- **Namespace**: `Hellotext\`
- **Coding Style**: WordPress Coding Standards with modern PHP

### Type Hints

All methods must have type hints for parameters and return types:

```php
public function process(?int $user_id, array $data = []): void
{
    // Implementation
}
```

### PHPDoc Comments

All classes and public methods must have PHPDoc comments:

```php
/**
 * Create a Hellotext profile.
 *
 * @param int $user_id WordPress user ID.
 * @param array $data Additional profile data.
 * @return array Profile response from API.
 * @throws \Exception If user not found.
 */
public function create(int $user_id, array $data = []): array
{
    // Implementation
}
```

### Constants Usage

Always use constants from the `Constants` class instead of magic strings:

```php
// ✅ Good
$session = $_COOKIE[Constants::SESSION_COOKIE_NAME];
$response = Client::post(Constants::API_ENDPOINT_PROFILES, $data);

// ❌ Bad
$session = $_COOKIE['hello_session'];
$response = Client::post('/profiles', $data);
```

### Error Handling

Use exceptions for error conditions and log appropriately:

```php
try {
    $adapter = new ProductAdapter($product_id);
    $payload = $adapter->get();
} catch (\Exception $e) {
    error_log('Hellotext: ' . $e->getMessage());
    return;
}
```

## Testing

The project uses [Pest](https://pestphp.com/) for testing.

### Running Tests

```bash
# Run all tests
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Unit/Adapters/ProductAdapterTest.php

# Run with coverage (requires Xdebug)
./vendor/bin/pest --coverage

# Run in parallel
./vendor/bin/paratest
```

### Writing Tests

#### Unit Tests

Unit tests are located in `tests/Unit/`. Each test file corresponds to a source file.

**Example: Testing an Adapter**

```php
<?php

use Hellotext\Adapters\ProductAdapter;

beforeEach(function () {
    $this->product = Mockery::mock('WC_Product');
    $this->product->shouldReceive('get_id')->andReturn(123);
    $this->product->shouldReceive('get_name')->andReturn('Test Product');
    // ... more mocks
});

test('ProductAdapter transforms product correctly', function () {
    $adapter = new ProductAdapter($this->product);
    $result = $adapter->get();

    expect($result)
        ->toHaveKey('reference', 123)
        ->toHaveKey('name', 'Test Product')
        ->toHaveKey('source', 'woo');
});

test('ProductAdapter throws exception for invalid product', function () {
    $adapter = new ProductAdapter(99999);
    $adapter->get(); // Should throw
})->throws(\Exception::class);
```

#### Test Structure

Use Pest's modern syntax:

```php
// Arrange
$data = ['key' => 'value'];

// Act
$result = (new Service())->process($data);

// Assert
expect($result)->toBe('expected');
```

#### Mocking

Use Mockery for mocking WordPress and WooCommerce functions:

```php
beforeEach(function () {
    // Mock WordPress functions
    Mockery::mock('function:get_option')
        ->shouldReceive('get_option')
        ->with('hellotext_business_id')
        ->andReturn('test_business_id');
});

afterEach(function () {
    Mockery::close();
});
```

### Test Coverage Goals

- **Unit Tests**: All Adapters and Services
- **Integration Tests**: Key user flows (order placement, profile creation)
- **Coverage Target**: 80%+ for critical paths

## Debugging

### Debug Logging

Enable WordPress debug logging and use `error_log()`:

```php
error_log('Hellotext Debug: ' . print_r($data, true));
```

Logs appear in `wp-content/debug.log`.

### API Debugging

To inspect API requests/responses:

```php
$response = Client::post('/profiles', $data);
error_log('API Response: ' . print_r($response, true));
```

### Event Tracking Debugging

To verify events are being tracked:

```php
add_filter('hellotext_event_payload', function($payload) {
    error_log('Event Payload: ' . print_r($payload, true));
    return $payload;
});
```

### WordPress Hooks Debug

Use `add_action()` to monitor hook execution:

```php
add_action('all', function($hook) {
    if (strpos($hook, 'woocommerce') !== false) {
        error_log('Hook fired: ' . $hook);
    }
});
```

## Build & Release

### Version Bump

1. Update version in `hellotext.php` header comment
2. Update `changelog.txt` with changes
3. Commit changes

```php
/**
 * Version: 1.3.0
 */
```

### Pre-Release Checklist

- [ ] Run all tests: `./vendor/bin/pest`
- [ ] Check for PHP errors/warnings
- [ ] Test on fresh WordPress installation
- [ ] Test with WooCommerce latest version
- [ ] Verify settings page functionality
- [ ] Test event tracking in Hellotext dashboard
- [ ] Update documentation if API changed
- [ ] Update changelog.txt

### Creating a Release

1. **Tag the release:**
```bash
git tag -a v1.3.0 -m "Release version 1.3.0"
git push origin v1.3.0
```

2. **Build release package:**
```bash
# Remove dev dependencies
composer install --no-dev

# Create zip
cd ..
zip -r hellotext-wordpress-1.3.0.zip hellotext-wordpress \
    -x "hellotext-wordpress/.git/*" \
    -x "hellotext-wordpress/tests/*" \
    -x "hellotext-wordpress/node_modules/*"
```

3. **Create GitHub release:**
   - Go to Releases → Draft new release
   - Select the tag
   - Upload the zip file
   - Add release notes from changelog

### Post-Release

1. Reinstall dev dependencies: `composer install`
2. Announce release to team
3. Monitor error logs for issues

## Contributing

### Workflow

1. **Fork & Clone**
```bash
git clone https://github.com/YOUR_USERNAME/hellotext-wordpress.git
cd hellotext-wordpress
composer install
```

2. **Create Feature Branch**
```bash
git checkout -b feature/my-new-feature
```

3. **Make Changes**
   - Write code
   - Add tests
   - Update documentation

4. **Run Tests**
```bash
./vendor/bin/pest
```

5. **Commit**
```bash
git add .
git commit -m "feat: add new feature"
```

Use [Conventional Commits](https://www.conventionalcommits.org/):
- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation
- `test:` - Tests
- `refactor:` - Code refactoring

6. **Push & PR**
```bash
git push origin feature/my-new-feature
```

Then create a Pull Request on GitHub.

### Code Review Checklist

- [ ] Code follows WordPress coding standards
- [ ] All functions have type hints
- [ ] PHPDoc comments present and accurate
- [ ] Tests written and passing
- [ ] No hardcoded strings (use Constants)
- [ ] Proper error handling
- [ ] WordPress/WooCommerce hooks used correctly
- [ ] Security best practices followed

## Common Development Tasks

### Adding a New Event

1. Create event file in `src/Events/`
2. Hook into appropriate WooCommerce action
3. Use `Event` class to track
4. Add event constant to `Constants.php`
5. Write test in `tests/Unit/Events/`

**Example:**

```php
<?php
use Hellotext\Api\Event;
use Hellotext\Constants;

add_action('woocommerce_new_action', 'hellotext_track_new_action');

function hellotext_track_new_action($data): void {
    $event = new Event();
    $event->track(Constants::EVENT_NEW_ACTION, [
        'object_parameters' => $data
    ]);
}
```

### Adding a New Adapter

1. Create adapter file in `src/Adapters/`
2. Implement `get(): array` method
3. Write comprehensive test
4. Document in API.md

### Adding Configuration Option

1. Add constant to `Constants.php`
2. Add field to `Settings.php` admin page
3. Use `get_option(Constants::OPTION_NAME)` to retrieve

## Troubleshooting

### Tests Failing

**Issue**: Mockery errors or WordPress function not found

**Solution**: Ensure WordPress and WooCommerce stubs are installed:
```bash
composer require --dev php-stubs/wordpress-stubs php-stubs/woocommerce-stubs
```

### Session Cookie Not Setting

**Issue**: `hello_session` cookie not being created

**Solution**: Check that `session_start()` is called early in plugin load and cookie settings (domain, path) are correct.

### API Requests Failing

**Issue**: 401 Unauthorized errors

**Solution**: Verify Business ID and Access Token are set correctly in WordPress admin → Extensions → Hellotext.

## Resources

- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WooCommerce Developer Documentation](https://woocommerce.github.io/code-reference/)
- [Pest Documentation](https://pestphp.com/docs/)
- [Hellotext API Documentation](https://docs.hellotext.com/)

## License

GPL v2 - See [LICENSE](LICENSE) file.

## Support

- **Issues**: [GitHub Issues](https://github.com/hellotext/hellotext-wordpress/issues)
- **Email**: support@hellotext.com
- **Documentation**: [API.md](API.md)
