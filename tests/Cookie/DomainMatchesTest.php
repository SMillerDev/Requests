<?php

namespace WpOrg\Requests\Tests\Cookie;

use WpOrg\Requests\Cookie;
use WpOrg\Requests\Tests\TestCase;
use WpOrg\Requests\Tests\TypeProviderHelper;
use WpOrg\Requests\Utility\CaseInsensitiveDictionary;

/**
 * @covers \WpOrg\Requests\Cookie::domain_matches
 */
final class DomainMatchesTest extends TestCase {

	/**
	 * Verify that invalid input will always result in a non-match.
	 *
	 * @dataProvider dataInvalidInput
	 *
	 * @param mixed $input Invalid parameter input.
	 *
	 * @return void
	 */
	public function testInvalidInput($input) {
		$attributes           = new CaseInsensitiveDictionary();
		$attributes['domain'] = 'example.com';
		$cookie               = new Cookie('requests-testcookie', 'testvalue', $attributes);

		$this->assertFalse($cookie->domain_matches($input));
	}

	/**
	 * Data Provider.
	 *
	 * @return array
	 */
	public function dataInvalidInput() {
		return TypeProviderHelper::getAllExcept(TypeProviderHelper::GROUP_STRING);
	}

	/**
	 * Manually set cookies without a domain/path set should always be valid.
	 *
	 * Cookies parsed from headers internally in Requests will always have a
	 * domain/path set, but those created manually will not. Manual cookies
	 * should be regarded as "global" cookies (that is, set for `.`).
	 *
	 * @dataProvider dataManuallySetCookie
	 *
	 * @param string $domain Domain to verify for a match.
	 *
	 * @return void
	 */
	public function testManuallySetCookie($domain) {
		$cookie = new Cookie('requests-testcookie', 'testvalue');

		$this->assertTrue($cookie->domain_matches($domain));
	}

	/**
	 * Data provider.
	 *
	 * @return array
	 */
	public function dataManuallySetCookie() {
		$domains = [
			'example.com',
			'example.net',
		];

		return $this->textArrayToDataprovider($domains);
	}

	/**
	 * @dataProvider dataDomainMatch
	 */
	public function testDomainExactMatch($original, $check, $matches, $domain_matches) {
		$attributes           = new CaseInsensitiveDictionary();
		$attributes['domain'] = $original;
		$cookie               = new Cookie('requests-testcookie', 'testvalue', $attributes);
		$this->assertSame($matches, $cookie->domain_matches($check));
	}

	/**
	 * @dataProvider dataDomainMatch
	 */
	public function testDomainMatch($original, $check, $matches, $domain_matches) {
		$attributes           = new CaseInsensitiveDictionary();
		$attributes['domain'] = $original;
		$flags                = [
			'host-only' => false,
		];
		$cookie               = new Cookie('requests-testcookie', 'testvalue', $attributes, $flags);
		$this->assertSame($domain_matches, $cookie->domain_matches($check));
	}

	/**
	 * Data provider.
	 *
	 * @return array
	 */
	public function dataDomainMatch() {
		return [
			['example.com', 'example.com', true, true],
			['example.com', 'www.example.com', false, true],
			['example.com', 'example.net', false, false],

			// Leading period
			['.example.com', 'example.com', true, true],
			['.example.com', 'www.example.com', false, true],
			['.example.com', 'example.net', false, false],

			// Prefix, but not subdomain
			['example.com', 'notexample.com', false, false],
			['example.com', 'notexample.net', false, false],

			// Reject IP address prefixes
			['127.0.0.1', '127.0.0.1', true, true],
			['127.0.0.1', 'abc.127.0.0.1', false, false],
			['127.0.0.1', 'example.com', false, false],

			// Check that we're checking the actual length
			['127.com', 'test.127.com', false, true],
		];
	}
}
