<?php
/**
 * Consent Log class for WordPress.
 *
 * @package Consent Log
 * @version 4.9.5
 *
 * Plugin Name:       Consent Log
 * Description:       Adds a CPT and utility functions for the purpose of keeping logs from various consents.
 * Version:           4.9.5
 * Author:            Xenos (xkon) Konstantinos
 * Author URI:        https://xkon.gr
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
					'labels'       => array(
						'name'          => __( 'Consent Log', 'consent-log' ),
						'singular_name' => __( 'Consent Log', 'consent-log' ),
					),
					'public'       => true,
					'show_in_menu' => true,
					'hierarchical' => false,
					'supports'     => array( '' ),
				)
			);
		}

		/**
		 * Set the CPT columns.
		 *
		 * @since 4.9.6
		 *
		 * @param array $columns An array of columns that will be used in consents.
		 *
		 * @uses unset()
		 *
		 * @return array
		 */
		public function set_cl_consent_log_columns( $columns ) {

			unset( $columns['title'] );
			unset( $columns['date'] );

			$columns['uid']  = __( 'User ID', 'consent-log' );
			$columns['cid']  = __( 'Consent ID', 'consent-log' );
			$columns['sid']  = __( 'Status ID', 'consent-log' );
			$columns['date'] = __( 'Date', 'consent-log' );

			return $columns;
		}

		/**
		 * Set the CPT column data.
		 *
		 * @since 4.9.6
		 *
		 * @param string $column  The column for which we want to get the data.
		 * @param int    $post_id The consent post-ID.
		 *
		 * @uses get_post_meta()
		 *
		 * @return void
		 */
		public function cl_consent_log_columns_data( $column, $post_id ) {

			switch ( $column ) {
				case 'uid':
					echo esc_html( get_post_meta( $post_id, '_cl_uid', true ) );
					break;

				case 'cid':
					echo esc_html( get_post_meta( $post_id, '_cl_cid', true ) );
					break;

				case 'sid':
					$sid = (int) get_post_meta( $post_id, '_cl_sid', true );

					if ( 1 === $sid ) {
						esc_html_e( 'Accepted', 'consent-log' );
					} elseif ( 0 === $sid ) {
						esc_html_e( 'Declined', 'consent-log' );
					}
					break;
			}
		}

		/**
		 * Unset post actions from CPT table
		 *
		 * @since 4.9.6
		 *
		 * @param array   $actions An array of actions for the post-type.
		 * @param WP_Post $post    The consent post object.
		 *
		 * @uses unset()
		 *
		 * @return array           The $actions, modified.
		 */
		public function cl_consent_log_disable_quit_edit( $actions = array(), $post = null ) {

			if ( 'cl_consent_log' === get_post_type( $post ) ) {
				unset( $actions['inline hide-if-no-js'] );
				unset( $actions['edit'] );
				unset( $actions['view'] );

				return $actions;
			}

			return $actions;
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
				'post_type'  => 'cl_consent_log',
				'meta_query' => array(
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
