<?php

$ds = DIRECTORY_SEPARATOR;
// require_once dirname( __FILE__, 2 ) . $ds . '../../../wp-load.php';
require_once dirname( __FILE__, 2 ) . $ds . 'vendor' . $ds . 'autoload.php';
require_once dirname( __FILE__, 2 ) . $ds . 'vendor' . $ds . 'php-stubs' . $ds . 'wordpress-stubs' . $ds . 'wordpress-stubs.php';
require_once dirname( __FILE__, 2 ) . $ds . 'tests'. $ds . 'Mocks.php';
require_once dirname( __FILE__, 2 ) . $ds . 'hellotext.php';

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
 */

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
 */

expect()->extend('toBeOne', function () {
	return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
 */

class TestHelper {
	public static function find_or_create_user ($name = 'Jane Doe', $email = 'jane@doe.com', $password = 'doe') {
		$user = get_user_by('email', $email);

		if (! isset($user)) {
			$user = wp_create_user($name, $password, $email);
		}

		return $user;
	}
}
