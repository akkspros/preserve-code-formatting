<?php

defined( 'ABSPATH' ) or die();

class Preserve_Code_Formatting_Test extends WP_UnitTestCase {

	public static function setUpBeforeClass() {
		c2c_PreserveCodeFormatting::get_instance()->install();

		add_filter( 'pcf_text', array( c2c_PreserveCodeFormatting::get_instance(), 'preserve_preprocess' ), 2 );
		add_filter( 'pcf_text', array( c2c_PreserveCodeFormatting::get_instance(), 'preserve_postprocess_and_preserve' ), 100 );
	}

	public function setUp() {
		parent::setUp();

		c2c_PreserveCodeFormatting::get_instance()->reset_options();
	}


	//
	//
	// DATA PROVIDERS
	//
	//


	public static function get_settings_and_defaults() {
		return array(
			array( 'preserve_tags', array( 'code', 'pre' ) ),
			array( 'preserve_in_posts', true ),
			array( 'preserve_in_comments', true ),
			array( 'wrap_multiline_code_in_pre', true ),
			array( 'use_nbsp_for_spaces', true ),
			array( 'nl2br', false ),
		);
	}

	 public static function get_preserved_tags( $more_tags = array() ) {
		return array(
			array( 'code' ),
			array( 'pre' ),
		);
	}

	public static function get_default_filters() {
		return array(
			array( 'the_content' ),
			array( 'the_excerpt' ),
		);
	}


	//
	//
	// HELPER FUNCTIONS
	//
	//


	private function set_option( $settings = array() ) {
		$defaults = array(
			'preserve_tags'              => array( 'code', 'pre' ),
			'preserve_in_posts'          => true,
			'preserve_in_comments'       => true,
			'wrap_multiline_code_in_pre' => true,
			'use_nbsp_for_spaces'        => true,
			'nl2br'                      => false,
		);
		$settings = wp_parse_args( $settings, $defaults );
		c2c_PreserveCodeFormatting::get_instance()->update_option( $settings, true );
	}

	private function preserve( $text, $filter = 'pcf_text' ) {
		return apply_filters( $filter, $text );
	}


	//
	//
	// TESTS
	//
	//


	public function test_class_exists() {
		$this->assertTrue( class_exists( 'c2c_PreserveCodeFormatting' ) );
	}

	public function test_plugin_framework_class_name() {
		$this->assertTrue( class_exists( 'c2c_PreserveCodeFormatting_Plugin_049' ) );
	}

	public function test_plugin_framework_version() {
		$this->assertEquals( '049', c2c_PreserveCodeFormatting::get_instance()->c2c_plugin_version() );
	}

	public function test_get_version() {
		$this->assertEquals( '3.9.1', c2c_PreserveCodeFormatting::get_instance()->version() );
	}

	public function test_instance_object_is_returned() {
		$this->assertTrue( is_a( c2c_PreserveCodeFormatting::get_instance(), 'c2c_PreserveCodeFormatting' ) );
	}

	public function test_hooks_plugins_loaded() {
		$this->assertEquals( 10, has_action( 'plugins_loaded', array( 'c2c_PreserveCodeFormatting', 'get_instance' ) ) );
	}

	/**
	 * @dataProvider get_settings_and_defaults
	 */
	public function test_default_settings( $setting, $value ) {
		$options = c2c_PreserveCodeFormatting::get_instance()->get_options();

		if ( is_bool( $value ) ) {
			if ( $value ) {
				$this->assertTrue( $options[ $setting ] );
			} else {
				$this->assertFalse( $options[ $setting ] );
			}
		} else {
			$this->assertEquals( $value, $options[ $setting ] );
		}
	}

	/**
	 * @dataProvider get_preserved_tags
	 */
	public function test_html_tags_are_preserved_in_preserved_tag( $tag ) {
		$code = '<strong>bold</strong> other markup <i>here</i>';
		$text = "Example <code>$code</code>";

		$this->assertEquals(
			'Example <code>' . htmlspecialchars( $code, ENT_QUOTES ) . '</code>',
			$this->preserve( $text )
		);
	}

	/**
	 * @dataProvider get_preserved_tags
	 */
	public function test_special_characters_are_preserved_in_preserved_tag( $tag ) {
		$code = "first\r\nsecond\rthird\n\n\n\n\$fourth\nfifth<?php test(); ?>";
		$text = "Example <code>$code</code>";
		$expected_code = "first\nsecond\nthird\n\n\$fourth\nfifth&lt;?php test(); ?&gt;";

		$this->assertEquals(
			'Example <pre><code>' . $expected_code . '</code></pre>',
			$this->preserve( $text )
		);
	}

	/**
	 * @dataProvider get_preserved_tags
	 */
	public function test_tabs_are_replaced_in_preserved_tag( $tag ) {
		$code = "\tfirst\n\t\tsecond";
		$text = "Example <code>$code</code>";

		$this->assertEquals(
			'Example <pre><code>' . str_replace( "\t", "&nbsp;&nbsp;", $code ) . '</code></pre>',
			$this->preserve( $text )
		);
	}

	/**
	 * @dataProvider get_preserved_tags
	 */
	public function test_spaces_are_preserved_in_preserved_tag( $tag ) {
		$text = "Example <$tag>preserve  multiple  spaces</$tag>";

		$this->assertEquals(
			"Example <$tag>preserve&nbsp;&nbsp;multiple&nbsp;&nbsp;spaces</$tag>",
			$this->preserve( $text )
		);
	}

	public function test_spaces_are_not_preserved_in_unhandled_tag() {
		$tag = 'strong';
		$text = "Example <$tag>preserve  multiple  spaces</$tag>";

		$this->assertEquals( $text, apply_filters( 'pcf_text', $text ) );
	}

	/**
	 * @dataProvider get_preserved_tags
	 */
	public function test_space_is_not_replaced_with_nbsp_if_false_for_setting_use_nbsp_for_spaces( $tag ) {
		$this->set_option( array( 'use_nbsp_for_spaces' => false ) );

		$text = "Example <$tag>preserve  multiple  spaces</$tag>";

		$this->assertEquals( $text, $this->preserve( $text ) );
	}

	public function test_multiline_code_gets_wrapped_in_pre() {
		$text = "<code>some code\nanother line\n yet another</code>";

		$this->assertEquals( "Example <pre>$text</pre>", $this->preserve( 'Example ' . $text ) );
	}

	public function test_multiline_pre_does_not_get_wrapped_in_pre() {
		$text = "Example <pre>some code\nanother line\n yet another</pre>";

		$this->assertEquals( $text, $this->preserve( $text ) );
	}

	public function test_multiline_code_not_wrapped_in_pre_if_setting_wrap_multiline_code_in_pre_is_false() {
		$this->set_option( array( 'wrap_multiline_code_in_pre' => false ) );

		$text = "Example <code>some code\nanother line\n yet another</code>";

		$this->assertEquals( $text, $this->preserve( $text ) );
	}

	public function test_nl2br_setting() {
		$this->set_option( array( 'nl2br' => true ) );

		$text = "<code>some code\nanother line\n yet another</code>";

		$this->assertEquals( str_replace( "\n", "<br />\n", "Example <pre>$text</pre>" ), $this->preserve( 'Example ' . $text ) );
	}

	public function test_code_preserving_honors_setting_preserve_tags() {
		$this->set_option( array( 'preserve_tags' => array( 'pre', 'strong' ) ) );
		$text = "<TAG>preserve  multiple  spaces</TAG>";

		// 'code' typically is preserved, but the setting un-does that
		$t = str_replace( 'TAG', 'code', $text );
		$this->assertEquals( $t, $this->preserve( $t ) );

		// it should now handle 'strong'
		$t = str_replace( 'TAG', 'strong', $text );
		$this->assertEquals( str_replace( ' ', '&nbsp;', $t ), $this->preserve( $t ) );
	}

	/**
	 * @dataProvider get_default_filters
	 */
	public function test_filters_default_filters( $filter ) {
		$code = '<strong>bold</strong> other markup <i>here</i>';
		$text = "Example <code>$code</code>";

		$this->assertEquals(
			wpautop( 'Example <code>' . htmlspecialchars( $code, ENT_QUOTES ) . '</code>' ),
			$this->preserve( $text, $filter )
		);
	}

	public function test_does_not_process_text_containing_code_block() {
		$text = <<<HTML
<!-- wp:paragraph -->
<p>This post has a code block:</p>
<!-- /wp:paragraph -->

<!-- wp:code -->
<pre class="wp-block-code"><code>if ( \$cat && \$dog < 1 ) {
	echo "<strong>Some code.</strong>";
}</code></pre>
<!-- /wp:code -->
HTML;

		$this->assertEquals( $text, $this->preserve( $text ) );
	}

	public function test_does_not_immediately_store_default_settings_in_db() {
		$option_name = c2c_PreserveCodeFormatting::SETTING_NAME;
		// Get the options just to see if they may get saved.
		$options     = c2c_PreserveCodeFormatting::get_instance()->get_options();

		$this->assertFalse( get_option( $option_name ) );
	}

	public function test_uninstall_deletes_option() {
		$option_name = c2c_PreserveCodeFormatting::SETTING_NAME;
		$options     = c2c_PreserveCodeFormatting::get_instance()->get_options();

		// Explicitly set an option to ensure options get saved to the database.
		$this->set_option( array( 'preserve_tags' => 'pre' ) );

		$this->assertNotEmpty( $options );
		$this->assertNotFalse( get_option( $option_name ) );

		c2c_PreserveCodeFormatting::uninstall();

		$this->assertFalse( get_option( $option_name ) );
	}

}
