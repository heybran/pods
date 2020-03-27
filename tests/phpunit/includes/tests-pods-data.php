<?php

namespace Pods_Unit_Tests;

use stdClass;

require_once PODS_TEST_PLUGIN_DIR . '/classes/PodsData.php';

/**
 * Class Test_PodsData
 *
 * @package Pods_Unit_Tests
 * @group   pods
 * @group   pods-data
 */
class Test_PodsData extends Pods_UnitTestCase {

	/**
	 * @covers ::pods_sanitize
	 */
	public function test_pods_sanitize_returns_empty_string() {

		$this->assertEquals( '', pods_sanitize( '' ) );
	}

	/**
	 * @covers ::pods_sanitize
	 */
	public function test_pods_sanitize_returns_int() {

		$this->assertEquals( 1, pods_sanitize( 1 ) );
	}

	/**
	 * @covers ::pods_sanitize
	 */
	public function test_pods_sanitize_returns_float() {

		$this->assertEquals( 12.348329, pods_sanitize( 12.348329 ) );
	}

	/**
	 * @covers ::pods_sanitize
	 */
	public function test_pods_sanitize_null() {

		$this->assertEquals( null, pods_sanitize( null ) );
	}

	/**
	 * @covers ::pods_sanitize
	 */
	public function test_pods_sanitize_returns_object() {

		$object      = new stdClass();
		$object->foo = 1;
		$object->bar = 'a test string';

		$this->assertEquals( $object, pods_sanitize( $object ) );
	}

	/**
	 * @covers ::pods_sanitize
	 */
	public function test_pods_sanitize_returns_array() {

		$array = array(
			'foo' => 1,
			'bar' => 'a test string',
		);
		$this->assertEquals( $array, pods_sanitize( $array ) );
	}

	/**
	 * @covers ::pods_sanitize
	 */
	public function test_pods_sanitize_string() {

		$original = "'\\`";

		$this->assertEquals( "\'\\\`", pods_sanitize( $original ) );
	}

	/**
	 * @covers ::pods_sanitize
	 */
	public function test_pods_sanitize_sql() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_sanitize_like() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_slash() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_unsanitize() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_unslash() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_trim() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_v() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_v_sanitized() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_v_set() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_var() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_var_raw() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_var_set() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_query_arg() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_var_update() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_cast() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_create_slug() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_unique_slug() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_clean_name() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	/**
	 * @covers ::pods_absint
	 */
	public function test_pods_absint() {

		$this->assertEquals( 1, pods_absint( 1.234 ) );
	}

	/**
	 * @covers ::pods_absint
	 */
	public function test_pods_absint_no_negative() {

		$this->assertEquals( 1, pods_absint( - 1.234 ) );
	}

	/**
	 * @covers ::pods_absint
	 */
	public function test_pods_absint_allows_negative() {

		$this->assertEquals( - 1, pods_absint( - 1.234, true, true ) );
	}

	/**
	 * @covers ::pods_absint
	 */
	public function test_pods_absint_returns_zero_for_string() {

		$this->assertEquals( 0, pods_absint( 'asdf' ) );
	}

	public function test_pods_str_replace() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	/**
	 * @covers ::pods_mb_strlen
	 */
	public function test_pods_mb_strlen() {

		$this->assertEquals( 4, pods_mb_strlen( 'asdf' ) );
	}

	/**
	 * @covers ::pods_mb_substr
	 */
	public function test_pods_mb_substr() {

		$this->assertEquals( 'sd', pods_mb_substr( 'asdf', 1, 2 ) );
	}

	/**
	 * @covers ::pods_evaluate_tags
	 * @covers ::pods_evaluate_tag
	 */
	public function test_pods_evaluate_tags() {
		global $wpdb;

		/**
		 * Special magic tags.
		 * @link https://docs.pods.io/displaying-pods/magic-tags/special-magic-tags/
		 */
		$this->assertEquals(
			$wpdb->prefix,
			pods_evaluate_tag( '{@prefix}' )
		);
		$this->assertEquals(
			get_template_directory_uri(),
			pods_evaluate_tag( '{@template-url}' )
		);
		$this->assertEquals(
			get_stylesheet_directory_uri(),
			pods_evaluate_tag( '{@stylesheet-url}' )
		);
		$this->assertEquals(
			site_url(),
			pods_evaluate_tag( '{@site-url}' )
		);
		$this->assertEquals(
			home_url(),
			pods_evaluate_tag( '{@home-url}' )
		);
		$this->assertEquals(
			admin_url(),
			pods_evaluate_tag( '{@admin-url}' )
		);
		$this->assertEquals(
			includes_url(),
			pods_evaluate_tag( '{@includes-url}' )
		);
		$this->assertEquals(
			plugins_url(),
			pods_evaluate_tag( '{@plugins-url}' )
		);
		$this->assertEquals(
			network_site_url(),
			pods_evaluate_tag( '{@network-site-url}' )
		);
		$this->assertEquals(
			network_home_url(),
			pods_evaluate_tag( '{@network-home-url}' )
		);
		$this->assertEquals(
			network_admin_url(),
			pods_evaluate_tag( '{@network-admin-url}' )
		);
		$this->assertEquals(
			user_admin_url(),
			pods_evaluate_tag( '{@user-admin-url}' )
		);
		$this->assertEquals(
			get_current_user_id(),
			pods_evaluate_tag( '{@user.id}' )
		);
		$this->assertEquals(
			date_i18n( 'Y-m-d' ),
			pods_evaluate_tag( '{@date.Y-m-d}' )
		);
		$this->assertEquals(
			date_i18n( 'Y-m-d',
			strtotime( 'tomorrow' ) ), pods_evaluate_tag( '{@date.Y-m-d|tomorrow}' )
		);

		//$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_evaluate_tag_sanitized() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_evaluate_tag() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_serial_comma() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_var_user() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_hierarchical_list() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_hierarchical_list_recurse() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_hierarchical_select() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_hierarchical_select_recurse() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

	public function test_pods_list_filter() {

		$this->markTestIncomplete( 'not yet implemented' );
	}

}
