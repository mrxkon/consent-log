<?php
/**
 * Consent Log class for WordPress.
 *
 * @package Consent Log
 * @version 4.9.6
 *
 * Plugin Name:       Consent Log
 * Description:       Adds a CPT and utility functions for the purpose of keeping logs from various consents.
 * Version:           4.9.6
 * Author:            xkon, aristah
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       consent-log
 * Domain Path:       /languages
 */

/*
How to use:

- Initialize the Consent_Log
$cl_consent = new Consent_Log();

- Add a new Consent
$consent = $cl_consent->cl_add_consent( 'test@test.gr', 'form_1', 1 );

- Remove a Consent
$consent = $cl_consent->cl_remove_consent( 'test@test.gr', 'form_1' );

- Update a Consent
$consent = $cl_consent->cl_update_consent( 'test@test.gr', 'form_1', 0 );

- Check if Consent Exists
$consent = $cl_consent->cl_consent_exists( 'test@test.gr', 'form_1' );

- Check if the consent is Accepted
$consent = $cl_consent->cl_has_consent( 'test@test.gr', 'form_1' );

*/


// Check that the file is not accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'We\'re sorry, but you can not directly access this file.' );
}

if ( ! class_exists( 'Consent_Log' ) ) {

	/**
	 * A class for logging consents in a cl_consent_log custom post-type.
	 *
	 * @since 4.9.6
	 */
	class Consent_Log {

		/**
		 * Constructor.
		 *
		 * @since 4.9.6
		 */
		public function __construct() {
			$this->init();
		}

		/**
		 * Plugin initialization.
		 *
		 * @since 4.9.6
		 *
		 * @uses add_action()
		 * @uses add_filter()
		 */
		public function init() {

			add_action( 'init', array( $this, 'cl_consent_log_post_type' ), 0 );
			add_action( 'manage_posts_custom_column', array( $this, 'cl_consent_log_columns_data' ), 10, 2 );
			add_filter( 'manage_cl_consent_log_posts_columns', array( $this, 'set_cl_consent_log_columns' ) );
			add_filter( 'post_row_actions', array( $this, 'cl_consent_log_disable_quit_edit' ), 10, 2 );
			add_action( 'admin_menu', array( $this, 'cl_create_admin_submenu' ) );

		}

		/**
		 * Register the consent log post type
		 *
		 * @since 4.9.6
		 *
		 * @uses register_post_type()
		 */
		public function cl_consent_log_post_type() {

			register_post_type(
				'cl_consent_log', array(
					'labels'            => array(
						'name'          => __( 'Consent Log', 'consent-log' ),
						'singular_name' => __( 'Consent Log', 'consent-log' ),
					),
					'public'            => true,
					'show_in_menu'      => true,
					'hierarchical'      => false,
					'supports'          => array( '' ),
					'show_in_nav_menus' => false,
				)
			);
		}

		/**
		 * Create Admin Page for Consent Log.
		 *
		 * @since 4.9.6
		 *
		 * @uses add_submenu_page()
		 *
		 * @return void
		 */
		public function cl_create_admin_submenu() {

			add_submenu_page(
				'tools.php',
				'Consent Log',
				'Consent Log',
				'manage_options',
				'consent-log',
				array( $this, 'cl_create_admin_page' )
			);

		}

		/**
		 * Creates the Admin Page for Consent Log
		 *
		 * @since 4.9.6
		 *
		 * @uses esc_attr_e()
		 * @uses WP_Query()
		 * @uses have_posts()
		 * @uses the_post()
		 * @uses esc_html()
		 * @uses get_post_meta()
		 * @uses get_the_ID()
		 * @uses get_the_date()
		 * @uses get_the_time()
		 *
		 * @return void
		 */

		public function cl_create_admin_page() {
			?>
			<div class="wrap">
			<h1>Consent Log</h1>
			<table class="widefat striped" style="margin-top:20px;">
				<thead>
					<th class="row-title"><?php esc_attr_e( 'User ID', 'consent-log' ); ?></th>
					<th><?php esc_attr_e( 'Consent ID', 'consent-log' ); ?></th>
					<th><?php esc_attr_e( 'Status', 'consent-log' ); ?></th>
					<th><?php esc_attr_e( 'Date', 'consent-log' ); ?></th>
					<th></th>
				</thead>
				<?php
				$args = array(
					'post_type'      => 'cl_consent_log',
					'posts_per_page' => '-1',
				);

				$query = new WP_Query( $args );

				if ( $query->have_posts() ) {
					while ( $query->have_posts() ) {
						$query->the_post();
					?>
						<tr>
							<td class="row-title">
								<?php
								echo esc_html( get_post_meta( get_the_ID(), '_cl_uid', true ) );
								?>
							</td>
							<td class="row-title">
								<?php
								echo esc_html( get_post_meta( get_the_ID(), '_cl_cid', true ) );
								?>
							</td>
							<td class="row-title">
								<?php
								$sid = (int) get_post_meta( get_the_ID(), '_cl_sid', true );

								if ( 1 === $sid ) {
									esc_html_e( 'Accepted', 'consent-log' );
								} elseif ( 0 === $sid ) {
									esc_html_e( 'Declined', 'consent-log' );
								}
								?>
							</td>
							<td class="row-title">
								<?php
								echo get_the_date() . ' - ' . get_the_time();
								?>
							</td>
							<td class="row-title">
								<button class="button">Remove</button>
							</td>
						</tr>
					<?php
					}
				}
				?>
			</table>
			<?php
		}

		/**
		 * Checks if the consent exists in the CPT
		 *
		 * @uses sanitize_text_field()
		 * @uses WP_Query
		 * @uses have_posts()
		 *
		 * @param string $uid The user's email address.
		 * @param string $cid Consent ID.
		 *
		 * @return mixed consent id if exists | false if there's no consent
		 */
		public function cl_consent_exists( $uid, $cid ) {

			$uid = sanitize_text_field( $uid );
			$cid = sanitize_text_field( $cid );

			$args = array(
				'post_type'      => 'cl_consent_log',
				'posts_per_page' => '1',
				'meta_query'     => array(
					'relation'            => 'AND',
					'_user_email'         => array(
						'key'   => '_cl_uid',
						'value' => $uid,
					),
					'_consent_identifier' => array(
						'key'   => '_cl_cid',
						'value' => $cid,
					),
				),
			);

			$query = new WP_Query( $args );

			if ( $query->have_posts() ) {
				return $query->post->ID;
			}

			return false;
		}

		/**
		 * Checks the consent is of status 1=accepted
		 *
		 * @uses sanitize_text_field()
		 * @uses Consent_Log::cl_consent_exists()
		 * @uses get_post_meta()
		 *
		 * @param string $uid The user's email address.
		 * @param string $cid Consent ID.
		 *
		 * @return boolean true/false depending if the consent is accepted
		 */
		public function cl_has_consent( $uid, $cid ) {

			$uid = sanitize_text_field( $uid );
			$cid = sanitize_text_field( $cid );

			$exists = $this->cl_consent_exists( $uid, $cid );

			if ( $exists ) {
				if ( 1 === (int) get_post_meta( $exists, '_cl_sid', true ) ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Adds a new consent in the CPT
		 *
		 * @uses sanitize_text_field()
		 * @uses intval()
		 * @uses Consent_Log::cl_consent_exists()
		 * @uses wp_insert_post()
		 * @uses current_time()
		 * @uses update_post_meta()
		 *
		 * @param string $uid The user's email address.
		 * @param string $cid Consent ID.
		 * @param mixed  $sid Consent Status.
		 *
		 * @return boolean true/false depending if the consent is updated
		 */
		public function cl_add_consent( $uid, $cid, $sid ) {

			$uid = sanitize_text_field( $uid );
			$cid = sanitize_text_field( $cid );
			$sid = intval( $sid );

			$exists = $this->cl_consent_exists( $uid, $cid );

			if ( ! $exists ) {

				$user_id = 0;

				$consent = wp_insert_post(
					array(
						'post_author'   => $user_id,
						'post_status'   => 'publish',
						'post_type'     => 'cl_consent_log',
						'post_date'     => current_time( 'mysql', false ),
						'post_date_gmt' => current_time( 'mysql', true ),
					), true
				);

				update_post_meta( $consent, '_cl_uid', $uid );
				update_post_meta( $consent, '_cl_cid', $cid );
				update_post_meta( $consent, '_cl_sid', $sid );

				return true;
			}

			return false;
		}

		/**
		 * Delete a consent from the CPT
		 *
		 * @uses sanitize_text_field()
		 * @uses Consent_Log::cl_consent_exists()
		 * @uses wp_delete_post()
		 * @uses current_time()
		 *
		 * @param string $uid The user's email address.
		 * @param string $cid Consent ID.
		 *
		 * @return boolean true/false depending if the consent is deleted
		 */
		public function cl_remove_consent( $uid, $cid ) {

			$uid = sanitize_text_field( $uid );
			$cid = sanitize_text_field( $cid );

			$exists = $this->cl_consent_exists( $uid, $cid );

			if ( $exists ) {

				wp_delete_post( $exists );

				return true;
			}

			return false;
		}

		/**
		 * Update a consent from the CPT
		 *
		 * @uses sanitize_text_field()
		 * @uses intval()
		 * @uses Consent_Log::cl_consent_exists()
		 * @uses update_post_meta()
		 *
		 * @param string $uid The user's email address.
		 * @param string $cid Consent ID.
		 * @param mixed  $sid Consent Status.
		 *
		 * @return boolean true/false depending if the consent is updated
		 */
		public function cl_update_consent( $uid, $cid, $sid ) {

			$uid = sanitize_text_field( $uid );
			$cid = sanitize_text_field( $cid );
			$sid = intval( $sid );

			$exists = $this->cl_consent_exists( $uid, $cid );

			if ( $exists ) {

				update_post_meta( $exists, '_cl_sid', $sid );

				return true;
			}
			return false;
		}
	}
	new Consent_Log();
}
