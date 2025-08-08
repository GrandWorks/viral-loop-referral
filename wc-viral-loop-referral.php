<?php
/**
 * Plugin Name: WooCommerce Viral Loop Referral
 * Plugin URI: https://github.com/wazidshah/woocommerce-viral-loop-referral
 * Description: Integrates Viral Loop referral system with WooCommerce to automatically generate and distribute unique coupon codes when referrals are accepted.
 * Version: 1.1.0
 * Author: Wazid Shah
 * Author URI: https://github.com/wazidshah
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
define('WC_VIRAL_LOOP_REFERRAL_VERSION', '1.1.0');
define('WC_VIRAL_LOOP_REFERRAL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_VIRAL_LOOP_REFERRAL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_VIRAL_LOOP_REFERRAL_PLUGIN_FILE', __FILE__);

/**
 * Plugin Update Checker
 */
class WC_Viral_Loop_Referral_Updater {
    
    private $plugin_file;
    private $plugin_slug;
    private $version;
    private $github_username;
    private $github_repo;
    private $update_path;
    
    public function __construct($plugin_file, $github_username, $github_repo) {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->version = WC_VIRAL_LOOP_REFERRAL_VERSION;
        $this->github_username = $github_username;
        $this->github_repo = $github_repo;
        $this->update_path = 'https://api.github.com/repos/' . $github_username . '/' . $github_repo . '/releases/latest';
        
        add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_update'));
        add_filter('plugins_api', array($this, 'plugin_api_call'), 10, 3);
        add_filter('upgrader_source_selection', array($this, 'upgrader_source_selection'), 10, 3);
        add_action('upgrader_process_complete', array($this, 'upgrader_process_complete'), 10, 2);
    }
    
    /**
     * Check for plugin updates
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Get remote version
        $remote_version = $this->get_remote_version();
        
        if ($remote_version && version_compare($this->version, $remote_version, '<')) {
            $transient->response[$this->plugin_slug] = (object) array(
                'slug' => $this->plugin_slug,
                'new_version' => $remote_version,
                'url' => $this->get_github_repo_url(),
                'package' => $this->get_download_url($remote_version)
            );
        }
        
        return $transient;
    }
    
    /**
     * Get remote version from GitHub
     */
    private function get_remote_version() {
        $request = wp_remote_get($this->update_path);
        
        if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
            $body = wp_remote_retrieve_body($request);
            $data = json_decode($body, true);
            
            if (isset($data['tag_name'])) {
                return ltrim($data['tag_name'], 'v');
            }
        }
        
        return false;
    }
    
    /**
     * Get GitHub repository URL
     */
    private function get_github_repo_url() {
        return 'https://github.com/' . $this->github_username . '/' . $this->github_repo;
    }
    
    /**
     * Get download URL for specific version
     */
    private function get_download_url($version) {
        return 'https://github.com/' . $this->github_username . '/' . $this->github_repo . '/archive/v' . $version . '.zip';
    }
    
    /**
     * Handle plugin API calls
     */
    public function plugin_api_call($result, $action, $args) {
        if ($action !== 'plugin_information' || $args->slug !== $this->plugin_slug) {
            return $result;
        }
        
        $remote_version = $this->get_remote_version();
        
        if ($remote_version) {
            $result = (object) array(
                'name' => 'WooCommerce Viral Loop Referral',
                'slug' => $this->plugin_slug,
                'version' => $remote_version,
                'author' => 'Wazid Shah',
                'homepage' => $this->get_github_repo_url(),
                'short_description' => 'Integrates Viral Loop referral system with WooCommerce',
                'sections' => array(
                    'description' => 'Integrates Viral Loop referral system with WooCommerce to automatically generate and distribute unique coupon codes when referrals are accepted.',
                    'changelog' => 'For changelog, please visit: ' . $this->get_github_repo_url() . '/releases'
                ),
                'download_link' => $this->get_download_url($remote_version),
                'last_updated' => date('Y-m-d'),
                'requires' => '5.0',
                'tested' => '6.6',
                'requires_php' => '7.4'
            );
        }
        
        return $result;
    }
    
    /**
     * Handle upgrader source selection
     */
    public function upgrader_source_selection($source, $remote_source, $upgrader) {
        if (isset($upgrader->skin->plugin_info) && $upgrader->skin->plugin_info['Name'] === 'WooCommerce Viral Loop Referral') {
            $corrected_source = $remote_source . '/' . $this->github_repo . '-' . ltrim($this->get_remote_version(), 'v') . '/';
            
            if (is_dir($corrected_source)) {
                return $corrected_source;
            }
        }
        
        return $source;
    }
    
    /**
     * Handle upgrade process completion
     */
    public function upgrader_process_complete($upgrader_object, $options) {
        if ($options['action'] === 'update' && $options['type'] === 'plugin') {
            delete_transient('wc_viral_loop_referral_update_check');
        }
    }
}

/**
 * Main Plugin Class
 * 
 * REFERRAL FLOW EXPLANATION:
 * 1. REFERRER creates a referral link/code in Viral Loop
 * 2. REFEREE clicks the link and joins/signs up
 * 3. Viral Loop sends webhook to this plugin (participation event)
 * 4. Plugin creates a coupon ONLY for the REFEREE
 * 5. Plugin sends welcome email with coupon ONLY to the REFEREE
 * 6. REFERRER's successful referral count is incremented for tiered rewards
 * 
 * IMPORTANT: 
 * - Only REFEREES get coupons and emails
 * - REFERRERS never get coupons for their own referrals
 * - Only successful referrals (where referee gets coupon) count toward thresholds
 * - Users who join directly (no referrer) get NO coupons or emails
 * 
 * SCENARIOS:
 * 1. User joins directly → NO email, NO coupon ✅
 * 2. User joins via referrer → Email to REFEREE only, coupon for REFEREE only ✅  
 * 3. User tries to refer themselves → Blocked, NO email, NO coupon ✅
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
            'tiered_discount_amount' => 10,
            'enable_custom_coupon_mode' => false,
            'custom_coupon_code' => ''
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
        add_action('wp_ajax_check_plugin_updates', array($this, 'ajax_check_plugin_updates'));
        add_action('wp_ajax_simulate_viral_loop_webhook', array($this, 'ajax_simulate_webhook'));
        
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
     * Handle referral acceptance (manual/direct method)
     */
    public function handle_referral_acceptance() {
        // Check if this is a referral acceptance request
        if (isset($_GET['accept_referral']) || (isset($_POST['action']) && $_POST['action'] === 'accept_referral')) {
            
            // Get referral data
            $referral_token = sanitize_text_field($_GET['referral_token'] ?? $_POST['referral_token'] ?? '');
            $referrer_email = sanitize_email($_GET['referrer_email'] ?? $_POST['referrer_email'] ?? '');  // REFERRER (person who invited)
            $new_user_email = sanitize_email($_GET['new_user_email'] ?? $_POST['new_user_email'] ?? '');  // REFEREE (person accepting referral)
            
            if (!empty($referral_token) && !empty($new_user_email)) {
                
                // CRITICAL: Only process if there's an actual referrer (not direct signups)
                if (empty($referrer_email)) {
                    if (WP_DEBUG) {
                        error_log("Manual Referral: No referrer found - cannot process referral for: {$new_user_email}");
                    }
                    if (wp_doing_ajax()) {
                        wp_send_json_error(array('message' => __('No referrer found - this is not a valid referral.', 'wc-viral-loop-referral')));
                    }
                    return;
                }
                
                // IMPORTANT: Prevent referrers from referring themselves
                if ($referrer_email === $new_user_email) {
                    if (WP_DEBUG) {
                        error_log("Manual Referral: Self-referral attempt blocked: {$new_user_email}");
                    }
                    if (wp_doing_ajax()) {
                        wp_send_json_error(array('message' => __('You cannot refer yourself.', 'wc-viral-loop-referral')));
                    }
                    return;
                }
                
                // Skip duplicate check for custom coupon mode (custom coupons can be reused)
                if (!$this->settings['enable_custom_coupon_mode']) {
                    // Check if coupon already exists for this referee
                    if ($this->coupon_exists_for_user($new_user_email, $referral_token)) {
                        if (wp_doing_ajax()) {
                            wp_send_json_error(array('message' => __('You already have a coupon for this referral.', 'wc-viral-loop-referral')));
                        }
                        return;
                    }
                }
                
                // Create referral coupon FOR THE REFEREE ONLY
                $coupon_code = $this->create_referral_coupon($referral_token, $referrer_email, $new_user_email);
                
                if ($coupon_code) {
                    // Send email with coupon TO THE REFEREE ONLY (not to referrer)
                    $this->send_referral_coupon_email($new_user_email, $coupon_code, $referrer_email);
                    
                    // Log for debugging
                    if (WP_DEBUG) {
                        error_log("Manual Referral: Created coupon {$coupon_code} for REFEREE {$new_user_email} (referred by {$referrer_email})");
                    }
                    
                    // Return JSON response for AJAX requests
                    if (wp_doing_ajax() || (isset($_POST['action']) && $_POST['action'] === 'accept_referral')) {
                        wp_send_json_success(array(
                            'coupon_code' => $coupon_code,
                            'message' => __('Welcome! Your exclusive coupon code has been created.', 'wc-viral-loop-referral'),
                            'coupon_url' => $this->get_coupon_apply_url($coupon_code),
                            'referee_email' => $new_user_email
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
        // Check if custom coupon mode is enabled
        if ($this->settings['enable_custom_coupon_mode']) {
            // Use custom coupon code if provided
            if (!empty($this->settings['custom_coupon_code'])) {
                $coupon_code = sanitize_text_field($this->settings['custom_coupon_code']);
                
                // Check if the custom coupon code already exists as a WooCommerce coupon
                $existing_coupon_id = wc_get_coupon_id_by_code($coupon_code);
                if ($existing_coupon_id) {
                    // Use existing coupon instead of creating a new one
                    return $coupon_code;
                } else {
                    // Custom coupon code doesn't exist as a WooCommerce coupon
                    // We'll create it with the specified settings but skip most automated settings
                    // since this is a manually managed coupon
                }
            } else {
                // Custom mode enabled but no code provided
                return false;
            }
        } else {
            // Generate unique coupon code (lowercase) - original behavior
            $coupon_code = 'ref-' . strtolower(substr(md5($referral_token . $new_user_email . time()), 0, 8));
            
            // Check if coupon already exists
            if (wc_get_coupon_id_by_code($coupon_code)) {
                $coupon_code = 'ref-' . strtolower(substr(md5($referral_token . $new_user_email . time() . rand()), 0, 8));
            }
        }
        
        // Skip automated coupon creation in custom mode
        if ($this->settings['enable_custom_coupon_mode']) {
            // For custom coupon mode, we simply create a basic coupon with the custom code
            // No tiered logic or complex settings - just create the coupon
            $coupon = new WC_Coupon();
            $coupon->set_code($coupon_code);
            $coupon->set_description(__('Custom referral coupon', 'wc-viral-loop-referral'));
            
            // Apply basic settings but skip complex logic
            $coupon->set_discount_type('percent');
            $coupon->set_amount(0); // 0% discount since this is a custom code
            $coupon->set_individual_use(true);
            $coupon->set_usage_limit(0); // Unlimited usage for custom code
            $coupon->set_usage_limit_per_user(0); // Unlimited per user for custom code
            
            // Save the coupon
            $coupon_id = $coupon->save();
        } else {
            // Original automated coupon creation logic
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
            // $coupon->set_email_restrictions(array($new_user_email));
            
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
        }
        
        if ($coupon_id) {
            // Store referral metadata
            update_post_meta($coupon_id, '_referral_token', $referral_token);
            update_post_meta($coupon_id, '_referrer_email', $referrer_email);
            update_post_meta($coupon_id, '_referee_email', $new_user_email);
            update_post_meta($coupon_id, '_referral_created', current_time('mysql'));
            update_post_meta($coupon_id, '_is_referral_coupon', 'yes');
            
            // Only set these metadata for non-custom coupon mode
            if (!$this->settings['enable_custom_coupon_mode']) {
                update_post_meta($coupon_id, '_referrer_referral_count', $previous_referrals + 1); // Track this referrer's total count
                update_post_meta($coupon_id, '_is_tiered_referral', ($this->settings['enable_tiered_referrals'] && $previous_referrals >= $this->settings['tiered_threshold']) ? 'yes' : 'no'); // Mark if this used tiered discount
            } else {
                update_post_meta($coupon_id, '_is_custom_coupon', 'yes'); // Mark as custom coupon
            }
            
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
     * Send referral coupon email TO THE REFEREE ONLY
     * 
     * @param string $recipient_email - REFEREE's email (person who joined via referral)
     * @param string $coupon_code - Unique coupon code for the referee
     * @param string $referrer_email - REFERRER's email (person who made the referral) - used for email content only
     */
    private function send_referral_coupon_email($recipient_email, $coupon_code, $referrer_email = '') {
        $coupon_url = $this->get_coupon_apply_url($coupon_code);
        $expiry_date = '';
        
        // Check if we're in custom coupon mode
        if ($this->settings['enable_custom_coupon_mode']) {
            // For custom coupons, we don't set expiry or complex logic
            $discount_text = __('your special discount', 'wc-viral-loop-referral'); // Generic text since discount is managed manually
            $is_tiered = false; // Custom coupons are not tiered
        } else {
            // Original logic for auto-created coupons
            if ($this->settings['expiry_days'] > 0) {
                $expiry_date = date('F j, Y', time() + ($this->settings['expiry_days'] * 24 * 60 * 60));
            }
            
            // Get the actual coupon to determine its discount
            $coupon_id = wc_get_coupon_id_by_code($coupon_code);
            $coupon = new WC_Coupon($coupon_id);
            $is_tiered = get_post_meta($coupon_id, '_is_tiered_referral', true) === 'yes';
            
            // Use actual coupon values for discount text
            $discount_text = $coupon->get_discount_type() === 'percent' 
                ? $coupon->get_amount() . '%' 
                : wc_price($coupon->get_amount()). ' kr';
        }
        
        // HTML email template - use custom template for custom coupon mode
        if ($this->settings['enable_custom_coupon_mode']) {
            $message = $this->get_custom_coupon_email_template($coupon_code, $coupon_url, $discount_text, $referrer_email);
        } else {
            $message = $this->get_email_template($coupon_code, $coupon_url, $discount_text, $expiry_date, $referrer_email, $is_tiered);
        }
        
        // Set headers for HTML email
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->settings['sender_name'] . ' <' . $this->settings['sender_email'] . '>'
        );
        
        // Send the email
        return wp_mail($recipient_email, $this->settings['email_subject'], $message, $headers);
    }
    
    /**
     * Send notification email to referrer (NO COUPON - just thank you message)
     * This function is for future use if you want to thank referrers
     * 
     * @param string $referrer_email - REFERRER's email (person who made the referral)
     * @param string $referee_email - REFEREE's email (person who joined) 
     * @param int $referral_count - How many successful referrals this referrer now has
     */
    private function send_referrer_thank_you_email($referrer_email, $referee_email, $referral_count) {
        // This function is intentionally not called anywhere yet
        // It's here for future use if you want to notify referrers (without giving them coupons)
        
        if (empty($referrer_email) || !is_email($referrer_email)) {
            return false;
        }
        
        $subject = sprintf(__('Thank you! Your referral was successful', 'wc-viral-loop-referral'));
        $message = sprintf(
            __('Hi there!<br><br>Great news! Someone joined through your referral link.<br><br>This is your %d successful referral. Keep sharing!<br><br>Thanks for being an awesome ambassador!<br><br>The %s Team', 'wc-viral-loop-referral'),
            $referral_count,
            get_bloginfo('name')
        );
        
        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->settings['sender_name'] . ' <' . $this->settings['sender_email'] . '>'
        );
        
        // NOTE: This email contains NO COUPON - it's just a thank you
        return wp_mail($referrer_email, $subject, $message, $headers);
    }
    
    /**
     * Get email template
     */
    private function get_email_template($coupon_code, $coupon_url, $discount_text, $expiry_date, $referrer_email, $is_tiered = false) {
        $is_custom_mode = $this->settings['enable_custom_coupon_mode'];
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($this->settings['email_subject']); ?></title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0;">
            <table role="presentation" style="width: 100%; padding: 20px 0;">
                <tr>
                    <td align="center">
                        <table role="presentation" style="max-width: 600px; margin: 0 auto; background: #EEF5FB; border-radius: 8px; overflow: hidden;">
                            <!-- Header -->
                            <tr>
                                <td style="background-color: #EEF5FB; padding: 30px 20px 0px 20px; text-align: center;">
                                    <img src="https://traningsmat.se/wp-content/uploads/2025/06/t-logo.png" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" style="max-width: 150px; height: auto; margin-bottom: 20px;">
                                    <h1 style="margin: 0; font-size: 24px; color: #17284D;">Välkommen till Träningsmat!</h1>
                                </td>
                            </tr>
                            
                            <!-- Content -->
                            <tr>
                                <td style="padding: 30px; background-color: #EEF5FB;">
                                    <p style="color: #17284D; margin: 0 0 15px 0;"><?php _e('Hej!', 'wc-viral-loop-referral'); ?></p>
                                    <p style="color: #17284D; margin: 0 0 20px 0;"><?php echo sprintf(__('Tack för att du gick med via din väns rekommendation %s! Vi är glada att ha dig som en del av Träningsmat-gänget!', 'wc-viral-loop-referral'), !empty($referrer_email) ? ' (' . $referrer_email . ')' : ''); ?></p>
                                    
                                    
                                    
                                    <div style="background: #f8f9fa; border: 2px dashed #5096ce; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px;">
                                        <div style="color: #17284D; margin-bottom: 10px;"><?php _e('Din unika rabattkod:', 'wc-viral-loop-referral'); ?></div>
                                        <div style="font-size: 24px; font-weight: bold; color: #17284D; margin: 10px 0; letter-spacing: 2px;"><?php echo esc_html($coupon_code); ?></div>
                                        <?php if ($is_custom_mode) : ?>
                                            <div style="color: #17284D; margin-bottom: 15px;"><?php _e('Använd denna kod vid kassan för att få din speciella rabatt!', 'wc-viral-loop-referral'); ?></div>
                                        <?php elseif ($is_tiered==false) : ?>
                                            <div style="color: #17284D; margin-bottom: 15px;"><?php echo sprintf(__('Handla nu och använd rabattkoden för att få 5 gratis matlådor (värde %s)! Du betalar endast för leveransen.', 'wc-viral-loop-referral'), $discount_text); ?></div>
                                        <?php else : ?>
                                            <div style="color: #17284D; margin-bottom: 15px;"><?php echo sprintf(__('Spara %s på din första beställning!', 'wc-viral-loop-referral'), $discount_text); ?></div>
                                        <?php endif; ?>
                                        
                                        <div style="text-align: center; margin-top: 20px;">
                                            <a href="<?php echo esc_url($coupon_url); ?>" style="display: inline-block; border-radius: 5px; background: #5096ce; border: 1px solid #5096ce; border-bottom: 3px solid #327bb5; font-weight: 800; font-size: 14px; letter-spacing: 1.4px; color: #fff; text-transform: uppercase; padding: 15px 30px; line-height: 16px; margin: 0; margin-bottom: 15px; text-decoration: none;"><?php _e('Handla nu och använd rabattkoden', 'wc-viral-loop-referral'); ?></a>
                                        </div>
                                    </div>
                                    
                                    <p style="color: #17284D; margin: 20px 0 10px 0;"><strong><?php _e('Viktigt att veta:', 'wc-viral-loop-referral'); ?></strong></p>
                                    <?php if ($is_custom_mode) : ?>
                                        <ul style="margin: 0 0 20px 0; padding-left: 20px;">
                                            <li style="color: #17284D; margin-bottom: 5px;"><?php _e('Denna rabattkod är exklusiv för dig och får inte delas', 'wc-viral-loop-referral'); ?></li>
                                            <li style="color: #17284D; margin-bottom: 5px;"><?php _e('Använd koden vid kassan för att få din rabatt', 'wc-viral-loop-referral'); ?></li>
                                            <li style="color: #17284D; margin-bottom: 5px;"><?php _e('Se villkor och begränsningar på vår hemsida', 'wc-viral-loop-referral'); ?></li>
                                        </ul>
                                    <?php elseif($is_tiered==false) : ?>
                                        <ul style="margin: 0 0 20px 0; padding-left: 20px;">
                                            <li style="color: #17284D; margin-bottom: 5px;"><?php _e('Denna rabattkod är exklusiv för dig och får inte delas', 'wc-viral-loop-referral'); ?></li>
                                            <?php if (!empty($expiry_date)) : ?>
                                            <li style="color: #17284D; margin-bottom: 5px;"><?php echo sprintf(__('Giltig till: %s', 'wc-viral-loop-referral'), $expiry_date); ?></li>
                                            <?php endif; ?>
                                            <li style="color: #17284D; margin-bottom: 5px;"><?php _e('Kan endast användas en gång', 'wc-viral-loop-referral'); ?></li>
                                            <li style="color: #17284D; margin-bottom: 5px;"><?php _e('Kan inte kombineras med andra erbjudanden', 'wc-viral-loop-referral'); ?></li>
                                            <li style="color: #17284D; margin-bottom: 5px;"><?php _e('Du betalar endast för hemleverans med kylbil.', 'wc-viral-loop-referral'); ?></li>
                                            <li style="color: #17284D; margin-bottom: 5px;"><?php _e('Du kan när som helst avsluta din prenumeration för att inte få fler beställningar. Sista tillfälle att avsluta för att inte få en ytterligare beställning är söndag veckan innan leverans, kl. 23:59.', 'wc-viral-loop-referral'); ?></li>
                                        </ul>
                                    <?php else : ?>
                                        <ul style="margin: 0 0 20px 0; padding-left: 20px;">
                                            <li style="color: #17284D; margin-bottom: 5px;"><?php _e('Denna rabattkod är exklusiv för dig och får inte delas', 'wc-viral-loop-referral'); ?></li>
                                            <?php if (!empty($expiry_date)) : ?>
                                            <li style="color: #17284D; margin-bottom: 5px;"><?php echo sprintf(__('Giltig till: %s', 'wc-viral-loop-referral'), $expiry_date); ?></li>
                                            <?php endif; ?>
                                            <li style="color: #17284D; margin-bottom: 5px;"><?php _e('Kan endast användas en gång', 'wc-viral-loop-referral'); ?></li>
                                            <li style="color: #17284D; margin-bottom: 5px;"><?php _e('Kan inte kombineras med andra erbjudanden', 'wc-viral-loop-referral'); ?></li>
                                            
                                        </ul>
                                    <?php endif; ?>       
                                    <p style="color: #17284D; margin: 0 0 15px 0;"><?php _e('Redo att börja handla? Klicka på knappen ovan eller kopiera rabattkoden och klistra in den i kassan.', 'wc-viral-loop-referral'); ?></p>
                                    
                                    <p style="color: #17284D; margin: 0;"><?php echo sprintf(__('Vi ser fram emot att leverera till dig.', 'wc-viral-loop-referral'), get_bloginfo('name')); ?></p>
                                    <p style="color: #17284D; margin: 0; font-weight: bold;"><?php echo sprintf(__('Träningsmat-gänget', 'wc-viral-loop-referral'), get_bloginfo('name')); ?></p>
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td style="background: #000; padding: 20px; text-align: center;">
                                    <img src="https://traningsmat.se/wp-content/uploads/2025/06/Traningsmat-email.png" style="width: 100px; height: auto; margin-bottom: 15px;" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
                                    <p style="color: #fff; margin: 0 0 5px 0; font-size: 12px;"><?php _e('Du har fått detta mejl eftersom du accepterat en inbjudan.', 'wc-viral-loop-referral'); ?></p>
                                    <p style="color: #fff; margin: 0; font-size: 12px;"><?php echo get_bloginfo('name') . ' | ' . home_url(); ?></p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get custom coupon email template (separate from regular template)
     */
    private function get_custom_coupon_email_template($coupon_code, $coupon_url, $discount_text, $referrer_email = '') {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($this->settings['email_subject']); ?></title>
        </head>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0;">
            <table role="presentation" style="width: 100%; padding: 20px 0;">
                <tr>
                    <td align="center">
                        <table role="presentation" style="max-width: 600px; margin: 0 auto; background: #EEF5FB; border-radius: 8px; overflow: hidden;">
                            <!-- Header -->
                            <tr>
                                <td style="background-color: #EEF5FB; padding: 30px 20px 0px 20px; text-align: center;">
                                    <img src="https://traningsmat.se/wp-content/uploads/2025/06/t-logo.png" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" style="max-width: 150px; height: auto; margin-bottom: 20px;">
                                    <h1 style="margin: 0; font-size: 24px; color: #17284D;">Välkommen till Träningsmat!</h1>
                                </td>
                            </tr>
                            
                            <!-- Content -->
                            <tr>
                                <td style="padding: 30px; background-color: #EEF5FB;">
                                    <p style="color: #17284D; margin: 0 0 15px 0;"><?php _e('Hej!', 'wc-viral-loop-referral'); ?></p>
                                    <p style="color: #17284D; margin: 0 0 20px 0;"><?php echo sprintf(__('Tack för att du gick med via din väns rekommendation %s! Vi är glada att ha dig som en del av Träningsmat-gänget!', 'wc-viral-loop-referral'), !empty($referrer_email) ? ' (' . $referrer_email . ')' : ''); ?></p>
                                    
                                    <!-- Custom Coupon Section -->
                                    <div style="background: #f8f9fa; border: 2px dashed #5096ce; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px;">
                                        <div style="color: #17284D; margin-bottom: 10px;"><?php _e('Din unika rabattkod:', 'wc-viral-loop-referral'); ?></div>
                                        <div style="font-size: 24px; font-weight: bold; color: #17284D; margin: 10px 0; letter-spacing: 2px;"><?php echo esc_html($coupon_code); ?></div>
                                        <div style="color: #17284D; margin-bottom: 5px;"><?php _e('Med denna rabattkod får du som ny kund en gratis påse Träningsmat Proplus-proteinpulver 900g med din första leverans.', 'wc-viral-loop-referral'); ?></div>
                                        <div style="color: #17284D; margin-bottom: 15px;"><?php _e('Därefter får du en ny påse med proteinpulver gratis var fjärde leverans och får totalt 12 leveranser med proteinpulver så länge du fortsätter prenumerera.', 'wc-viral-loop-referral'); ?></div>
                                        
                                        <div style="text-align: center; margin-top: 20px;">
                                            <a href="<?php echo esc_url($coupon_url); ?>" style="display: inline-block; border-radius: 5px; background: #5096ce; border: 1px solid #5096ce; border-bottom: 3px solid #327bb5; font-weight: 800; font-size: 14px; letter-spacing: 1.4px; color: #fff; text-transform: uppercase; padding: 15px 30px; line-height: 16px; margin: 0; margin-bottom: 15px; text-decoration: none;"><?php _e('Handla nu med din rabattkod', 'wc-viral-loop-referral'); ?></a>
                                        </div>
                                    </div>
                                    
                                    <p style="color: #17284D; margin: 20px 0 10px 0;"><strong><?php _e('Om din rabattkod:', 'wc-viral-loop-referral'); ?></strong></p>
                                    <ul style="margin: 0 0 20px 0; padding-left: 20px;">
                                        <li style="color: #17284D; margin-bottom: 5px;"><?php _e('Denna rabattkod är exklusiv för dig och får inte delas', 'wc-viral-loop-referral'); ?></li>
                                        <li style="color: #17284D; margin-bottom: 5px;"><?php _e('Använd koden vid kassan för att få din rabatt', 'wc-viral-loop-referral'); ?></li>
                                        <li style="color: #17284D; margin-bottom: 5px;"><?php _e('Se villkor och begränsningar nedan eller på vår hemsida', 'wc-viral-loop-referral'); ?></li>
                                    </ul>
                                    
                                    <p style="color: #17284D; margin: 0 0 15px 0;"><?php _e('Redo att börja handla? Klicka på knappen ovan eller kopiera rabattkoden och klistra in den i kassan.', 'wc-viral-loop-referral'); ?></p>
                                    
                                    <p style="color: #17284D; margin: 0;"><?php echo sprintf(__('Vi ser fram emot att leverera till dig.', 'wc-viral-loop-referral'), get_bloginfo('name')); ?></p>
                                    <p style="color: #17284D; margin: 0; font-weight: bold;"><?php echo sprintf(__('Träningsmat-gänget', 'wc-viral-loop-referral'), get_bloginfo('name')); ?></p>
                                </td>
                            </tr>
                            
                            <!-- Footer -->
                            <tr>
                                <td style="background: #000; padding: 20px; text-align: center;">
                                    <img src="https://traningsmat.se/wp-content/uploads/2025/06/Traningsmat-email.png" style="width: 100px; height: auto; margin-bottom: 15px;" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
                                    <p style="color: #fff; margin: 0 0 5px 0; font-size: 12px;"><?php _e('Du har fått detta mejl eftersom du accepterat en inbjudan.', 'wc-viral-loop-referral'); ?></p>
                                    <p style="color: #fff; margin: 0; font-size: 12px;"><?php echo get_bloginfo('name') . ' | ' . home_url(); ?></p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get coupon apply URL
     */
    private function get_coupon_apply_url($coupon_code) {
        //return add_query_arg(array('coupon-code' => $coupon_code), wc_get_cart_url());
        
        // Use different URL format for custom coupons vs regular coupons
        if ($this->settings['enable_custom_coupon_mode']) {
            return home_url('/').'?tm-coupon='.$coupon_code.'&tm-page=referred';
        } else {
            return home_url('/').'?coupon-code='.$coupon_code.'&sc-page=referred';
        }
    }
    
    /**
     * Auto-apply coupon from URL
     */
    public function auto_apply_coupon_from_url() {
        if (!is_admin()) {
            $coupon_code = '';
            
            // Check for custom coupon URL format
            if (isset($_GET['tm-coupon'])) {
                $coupon_code = sanitize_text_field($_GET['tm-coupon']);
            }
            // Check for regular coupon URL format (backward compatibility)
            elseif (isset($_GET['coupon-code'])) {
                $coupon_code = sanitize_text_field($_GET['coupon-code']);
            }
            
            if (!empty($coupon_code) && !WC()->cart->is_empty() && !WC()->cart->has_discount($coupon_code)) {
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
        $sample_coupon_code = 'ref-test' . strtolower(substr(md5(time()), 0, 6));
        
        // Generate URL based on email type
        if ($email_type === 'custom') {
            $sample_coupon_url = home_url('/') . '?tm-coupon=' . $sample_coupon_code . '&tm-page=referred';
        } else {
            $sample_coupon_url = home_url('/') . '?coupon-code=' . $sample_coupon_code . '&sc-page=referred';
        }
        
        $sample_referrer_email = 'john.doe@example.com';
        
        // Generate discount text based on current settings and email type
        if ($email_type === 'custom') {
            $discount_text = __('your special discount', 'wc-viral-loop-referral');
            $is_tiered = false;
        } else {
            $discount_text = $this->settings['discount_type'] === 'percent' 
                ? $this->settings['discount_amount'] . '%' 
                : wc_price($this->settings['discount_amount']);
            
            // For tiered test, use tiered discount
            $is_tiered = ($email_type === 'tiered');
            if ($is_tiered) {
                $discount_text = $this->settings['tiered_discount_amount'] . '% ';
            }
        }
        
        // Generate expiry date
        $expiry_date = '';
        if ($this->settings['expiry_days'] > 0) {
            $expiry_date = date('F j, Y', time() + ($this->settings['expiry_days'] * 24 * 60 * 60));
        }
        
        // Temporarily override custom mode setting for test emails
        $original_custom_mode = $this->settings['enable_custom_coupon_mode'];
        if ($email_type === 'custom') {
            $this->settings['enable_custom_coupon_mode'] = true;
        } else {
            $this->settings['enable_custom_coupon_mode'] = false;
        }
        
        // Generate email HTML using appropriate template
        if ($email_type === 'custom') {
            $email_html = $this->get_custom_coupon_email_template(
                $sample_coupon_code,
                $sample_coupon_url,
                $discount_text,
                $sample_referrer_email
            );
        } else {
            $email_html = $this->get_email_template(
                $sample_coupon_code,
                $sample_coupon_url,
                $discount_text,
                $expiry_date,
                $sample_referrer_email,
                $is_tiered
            );
        }
        
        // Restore original setting
        $this->settings['enable_custom_coupon_mode'] = $original_custom_mode;
        
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
     * AJAX handler for simulating webhook calls (admin only)
     */
    public function ajax_simulate_webhook() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_viral_loop_webhook_test')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get test data from POST
        $referrer_email = sanitize_email($_POST['referrer_email'] ?? '');
        $referee_email = sanitize_email($_POST['referee_email'] ?? '');
        $referral_code = sanitize_text_field($_POST['referral_code'] ?? '');
        
        // Validate input
        if (empty($referrer_email) || empty($referee_email) || empty($referral_code)) {
            wp_send_json_error('Missing required fields');
        }
        
        if (!is_email($referrer_email) || !is_email($referee_email)) {
            wp_send_json_error('Invalid email addresses');
        }
        
        if ($referrer_email === $referee_email) {
            wp_send_json_error('Referrer and referee cannot be the same (self-referral blocked)');
        }
        
        // Create simulated webhook data that matches Viral Loop's format
        $simulated_webhook_data = array(
            'type' => 'participation',
            'user' => array(
                'email' => $referee_email,
                'referralCode' => $referral_code,
                'id' => 'sim_' . uniqid(),
                'name' => 'Test User'
            ),
            'referrer' => array(
                'email' => $referrer_email,
                'id' => 'sim_ref_' . uniqid(),
                'name' => 'Test Referrer'
            ),
            'campaign' => array(
                'id' => 'test_campaign',
                'name' => 'Test Campaign'
            ),
            'timestamp' => time(),
            'source' => 'admin_simulation'
        );
        
        // Use the existing webhook validation
        if (!$this->validate_viral_loop_webhook($simulated_webhook_data)) {
            wp_send_json_error('Simulated webhook data failed validation');
        }
        
        // Process the simulated webhook using existing logic
        $webhook_data = $simulated_webhook_data;
        
        // Extract user data (same as in handle_viral_loop_webhook)
        $user_data = $webhook_data['user'] ?? array();
        $referrer_data = $webhook_data['referrer'] ?? array();
        
        $new_user_email = $user_data['email'] ?? '';
        $referrer_email_check = $referrer_data['email'] ?? '';
        $referral_code_check = $user_data['referralCode'] ?? '';
        
        // Create unique referral token from available data
        $referral_token = $referral_code_check ?: md5($new_user_email . time());
        
        // Skip duplicate check for custom coupon mode (custom coupons can be reused)
        if (!$this->settings['enable_custom_coupon_mode']) {
            // Check if coupon already exists for this referee/referral
            if ($this->coupon_exists_for_user($new_user_email, $referral_token)) {
                wp_send_json_error('Coupon already exists for this referee with this referral code');
            }
        }
        
        // Create referral coupon FOR THE REFEREE ONLY
        $coupon_code = $this->create_referral_coupon($referral_token, $referrer_email_check, $new_user_email);
        
        if ($coupon_code) {
            // Send email with coupon TO THE REFEREE ONLY
            $email_sent = $this->send_referral_coupon_email($new_user_email, $coupon_code, $referrer_email_check);
            
            // Log success for debugging
            if (WP_DEBUG) {
                error_log("Simulated Webhook: Created coupon {$coupon_code} for REFEREE {$new_user_email} (referred by {$referrer_email_check})");
            }
            
            wp_send_json_success(array(
                'coupon_code' => $coupon_code,
                'email_sent' => $email_sent,
                'message' => 'Webhook simulation completed successfully',
                'referee_email' => $new_user_email,
                'referrer_email' => $referrer_email_check,
                'referral_code' => $referral_code_check
            ));
        } else {
            wp_send_json_error('Failed to create coupon for referee');
        }
    }
    
    /**
     * AJAX handler for checking plugin updates
     */
    public function ajax_check_plugin_updates() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_viral_loop_update_check')) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get remote version using our updater class
        $github_username = 'GrandWorks';
        $github_repo = 'viral-loop-referral';
        $update_path = 'https://api.github.com/repos/' . $github_username . '/' . $github_repo . '/releases/latest';
        
        $request = wp_remote_get($update_path);
        
        if (is_wp_error($request)) {
            wp_send_json_error('Failed to connect to update server: ' . $request->get_error_message());
        }
        
        if (wp_remote_retrieve_response_code($request) !== 200) {
            wp_send_json_error('Update server returned error: ' . wp_remote_retrieve_response_code($request));
        }
        
        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body, true);
        
        if (!isset($data['tag_name'])) {
            wp_send_json_error('Invalid response from update server');
        }
        
        $remote_version = ltrim($data['tag_name'], 'v');
        $current_version = WC_VIRAL_LOOP_REFERRAL_VERSION;
        
        $update_available = version_compare($current_version, $remote_version, '<');
        
        wp_send_json_success(array(
            'update_available' => $update_available,
            'current_version' => $current_version,
            'remote_version' => $remote_version,
            'update_url' => admin_url('update-core.php')
        ));
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
        
        // Check event type - we only process when someone JOINS via referral (not when they become a referrer)
        $event_type = $webhook_data['type'] ?? '';
        
        if ($event_type !== 'participation') {
            // Log other event types for debugging
            if (WP_DEBUG) {
                error_log("Viral Loop: Ignoring webhook event type: {$event_type}");
            }
            wp_send_json_error('Not a participation event - only processing when someone joins via referral');
            return;
        }
        
        // Extract user data
        $user_data = $webhook_data['user'] ?? array();
        $referrer_data = $webhook_data['referrer'] ?? array();
        
        $new_user_email = $user_data['email'] ?? '';  // REFEREE (person who joined)
        $referrer_email = $referrer_data['email'] ?? '';  // REFERRER (person who invited)
        $referral_code = $user_data['referralCode'] ?? '';
        
        // Create unique referral token from available data
        $referral_token = $referral_code ?: md5($new_user_email . time());
        
        if (empty($new_user_email)) {
            wp_send_json_error('Missing referee email');
            return;
        }
        
        // CRITICAL: Only process if there's an actual referrer (not direct signups)
        if (empty($referrer_email)) {
            if (WP_DEBUG) {
                error_log("Viral Loop: No referrer found - this is a direct signup, not a referral. Skipping coupon creation for: {$new_user_email}");
            }
            wp_send_json_error('No referrer - this is a direct signup, not a referral. No coupon needed.');
            return;
        }
        
        // IMPORTANT: Prevent referrers from getting coupons for themselves
        if ($referrer_email === $new_user_email) {
            if (WP_DEBUG) {
                error_log("Viral Loop: Self-referral attempt blocked: {$new_user_email}");
            }
            wp_send_json_error('Referrer cannot refer themselves');
            return;
        }
        
        // Skip duplicate check for custom coupon mode (custom coupons can be reused)
        if (!$this->settings['enable_custom_coupon_mode']) {
            // Check if coupon already exists for this referee/referral
            if ($this->coupon_exists_for_user($new_user_email, $referral_token)) {
                wp_send_json_success('Coupon already exists for this referee');
                return;
            }
        }
        
        // Create referral coupon FOR THE REFEREE ONLY
        $coupon_code = $this->create_referral_coupon($referral_token, $referrer_email, $new_user_email);
        
        if ($coupon_code) {
            // Send email with coupon TO THE REFEREE ONLY (not to referrer)
            $email_sent = $this->send_referral_coupon_email($new_user_email, $coupon_code, $referrer_email);
            
            // Log success - clarify who gets what
            if (WP_DEBUG) {
                error_log("Viral Loop: Created coupon {$coupon_code} for REFEREE {$new_user_email} (referred by {$referrer_email})");
                error_log("Viral Loop: Email sent to REFEREE ONLY: {$new_user_email}");
            }
            
            wp_send_json_success(array(
                'coupon_code' => $coupon_code,
                'email_sent' => $email_sent,
                'message' => 'Coupon created successfully for referee',
                'referee_email' => $new_user_email,
                'referrer_email' => $referrer_email
            ));
        } else {
            wp_send_json_error('Failed to create coupon for referee');
        }
    }
    
    /**
     * Validate Viral Loop webhook data
     */
    private function validate_viral_loop_webhook($data) {
        // Basic validation
        if (empty($data) || !is_array($data)) {
            if (WP_DEBUG) {
                error_log('Viral Loop Webhook: Invalid data structure');
            }
            return false;
        }
        
        // Check for required fields
        $required_fields = array('type', 'user');
        foreach ($required_fields as $field) {
            if (!isset($data[$field])) {
                if (WP_DEBUG) {
                    error_log("Viral Loop Webhook: Missing required field: {$field}");
                }
                return false;
            }
        }
        
        // Validate user data
        $user_data = $data['user'];
        if (!isset($user_data['email']) || !is_email($user_data['email'])) {
            if (WP_DEBUG) {
                error_log('Viral Loop Webhook: Invalid or missing user email');
            }
            return false;
        }
        
        // For participation events (referrals), require referrer data
        if ($data['type'] === 'participation') {
            $referrer_data = $data['referrer'] ?? array();
            $referrer_email = $referrer_data['email'] ?? '';
            
            if (empty($referrer_email) || !is_email($referrer_email)) {
                if (WP_DEBUG) {
                    error_log('Viral Loop Webhook: Participation event missing valid referrer email - this appears to be a direct signup, not a referral');
                }
                return false;
            }
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
                    <img src="https://traningsmat.se/wp-content/themes/traningsmat/img/traningsmat.svg" alt="Viral Loop Logo" style="width: 100px; height: 100px; margin-bottom: 20px;">
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
                $is_custom = get_post_meta($post_id, '_is_custom_coupon', true);
                $referral_count = get_post_meta($post_id, '_referrer_referral_count', true);
                
                echo '<strong>' . __('Referral Coupon', 'wc-viral-loop-referral') . '</strong><br>';
                if ($is_custom === 'yes') {
                    echo '<span style="color: #d63384; font-weight: bold;">🎯 ' . __('Custom Coupon Mode', 'wc-viral-loop-referral') . '</span><br>';
                } else if ($is_tiered === 'yes') {
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
            $settings['enable_custom_coupon_mode'] = isset($_POST['enable_custom_coupon_mode']);
            $settings['custom_coupon_code'] = sanitize_text_field($_POST['custom_coupon_code'] ?? '');
            
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

// Initialize updater
if (is_admin()) {
    new WC_Viral_Loop_Referral_Updater(__FILE__, 'GrandWorks', 'viral-loop-referral');
}
?>