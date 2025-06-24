<?php
/**
 * Plugin Name: WooCommerce Viral Loop Referral
 * Plugin URI: https://yoursite.com
 * Description: Integrates Viral Loop referral system with WooCommerce to automatically generate and distribute unique coupon codes when referrals are accepted.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yoursite.com
 * Requires at least: 5.0
 * Tested up to: 6.6
 * Requires PHP: 7.4
 * WC requires at least: 4.0
 * WC tested up to: 9.0
 * Text Domain: wc-viral-loop-referral
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_VIRAL_LOOP_REFERRAL_VERSION', '1.0.0');
define('WC_VIRAL_LOOP_REFERRAL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_VIRAL_LOOP_REFERRAL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_VIRAL_LOOP_REFERRAL_PLUGIN_FILE', __FILE__);

/**
 * Main Plugin Class
 */
class WC_Viral_Loop_Referral {
    
    /**
     * Single instance of the class
     */
    private static $instance = null;
    
    /**
     * Plugin settings
     */
    private $settings = array();
    
    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Load plugin text domain
        load_plugin_textdomain('wc-viral-loop-referral', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize settings
        $this->init_settings();
        
        // Initialize hooks
        $this->init_hooks();
        
        // Initialize admin
        if (is_admin()) {
            $this->init_admin();
        }
    }
    
    /**
     * Initialize settings
     */
    private function init_settings() {
        $default_settings = array(
            'discount_type' => 'percent',
            'discount_amount' => 20,
            'expiry_days' => 30,
            'usage_limit' => 1,
            'usage_limit_per_user' => 1,
            'minimum_amount' => 0,
            'free_shipping' => false,
            'individual_use' => true,
            'email_subject' => __('Welcome! Your exclusive discount code is ready', 'wc-viral-loop-referral'),
            'sender_name' => get_bloginfo('name'),
            'sender_email' => get_option('admin_email'),
            'viral_loop_origin' => 'https://app.viral-loops.com',
            'product_ids' => array(),
            'exclude_product_ids' => array(),
            'product_categories' => array(),
            'exclude_product_categories' => array(),
            'exclude_sale_items' => false,
            'enable_tiered_referrals' => true,
            'tiered_threshold' => 3,
            'tiered_discount_amount' => 10
        );
        
        $this->settings = wp_parse_args(get_option('wc_viral_loop_referral_settings', array()), $default_settings);
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Handle referral acceptance
        add_action('init', array($this, 'handle_referral_acceptance'));
        
        // Auto-apply coupon from URL
        add_action('wp_loaded', array($this, 'auto_apply_coupon_from_url'));
        
        // AJAX handlers
        add_action('wp_ajax_accept_referral', array($this, 'ajax_accept_referral'));
        add_action('wp_ajax_nopriv_accept_referral', array($this, 'ajax_accept_referral'));
        add_action('wp_ajax_get_products_for_selector', array($this, 'ajax_get_products_for_selector'));
        add_action('wp_ajax_preview_referral_email', array($this, 'ajax_preview_referral_email'));
        
        // Webhook handlers (no login required)
        add_action('wp_ajax_viral_loop_webhook', array($this, 'handle_viral_loop_webhook'));
        add_action('wp_ajax_nopriv_viral_loop_webhook', array($this, 'handle_viral_loop_webhook'));
        
        // Add scripts to footer
        add_action('wp_footer', array($this, 'add_frontend_script'));
        
        // Add shortcode
        add_shortcode('referral_success', array($this, 'referral_success_shortcode'));
        
        // Add custom columns to coupons list
        add_filter('manage_shop_coupon_posts_columns', array($this, 'add_coupon_columns'));
        add_action('manage_shop_coupon_posts_custom_column', array($this, 'populate_coupon_columns'), 10, 2);
    }
    
    /**
     * Initialize admin
     */
    private function init_admin() {
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Add settings link to plugin page
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
        
        // Initialize settings
        add_action('admin_init', array($this, 'init_admin_settings'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Create referral success page
        $this->create_referral_success_page();
        
        // Set default settings
        if (!get_option('wc_viral_loop_referral_settings')) {
            update_option('wc_viral_loop_referral_settings', $this->settings);
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php _e('WooCommerce Viral Loop Referral requires WooCommerce to be installed and active.', 'wc-viral-loop-referral'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Handle referral acceptance
     */
    public function handle_referral_acceptance() {
        // Check if this is a referral acceptance request
        if (isset($_GET['accept_referral']) || (isset($_POST['action']) && $_POST['action'] === 'accept_referral')) {
            
            // Get referral data
            $referral_token = sanitize_text_field($_GET['referral_token'] ?? $_POST['referral_token'] ?? '');
            $referrer_email = sanitize_email($_GET['referrer_email'] ?? $_POST['referrer_email'] ?? '');
            $new_user_email = sanitize_email($_GET['new_user_email'] ?? $_POST['new_user_email'] ?? '');
            
            if (!empty($referral_token) && !empty($new_user_email)) {
                $coupon_code = $this->create_referral_coupon($referral_token, $referrer_email, $new_user_email);
                
                if ($coupon_code) {
                    // Send email with coupon
                    $this->send_referral_coupon_email($new_user_email, $coupon_code, $referrer_email);
                    
                    // Return JSON response for AJAX requests
                    if (wp_doing_ajax() || (isset($_POST['action']) && $_POST['action'] === 'accept_referral')) {
                        wp_send_json_success(array(
                            'coupon_code' => $coupon_code,
                            'message' => __('Welcome! Your exclusive coupon code has been created.', 'wc-viral-loop-referral'),
                            'coupon_url' => $this->get_coupon_apply_url($coupon_code)
                        ));
                    }
                    
                    // For direct URL access, redirect to thank you page
                    wp_redirect(add_query_arg(array(
                        'referral_success' => '1',
                        'coupon_code' => $coupon_code
                    ), home_url('/referral-success/')));
                    exit;
                } else {
                    if (wp_doing_ajax()) {
                        wp_send_json_error(array('message' => __('Failed to create coupon code.', 'wc-viral-loop-referral')));
                    }
                }
            }
        }
    }
    
    /**
     * Create referral coupon
     */
    private function create_referral_coupon($referral_token, $referrer_email = '', $new_user_email = '') {
        // Generate unique coupon code
        $coupon_code = 'REF-' . strtoupper(substr(md5($referral_token . $new_user_email . time()), 0, 8));
        
        // Check if coupon already exists
        if (wc_get_coupon_id_by_code($coupon_code)) {
            $coupon_code = 'REF-' . strtoupper(substr(md5($referral_token . $new_user_email . time() . rand()), 0, 8));
        }
        
        // Count previous successful referrals by this referrer
        $previous_referrals = $this->count_referrer_successful_referrals($referrer_email);
        
        // Determine discount settings based on referral count
        $discount_type = $this->settings['discount_type'];
        $discount_amount = $this->settings['discount_amount'];
        
        // If tiered referrals are enabled and this referrer has reached the threshold
        if ($this->settings['enable_tiered_referrals'] && $previous_referrals >= $this->settings['tiered_threshold']) {
            $discount_type = 'percent';
            $discount_amount = $this->settings['tiered_discount_amount'];
        }
        
        // Create the coupon
        $coupon = new WC_Coupon();
        
        // Set coupon properties from settings (with potential override for 4th+ referrals)
        $coupon->set_code($coupon_code);
        $coupon->set_description(__('Referral welcome coupon', 'wc-viral-loop-referral'));
        $coupon->set_discount_type($discount_type);
        $coupon->set_amount($discount_amount);
        $coupon->set_individual_use($this->settings['individual_use']);
        $coupon->set_usage_limit($this->settings['usage_limit']);
        $coupon->set_usage_limit_per_user($this->settings['usage_limit_per_user']);
        $coupon->set_minimum_amount($this->settings['minimum_amount']);
        $coupon->set_free_shipping($this->settings['free_shipping']);
        $coupon->set_email_restrictions(array($new_user_email));
        
        // Set product restrictions
        if (!empty($this->settings['product_ids'])) {
            $coupon->set_product_ids($this->settings['product_ids']);
        }
        
        if (!empty($this->settings['exclude_product_ids'])) {
            $coupon->set_excluded_product_ids($this->settings['exclude_product_ids']);
        }
        
        if (!empty($this->settings['product_categories'])) {
            $coupon->set_product_categories($this->settings['product_categories']);
        }
        
        if (!empty($this->settings['exclude_product_categories'])) {
            $coupon->set_excluded_product_categories($this->settings['exclude_product_categories']);
        }
        
        if ($this->settings['exclude_sale_items']) {
            $coupon->set_exclude_sale_items(true);
        }
        
        // Set expiration date
        if ($this->settings['expiry_days'] > 0) {
            $coupon->set_date_expires(time() + ($this->settings['expiry_days'] * 24 * 60 * 60));
        }
        
        // Save the coupon
        $coupon_id = $coupon->save();
        
        if ($coupon_id) {
            // Store referral metadata
            update_post_meta($coupon_id, '_referral_token', $referral_token);
            update_post_meta($coupon_id, '_referrer_email', $referrer_email);
            update_post_meta($coupon_id, '_referee_email', $new_user_email);
            update_post_meta($coupon_id, '_referral_created', current_time('mysql'));
            update_post_meta($coupon_id, '_is_referral_coupon', 'yes');
            update_post_meta($coupon_id, '_referrer_referral_count', $previous_referrals + 1); // Track this referrer's total count
            update_post_meta($coupon_id, '_is_tiered_referral', ($this->settings['enable_tiered_referrals'] && $previous_referrals >= $this->settings['tiered_threshold']) ? 'yes' : 'no'); // Mark if this used tiered discount
            
            return $coupon_code;
        }
        
        return false;
    }
    
    /**
     * Count successful referrals by a specific referrer
     */
    private function count_referrer_successful_referrals($referrer_email) {
        if (empty($referrer_email)) {
            return 0;
        }
        
        $referral_coupons = get_posts(array(
            'post_type' => 'shop_coupon',
            'meta_query' => array(
                array(
                    'key' => '_referrer_email',
                    'value' => $referrer_email,
                    'compare' => '='
                ),
                array(
                    'key' => '_is_referral_coupon',
                    'value' => 'yes',
                    'compare' => '='
                )
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));
        
        return count($referral_coupons);
    }
    
    /**
     * Send referral coupon email
     */
    private function send_referral_coupon_email($recipient_email, $coupon_code, $referrer_email = '') {
        $coupon_url = $this->get_coupon_apply_url($coupon_code);
        $expiry_date = '';
        
        if ($this->settings['expiry_days'] > 0) {
            $expiry_date = date('F j, Y', time() + ($this->settings['expiry_days'] * 24 * 60 * 60));
        }
        
        // Get the actual coupon to determine its discount
        $coupon_id = wc_get_coupon_id_by_code($coupon_code);
        $coupon = new WC_Coupon($coupon_id);
        $is_tiered = get_post_meta($coupon_id, '_is_tiered_referral', true) === 'yes';
        
        // Use actual coupon values for discount text
        $discount_text = $coupon->get_discount_type() === 'percent' 
            ? $coupon->get_amount() . '% off' 
            : wc_price($coupon->get_amount()) . ' off';
        
        // HTML email template
        $message = $this->get_email_template($coupon_code, $coupon_url, $discount_text, $expiry_date, $referrer_email, $is_tiered);
        
        // Set headers for HTML email
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->settings['sender_name'] . ' <' . $this->settings['sender_email'] . '>'
        );
        
        // Send the email
        return wp_mail($recipient_email, $this->settings['email_subject'], $message, $headers);
    }
    
    /**
     * Get email template
     */
    private function get_email_template($coupon_code, $coupon_url, $discount_text, $expiry_date, $referrer_email, $is_tiered = false) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($this->settings['email_subject']); ?></title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #E8EFF4; }
                .email-container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                .header { background: #5096ce; color: white; padding: 30px 20px; text-align: center; }
                .header h1 { margin: 0; font-size: 24px; }
                .content { padding: 30px; background-color: #E8EFF4;}
                .coupon-box { background: #f8f9fa; border: 2px dashed #5096ce; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px; }
                .coupon-code { font-size: 24px; font-weight: bold; color: #5096ce; margin: 10px 0; letter-spacing: 2px; }
                .btn { border-radius: 5px; background: #ECF5FC; border: 1px solid #C0DFF4; border-bottom: 3px solid #C0DFF4; font-weight: 800; font-size: 14px; letter-spacing: 1.4px; color: #17284D; text-transform: uppercase; padding: 15px 30px; line-height: 16px; margin: 0px; margin-bottom: 15px; text-decoration: none;}
                .footer { background: #5096ce; padding: 20px; text-align: center; font-size: 12px; color: #fff; }
                .tiered-message { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .tiered-message h4 { margin-top: 0; color: #856404; }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <h1><?php echo sprintf(__('Welcome to %s!', 'wc-viral-loop-referral'), get_bloginfo('name')); ?></h1>
                </div>
                <div class="content">
                    <p><?php _e('Hi there!', 'wc-viral-loop-referral'); ?></p>
                    <p><?php echo sprintf(__('Thanks for joining us through a friend\'s referral%s! We\'re excited to have you as part of our community.', 'wc-viral-loop-referral'), !empty($referrer_email) ? ' (' . $referrer_email . ')' : ''); ?></p>
                    
                    <?php if ($is_tiered) : ?>
                    <div class="tiered-message">
                        <h4><?php _e('🎉 Special Bonus Discount!', 'wc-viral-loop-referral'); ?></h4>
                        <p><?php _e('Your referrer is one of our top ambassadors! As a result, you\'re getting our exclusive enhanced discount of 10% off your first order.', 'wc-viral-loop-referral'); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="coupon-box">
                        <div><?php _e('Your Exclusive Discount Code:', 'wc-viral-loop-referral'); ?></div>
                        <div class="coupon-code"><?php echo esc_html($coupon_code); ?></div>
                        <div><?php echo sprintf(__('Save %s on your first order!', 'wc-viral-loop-referral'), $discount_text); ?></div>
                        <?php if ($is_tiered) : ?>
                        <div style="color: #856404; font-weight: bold; margin-top: 10px;">
                            <?php _e('✨ Enhanced Ambassador Referral Discount ✨', 'wc-viral-loop-referral'); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <p style="text-align: center; margin-bottom: 40px;">
                        <a href="<?php echo esc_url($coupon_url); ?>" class="btn"><?php _e('Shop Now & Apply Coupon', 'wc-viral-loop-referral'); ?></a>
                    </p>
                    
                    <p><strong><?php _e('Important Details:', 'wc-viral-loop-referral'); ?></strong></p>
                    <ul>
                        <li><?php _e('This coupon is exclusively for you and cannot be shared', 'wc-viral-loop-referral'); ?></li>
                        <?php if (!empty($expiry_date)) : ?>
                        <li><?php echo sprintf(__('Valid until: %s', 'wc-viral-loop-referral'), $expiry_date); ?></li>
                        <?php endif; ?>
                        <li><?php _e('Can only be used once', 'wc-viral-loop-referral'); ?></li>
                        <li><?php _e('Cannot be combined with other offers', 'wc-viral-loop-referral'); ?></li>
                    </ul>
                    
                    <p><?php _e('Ready to start shopping? Click the button above or copy the coupon code and paste it at checkout.', 'wc-viral-loop-referral'); ?></p>
                    
                    <p><?php echo sprintf(__('Happy shopping!<br>The %s Team', 'wc-viral-loop-referral'), get_bloginfo('name')); ?></p>
                </div>
                <div class="footer">
                    <p><?php _e('This email was sent because you accepted a referral invitation.', 'wc-viral-loop-referral'); ?></p>
                    <p><?php echo get_bloginfo('name') . ' | ' . home_url(); ?></p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get coupon apply URL
     */
    private function get_coupon_apply_url($coupon_code) {
        //return add_query_arg(array('apply_coupon' => $coupon_code), wc_get_cart_url());
        return home_url('/').'?apply_coupon='.$coupon_code.'&sc-page=referred';
    }
    
    /**
     * Auto-apply coupon from URL
     */
    public function auto_apply_coupon_from_url() {
        if (isset($_GET['apply_coupon']) && !is_admin()) {
            $coupon_code = sanitize_text_field($_GET['apply_coupon']);
            
            if (!WC()->cart->is_empty() && !WC()->cart->has_discount($coupon_code)) {
                WC()->cart->apply_coupon($coupon_code);
                wc_add_notice(sprintf(__('Coupon "%s" has been applied to your cart!', 'wc-viral-loop-referral'), $coupon_code), 'success');
            }
        }
    }
    
    /**
     * AJAX handler for accepting referrals
     */
    public function ajax_accept_referral() {
        $this->handle_referral_acceptance();
    }
    
    /**
     * AJAX handler for getting products for selector
     */
    public function ajax_get_products_for_selector() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_viral_loop_products')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get products
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => 500, // Limit for performance
            'orderby' => 'title',
            'order' => 'ASC'
        );
        
        $products = get_posts($args);
        $product_data = array();
        
        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            if ($product) {
                $product_data[] = array(
                    'id' => $product->get_id(),
                    'name' => $product->get_name(),
                    'price' => $product->get_price_html()
                );
            }
        }
        
        wp_send_json_success($product_data);
    }
    
    /**
     * AJAX handler for sending test emails
     */
    public function ajax_preview_referral_email() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_viral_loop_email_preview')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $email_type = sanitize_text_field($_POST['email_type'] ?? 'regular');
        $test_email = sanitize_email($_POST['test_email'] ?? '');
        
        if (empty($test_email)) {
            wp_send_json_error('Test email address is required');
        }
        
        // Sample data for test email
        $sample_coupon_code = 'REF-TEST' . strtoupper(substr(md5(time()), 0, 6));
        $sample_coupon_url = wc_get_cart_url() . '?apply_coupon=' . $sample_coupon_code;
        $sample_referrer_email = 'john.doe@example.com';
        
        // Generate discount text based on current settings
        $discount_text = $this->settings['discount_type'] === 'percent' 
            ? $this->settings['discount_amount'] . '% off' 
            : wc_price($this->settings['discount_amount']) . ' off';
        
        // For tiered test, use tiered discount
        $is_tiered = ($email_type === 'tiered');
        if ($is_tiered) {
            $discount_text = $this->settings['tiered_discount_amount'] . '% off';
        }
        
        // Generate expiry date
        $expiry_date = '';
        if ($this->settings['expiry_days'] > 0) {
            $expiry_date = date('F j, Y', time() + ($this->settings['expiry_days'] * 24 * 60 * 60));
        }
        
        // Generate email HTML
        $email_html = $this->get_email_template(
            $sample_coupon_code,
            $sample_coupon_url,
            $discount_text,
            $expiry_date,
            $sample_referrer_email,
            $is_tiered
        );
        
        // Prepare email subject with [TEST] prefix
        $subject = '[TEST] ' . $this->settings['email_subject'];
        
        // Set headers for HTML email
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->settings['sender_name'] . ' <' . $this->settings['sender_email'] . '>'
        );
        
        // Send the test email
        $email_sent = wp_mail($test_email, $subject, $email_html, $headers);
        
        if ($email_sent) {
            wp_send_json_success(array(
                'message' => sprintf(__('Test email sent successfully to %s', 'wc-viral-loop-referral'), $test_email),
                'email_type' => $email_type,
                'coupon_code' => $sample_coupon_code
            ));
        } else {
            wp_send_json_error('Failed to send test email. Please check your email settings.');
        }
    }
    
    /**
     * Handle Viral Loop webhook
     */
    public function handle_viral_loop_webhook() {
        // Log the webhook for debugging
        if (WP_DEBUG) {
            error_log('Viral Loop Webhook received: ' . print_r($_POST, true));
        }
        
        // Get raw POST data
        $raw_data = file_get_contents('php://input');
        $webhook_data = json_decode($raw_data, true);
        
        // If JSON decode fails, try $_POST
        if (!$webhook_data) {
            $webhook_data = $_POST;
        }
        
        // Validate webhook data
        if (!$this->validate_viral_loop_webhook($webhook_data)) {
            wp_send_json_error('Invalid webhook data');
            return;
        }
        
        // Check if this is a participation event
        if (!isset($webhook_data['type']) || $webhook_data['type'] !== 'participation') {
            wp_send_json_error('Not a participation event');
            return;
        }
        
        // Extract user data
        $user_data = $webhook_data['user'] ?? array();
        $referrer_data = $webhook_data['referrer'] ?? array();
        
        $new_user_email = $user_data['email'] ?? '';
        $referrer_email = $referrer_data['email'] ?? '';
        $referral_code = $user_data['referralCode'] ?? '';
        
        // Create unique referral token from available data
        $referral_token = $referral_code ?: md5($new_user_email . time());
        
        if (empty($new_user_email)) {
            wp_send_json_error('Missing user email');
            return;
        }
        
        // Check if coupon already exists for this user/referral
        if ($this->coupon_exists_for_user($new_user_email, $referral_token)) {
            wp_send_json_success('Coupon already exists for this user');
            return;
        }
        
        // Create referral coupon
        $coupon_code = $this->create_referral_coupon($referral_token, $referrer_email, $new_user_email);
        
        if ($coupon_code) {
            // Send email with coupon
            $email_sent = $this->send_referral_coupon_email($new_user_email, $coupon_code, $referrer_email);
            
            // Log success
            if (WP_DEBUG) {
                error_log("Viral Loop: Created coupon {$coupon_code} for {$new_user_email}");
            }
            
            wp_send_json_success(array(
                'coupon_code' => $coupon_code,
                'email_sent' => $email_sent,
                'message' => 'Coupon created successfully'
            ));
        } else {
            wp_send_json_error('Failed to create coupon');
        }
    }
    
    /**
     * Validate Viral Loop webhook data
     */
    private function validate_viral_loop_webhook($data) {
        // Basic validation
        if (empty($data) || !is_array($data)) {
            return false;
        }
        
        // Check for required fields
        $required_fields = array('type', 'user');
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                return false;
            }
        }
        
        // Validate user data
        $user_data = $data['user'];
        if (!isset($user_data['email']) || !is_email($user_data['email'])) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if coupon already exists for this user/referral
     */
    private function coupon_exists_for_user($email, $referral_token) {
        $existing_coupons = get_posts(array(
            'post_type' => 'shop_coupon',
            'meta_query' => array(
                array(
                    'key' => '_referee_email',
                    'value' => $email,
                    'compare' => '='
                ),
                array(
                    'key' => '_referral_token',
                    'value' => $referral_token,
                    'compare' => '='
                ),
                array(
                    'key' => '_is_referral_coupon',
                    'value' => 'yes',
                    'compare' => '='
                )
            ),
            'posts_per_page' => 1
        ));
        
        return !empty($existing_coupons);
    }
    
    /**
     * Add frontend script
     */
    public function add_frontend_script() {
        ?>
        <script>
        // Function to handle referral acceptance
        function acceptReferral(referralToken, referrerEmail, newUserEmail) {
            const loadingMessage = document.getElementById('referral-loading');
            if (loadingMessage) {
                loadingMessage.style.display = 'block';
            }
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'accept_referral',
                    referral_token: referralToken,
                    referrer_email: referrerEmail,
                    new_user_email: newUserEmail
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayCouponSuccess(data.data.coupon_code, data.data.coupon_url);
                } else {
                    console.error('Error creating coupon:', data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            })
            .finally(() => {
                if (loadingMessage) {
                    loadingMessage.style.display = 'none';
                }
            });
        }
        
        // Function to display coupon success message
        function displayCouponSuccess(couponCode, couponUrl) {
            const successHtml = `
                <div id="coupon-success" style="background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px; text-align: center; margin: 20px 0;">
                    <h3 style="color: #155724; margin-top: 0;"><?php _e('Welcome! Your exclusive coupon is ready!', 'wc-viral-loop-referral'); ?></h3>
                    <p><?php _e('Your coupon code:', 'wc-viral-loop-referral'); ?> <strong style="font-size: 18px; color: #5096ce;">${couponCode}</strong></p>
                    <p><?php _e("We've sent this code to your email address.", 'wc-viral-loop-referral'); ?></p>
                    <a href="${couponUrl}" style="background: #5096ce; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;"><?php _e('Start Shopping', 'wc-viral-loop-referral'); ?></a>
                </div>
            `;
            
            const container = document.querySelector('.viral-loop-container') || document.body;
            container.insertAdjacentHTML('beforeend', successHtml);
            
            document.getElementById('coupon-success').scrollIntoView({ behavior: 'smooth' });
        }
        
        // Listen for messages from Viral Loop iframe
        window.addEventListener('message', function(event) {
            if (event.origin !== '<?php echo esc_js($this->settings['viral_loop_origin']); ?>') {
                return;
            }
            
            if (event.data.type === 'referral_accepted') {
                acceptReferral(
                    event.data.referralToken,
                    event.data.referrerEmail,
                    event.data.newUserEmail
                );
            }
        });
        </script>
        <?php
    }
    
    /**
     * Referral success shortcode
     */
    public function referral_success_shortcode($atts) {
        if (isset($_GET['referral_success']) && isset($_GET['coupon_code'])) {
            $coupon_code = sanitize_text_field($_GET['coupon_code']);
            $coupon_url = $this->get_coupon_apply_url($coupon_code);
            
            ob_start();
            ?>
            <div class="referral-success-message" style="background: #d4edda; border: 1px solid #c3e6cb; padding: 20px; border-radius: 8px; text-align: center;">
                <h3 style="color: #155724;"><?php _e('Welcome! Your referral was successful!', 'wc-viral-loop-referral'); ?></h3>
                <p><?php _e('Your exclusive coupon code:', 'wc-viral-loop-referral'); ?> <strong style="font-size: 18px; color: #28a745;"><?php echo esc_html($coupon_code); ?></strong></p>
                <p><?php _e("We've also sent this code to your email address.", 'wc-viral-loop-referral'); ?></p>
                <a href="<?php echo esc_url($coupon_url); ?>" class="button" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;"><?php _e('Start Shopping', 'wc-viral-loop-referral'); ?></a>
            </div>
            <?php
            return ob_get_clean();
        }
        return '';
    }
    
    /**
     * Create referral success page
     */
    private function create_referral_success_page() {
        $page_title = __('Referral Success', 'wc-viral-loop-referral');
        $page_content = '[referral_success]<br><br><p>' . __('Thank you for joining our community! Start exploring our products and enjoy your exclusive discount.', 'wc-viral-loop-referral') . '</p>';
        
        $page = get_page_by_path('referral-success');
        
        if (!$page) {
            $page_data = array(
                'post_title'   => $page_title,
                'post_content' => $page_content,
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_name'    => 'referral-success'
            );
            
            wp_insert_post($page_data);
        }
    }
    
    /**
     * Add custom columns to coupons list
     */
    public function add_coupon_columns($columns) {
        $columns['referral_info'] = __('Referral Info', 'wc-viral-loop-referral');
        return $columns;
    }
    
    /**
     * Populate custom coupon columns
     */
    public function populate_coupon_columns($column, $post_id) {
        if ($column === 'referral_info') {
            $is_referral = get_post_meta($post_id, '_is_referral_coupon', true);
            if ($is_referral === 'yes') {
                $referrer_email = get_post_meta($post_id, '_referrer_email', true);
                $referee_email = get_post_meta($post_id, '_referee_email', true);
                $created = get_post_meta($post_id, '_referral_created', true);
                $is_tiered = get_post_meta($post_id, '_is_tiered_referral', true);
                $referral_count = get_post_meta($post_id, '_referrer_referral_count', true);
                
                echo '<strong>' . __('Referral Coupon', 'wc-viral-loop-referral') . '</strong><br>';
                if ($is_tiered === 'yes') {
                    echo '<span style="color: #856404; font-weight: bold;">✨ ' . __('Tiered Referral (10%)', 'wc-viral-loop-referral') . '</span><br>';
                }
                if ($referrer_email) echo __('From:', 'wc-viral-loop-referral') . ' ' . esc_html($referrer_email) . '<br>';
                if ($referee_email) echo __('To:', 'wc-viral-loop-referral') . ' ' . esc_html($referee_email) . '<br>';
                if ($referral_count) echo __('Referrer\'s #:', 'wc-viral-loop-referral') . ' ' . esc_html($referral_count) . '<br>';
                if ($created) echo __('Created:', 'wc-viral-loop-referral') . ' ' . esc_html(date('M j, Y', strtotime($created)));
            }
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Viral Loop Referrals', 'wc-viral-loop-referral'),
            __('Viral Loop Referrals', 'wc-viral-loop-referral'),
            'manage_woocommerce',
            'wc-viral-loop-referral',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Add settings link
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=wc-viral-loop-referral') . '">' . __('Settings', 'wc-viral-loop-referral') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Initialize admin settings
     */
    public function init_admin_settings() {
        register_setting('wc_viral_loop_referral_settings', 'wc_viral_loop_referral_settings');
    }
    
    /**
     * Admin page content
     */
    public function admin_page() {
        if (isset($_POST['submit'])) {
            $settings = array();
            $settings['discount_type'] = sanitize_text_field($_POST['discount_type']);
            $settings['discount_amount'] = floatval($_POST['discount_amount']);
            $settings['expiry_days'] = intval($_POST['expiry_days']);
            $settings['usage_limit'] = intval($_POST['usage_limit']);
            $settings['usage_limit_per_user'] = intval($_POST['usage_limit_per_user']);
            $settings['minimum_amount'] = floatval($_POST['minimum_amount']);
            $settings['free_shipping'] = isset($_POST['free_shipping']);
            $settings['individual_use'] = isset($_POST['individual_use']);
            $settings['email_subject'] = sanitize_text_field($_POST['email_subject']);
            $settings['sender_name'] = sanitize_text_field($_POST['sender_name']);
            $settings['sender_email'] = sanitize_email($_POST['sender_email']);
            $settings['viral_loop_origin'] = esc_url_raw($_POST['viral_loop_origin']);
            $settings['product_ids'] = array_map('intval', array_filter(explode(',', $_POST['product_ids'])));
            $settings['exclude_product_ids'] = array_map('intval', array_filter(explode(',', $_POST['exclude_product_ids'])));
            $settings['product_categories'] = array_map('intval', array_filter(explode(',', $_POST['product_categories'])));
            $settings['exclude_product_categories'] = array_map('intval', array_filter(explode(',', $_POST['exclude_product_categories'])));
            $settings['exclude_sale_items'] = isset($_POST['exclude_sale_items']);
            $settings['enable_tiered_referrals'] = isset($_POST['enable_tiered_referrals']);
            $settings['tiered_threshold'] = intval($_POST['tiered_threshold']);
            $settings['tiered_discount_amount'] = floatval($_POST['tiered_discount_amount']);
            
            update_option('wc_viral_loop_referral_settings', $settings);
            $this->settings = $settings;
            
            echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'wc-viral-loop-referral') . '</p></div>';
        }
        
        // Get referral coupons
        $referral_coupons = get_posts(array(
            'post_type' => 'shop_coupon',
            'meta_query' => array(
                array(
                    'key' => '_is_referral_coupon',
                    'value' => 'yes'
                )
            ),
            'posts_per_page' => -1
        ));
        
        include_once plugin_dir_path(__FILE__) . 'admin-page.php';
    }
}

// Initialize the plugin
WC_Viral_Loop_Referral::get_instance();
?>