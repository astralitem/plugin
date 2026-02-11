<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Arknight_EDD_Account_Form_Plugin {
	/**
	 * @var Arknight_EDD_Account_Form_Plugin|null
	 */
	private static $instance = null;

	/**
	 * @var Arknight_EDD_Account_Form_Admin
	 */
	private $admin;

	/**
	 * @var Arknight_EDD_Account_Form_Frontend
	 */
	private $frontend;

	/**
	 * @var Arknight_EDD_Account_Form_Product_Cards
	 */
	private $product_cards;

	/**
	 * @var Arknight_EDD_Account_Form_Single_Template
	 */
	private $single_template;

	/**
	 * @return Arknight_EDD_Account_Form_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->admin           = new Arknight_EDD_Account_Form_Admin();
		$this->frontend        = new Arknight_EDD_Account_Form_Frontend( $this->admin );
		$this->product_cards   = new Arknight_EDD_Account_Form_Product_Cards( $this->admin );
		$this->single_template = new Arknight_EDD_Account_Form_Single_Template();
		$this->hooks();
	}

	private function hooks() {
		add_action( 'init', array( $this->frontend, 'register_shortcode' ) );
		add_action( 'init', array( $this->product_cards, 'register_shortcode' ) );

		add_action( 'wp_enqueue_scripts', array( $this->frontend, 'register_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this->product_cards, 'register_assets' ) );

		add_action( 'admin_menu', array( $this->admin, 'register_settings_page' ) );
		add_action( 'admin_init', array( $this->admin, 'register_settings' ) );
		add_action( 'admin_post_nopriv_arkn_submit_account', array( $this->frontend, 'handle_submission' ) );
		add_action( 'admin_post_arkn_submit_account', array( $this->frontend, 'handle_submission' ) );

		$this->single_template->hooks();
	}
}
