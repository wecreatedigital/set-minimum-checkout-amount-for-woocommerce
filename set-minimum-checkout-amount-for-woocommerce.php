<?php
/**
 * Plugin Name: Set Minimum Checkout Amount for WooCommerce
 * Plugin URI: https://wecreate.digital/blog/setting-a-minimum-order-amount-in-woocommerce/
 * Description: Prevent customers from completing their order by setting a minimum checkout amount.
 * Version: 1.0.3
 * Author: We Create Digital
 * Author URI: https://wecreate.digital
 * Developer: Dean Appleton-Claydon
 * Developer URI: https://dean.codes
 * Text Domain: set-minimum-purchase-amount-for-woocommerce
 *
 * WC requires at least: 3.9.0
 * WC tested up to: 4.8.0
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

  add_filter( 'woocommerce_general_settings','wecreate_woocommerce_set_minimum_order_settings', 10, 2 );
  function wecreate_woocommerce_set_minimum_order_settings( $settings ) {

      $settings[] = array(
        'title' => __( 'Set minimum purchase settings', 'set-minimum-purchase-amount-for-woocommerce' ),
        'type' => 'title',
        'desc' => 'Prevent customers from completing their order by setting a minimum checkout amount.',
        'id' => 'wecreate_minimum_order_settings',
      );

      $settings[] = array(
        'title' => __( 'Minimum checkout amount', 'set-minimum-purchase-amount-for-woocommerce' ),
        'desc' => __( 'Leave this field empty to allow any purchase to complete', 'set-minimum-purchase-amount-for-woocommerce' ),
        'id' => 'wecreate_minimum_order_value',
        'placeholder' => '9.99',
        'type' => 'number',
        'desc_tip' => true,
      );

      $settings[] = array(
        'title' => __( 'Check against', 'set-minimum-purchase-amount-for-woocommerce' ),
        'desc' => __( 'Minimum purchase to compare against order total or sub-total', 'set-minimum-purchase-amount-for-woocommerce' ),
        'id' => 'wecreate_minimum_order_use_amount',
        'type' => 'select',
        'options' => [
          'subtotal' => 'Sub-total',
          'total' => 'Total',
        ],
        'desc_tip' => true,
      );

      $settings[] = array(
        'title' => __( 'Checkout warning message', 'set-minimum-purchase-amount-for-woocommerce' ),
        'desc' => __( 'Present a message when the sub-total in the customer\'s basket is less than the minimum purchase set above. Use the shortcode [minimum_amount] to display the minimum amount set in the above field.', 'set-minimum-purchase-amount-for-woocommerce' ),
        'id' => 'wecreate_minimum_order_checkout_notification',
        'default' => 'Your order must be at least [minimum_amount] before you place your order',
        'placeholder' => 'Your order must be at least [minimum_amount] before you place your order',
        'type' => 'text',
        'desc_tip' => true,
      );

      $settings[] = array(
        'title' => __( 'Start date', 'set-minimum-purchase-amount-for-woocommerce' ),
        'desc' => __( 'Leave the field blank to continue', 'set-minimum-purchase-amount-for-woocommerce' ),
        'id' => 'wecreate_minimum_order_start_date',
        'type' => 'date',
        'desc_tip' => true,
      );

      $settings[] = array(
        'title' => __( 'End date', 'set-minimum-purchase-amount-for-woocommerce' ),
        'desc' => __( 'Leave the field blank to continue', 'set-minimum-purchase-amount-for-woocommerce' ),
        'id' => 'wecreate_minimum_order_end_date',
        'type' => 'date',
        'desc_tip' => true,
      );

      $settings[] = array(
        'type' => 'sectionend',
        'id' => 'wecreate_minimum_order_settings'
      );

      return $settings;
  }

  /**
   * Notices and checks
   *
   * Possible TODO: determine where to place the alert using woocommerce_before_cart woocommerce_after_cart hooks - these two don't update when changing shipping option
   */
  add_action( 'woocommerce_proceed_to_checkout', 'wecreate_woocommerce_minimum_order_amount' );
  add_action( 'woocommerce_review_order_before_payment', 'wecreate_woocommerce_minimum_order_amount', 11 );

  function wecreate_woocommerce_minimum_order_amount() {

    if ( is_cart() || is_checkout() ) {

      $minimum_order_value = (float) get_option( 'wecreate_minimum_order_value' );
      $start_date = get_option( 'wecreate_minimum_order_start_date' );
      $end_date = get_option( 'wecreate_minimum_order_end_date' );
      $compare_against = (string) get_option( 'wecreate_minimum_order_use_amount' );
      $check_against_total = (float) ($compare_against === 'subtotal') ? WC()->cart->subtotal : WC()->cart->total;

      // Minimum checkout amount when set
      if ( ! empty($minimum_order_value) && $check_against_total < $minimum_order_value ) {

        // Only apply minimum checkout amount when start date has passed
        if ( ! empty($start_date) && strtotime('now') < strtotime($start_date) ) {
          return true;
        }

        // Only apply minimum checkout amount before end date has passed
        if ( ! empty($end_date) && strtotime('now') > strtotime($end_date) ) {
          return true;
        }

        // Now display either the banner or redirect customer's back to the basket
        if ( is_cart() ) {
          remove_action( 'woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);

          wc_print_notice(
            str_replace(
              '[minimum_amount]',
              wc_price( $minimum_order_value ),
              get_option( 'wecreate_minimum_order_checkout_notification' )
            ),
            'error'
          );

        } elseif ( is_checkout() ) {

          wp_redirect(
            WC()->cart->get_cart_url()
          );

        }

      }

    }

    return true;

  }

}
