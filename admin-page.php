<?php
/**
 * Admin page template for WooCommerce Viral Loop Referral plugin
 * This file should be saved as 'admin-page.php' in your plugin directory
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php _e('WooCommerce Viral Loop Referral Settings', 'wc-viral-loop-referral'); ?></h1>
    
    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                <!-- Settings Form -->
                <div class="postbox">
                    <h2 class="hndle"><?php _e('Coupon Settings', 'wc-viral-loop-referral'); ?></h2>
                    <div class="inside">
                        <form method="post" action="">
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Discount Type', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <select name="discount_type">
                                            <option value="percent" <?php selected($this->settings['discount_type'], 'percent'); ?>><?php _e('Percentage', 'wc-viral-loop-referral'); ?></option>
                                            <option value="fixed_cart" <?php selected($this->settings['discount_type'], 'fixed_cart'); ?>><?php _e('Fixed Amount', 'wc-viral-loop-referral'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Discount Amount', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <input type="number" name="discount_amount" value="<?php echo esc_attr($this->settings['discount_amount']); ?>" step="0.01" min="0" />
                                        <p class="description"><?php _e('Enter the discount amount (percentage or fixed amount based on discount type).', 'wc-viral-loop-referral'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Expiry Days', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <input type="number" name="expiry_days" value="<?php echo esc_attr($this->settings['expiry_days']); ?>" min="0" />
                                        <p class="description"><?php _e('Number of days after which the coupon expires. Set to 0 for no expiration.', 'wc-viral-loop-referral'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Usage Limit', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <input type="number" name="usage_limit" value="<?php echo esc_attr($this->settings['usage_limit']); ?>" min="0" />
                                        <p class="description"><?php _e('How many times this coupon can be used in total. Set to 0 for unlimited usage.', 'wc-viral-loop-referral'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Usage Limit Per User', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <input type="number" name="usage_limit_per_user" value="<?php echo esc_attr($this->settings['usage_limit_per_user']); ?>" min="0" />
                                        <p class="description"><?php _e('How many times this coupon can be used by each user. Set to 0 for unlimited usage per user.', 'wc-viral-loop-referral'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Minimum Amount', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <input type="number" name="minimum_amount" value="<?php echo esc_attr($this->settings['minimum_amount']); ?>" step="0.01" min="0" />
                                        <p class="description"><?php _e('Minimum order amount required to use the coupon. Set to 0 for no minimum.', 'wc-viral-loop-referral'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Allow Free Shipping', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="free_shipping" value="1" <?php checked($this->settings['free_shipping']); ?> />
                                            <?php _e('Check this box if the coupon grants free shipping.', 'wc-viral-loop-referral'); ?>
                                        </label>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Individual Use Only', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="individual_use" value="1" <?php checked($this->settings['individual_use']); ?> />
                                            <?php _e('Check this box if the coupon cannot be used in conjunction with other coupons.', 'wc-viral-loop-referral'); ?>
                                        </label>
                                    </td>
                                </tr>
                            </table>
                            
                            <h3><?php _e('Custom Coupon Mode', 'wc-viral-loop-referral'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Enable Custom Coupon Mode', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="enable_custom_coupon_mode" value="1" <?php checked($this->settings['enable_custom_coupon_mode']); ?> onchange="toggleCustomCouponSettings()" />
                                            <?php _e('Disable automatic coupon creation and use custom coupon code', 'wc-viral-loop-referral'); ?>
                                        </label>
                                        <p class="description"><?php _e('When enabled, the plugin will not create automatic coupons with discounts. Instead, it will send emails with your custom coupon code.', 'wc-viral-loop-referral'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr class="custom-coupon-setting">
                                    <th scope="row"><?php _e('Custom Coupon Code', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <input type="text" name="custom_coupon_code" value="<?php echo esc_attr($this->settings['custom_coupon_code']); ?>" class="regular-text" placeholder="<?php _e('Enter your custom coupon code', 'wc-viral-loop-referral'); ?>" />
                                        <p class="description"><?php _e('Enter the custom coupon code that will be sent to all referees. Make sure this code exists in your WooCommerce coupons or create it manually.', 'wc-viral-loop-referral'); ?></p>
                                        
                                        <?php if ($this->settings['enable_custom_coupon_mode']) : ?>
                                        <div class="custom-mode-warning">
                                            <strong><?php _e('⚠️ Custom Coupon Mode Active', 'wc-viral-loop-referral'); ?></strong><br>
                                            <?php _e('When this mode is enabled:', 'wc-viral-loop-referral'); ?>
                                            <ul style="margin: 10px 0 0 20px;">
                                                <li><?php _e('All automatic coupon creation and discount calculations are disabled', 'wc-viral-loop-referral'); ?></li>
                                                <li><?php _e('Tiered referral system is bypassed', 'wc-viral-loop-referral'); ?></li>
                                                <li><?php _e('Only emails with your custom coupon code will be sent', 'wc-viral-loop-referral'); ?></li>
                                                <li><?php _e('You must create and manage the coupon code manually in WooCommerce', 'wc-viral-loop-referral'); ?></li>
                                            </ul>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                            
                            <h3 class="tiered-referral-section"><?php _e('Tiered Referral System', 'wc-viral-loop-referral'); ?></h3>
                            <table class="form-table tiered-referral-section">
                                <tr>
                                    <th scope="row"><?php _e('Enable Tiered Referrals', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="enable_tiered_referrals" value="1" <?php checked($this->settings['enable_tiered_referrals']); ?> />
                                            <?php _e('Enable enhanced discounts for high-performing referrers', 'wc-viral-loop-referral'); ?>
                                        </label>
                                        <p class="description"><?php _e('When enabled, referrers who have successfully referred a certain number of people will provide enhanced discounts to subsequent referrals.', 'wc-viral-loop-referral'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Referral Threshold', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <input type="number" name="tiered_threshold" value="<?php echo esc_attr($this->settings['tiered_threshold']); ?>" min="1" />
                                        <p class="description"><?php _e('Number of successful referrals after which the enhanced discount kicks in. Default: 3 (4th referral onwards gets enhanced discount)', 'wc-viral-loop-referral'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Enhanced Discount Amount', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <input type="number" name="tiered_discount_amount" value="<?php echo esc_attr($this->settings['tiered_discount_amount']); ?>" step="0.01" min="0" />%
                                        <p class="description"><?php _e('Enhanced discount percentage for tiered referrals. This will always be a percentage discount regardless of the main discount type.', 'wc-viral-loop-referral'); ?></p>
                                    </td>
                                </tr>
                            </table>
                            
                            <h3><?php _e('Product Restrictions', 'wc-viral-loop-referral'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Include Products', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <input type="text" name="product_ids" id="product_ids" value="<?php echo esc_attr(implode(',', $this->settings['product_ids'])); ?>" class="regular-text" />
                                        <button type="button" class="button" onclick="openProductSelector('product_ids')"><?php _e('Select Products', 'wc-viral-loop-referral'); ?></button>
                                        <p class="description"><?php _e('Comma-separated list of product IDs. Leave empty to allow all products. Click "Select Products" to choose from a list.', 'wc-viral-loop-referral'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Exclude Products', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <input type="text" name="exclude_product_ids" id="exclude_product_ids" value="<?php echo esc_attr(implode(',', $this->settings['exclude_product_ids'])); ?>" class="regular-text" />
                                        <button type="button" class="button" onclick="openProductSelector('exclude_product_ids')"><?php _e('Select Products', 'wc-viral-loop-referral'); ?></button>
                                        <p class="description"><?php _e('Comma-separated list of product IDs to exclude from coupon usage.', 'wc-viral-loop-referral'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Include Categories', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <select name="product_categories[]" multiple="multiple" style="width: 100%; height: 100px;">
                                            <?php
                                            $categories = get_terms(array(
                                                'taxonomy' => 'product_cat',
                                                'hide_empty' => false,
                                            ));
                                            foreach ($categories as $category) {
                                                $selected = in_array($category->term_id, $this->settings['product_categories']) ? 'selected' : '';
                                                echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <p class="description"><?php _e('Hold Ctrl/Cmd to select multiple categories. Leave empty to allow all categories.', 'wc-viral-loop-referral'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Exclude Categories', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <select name="exclude_product_categories[]" multiple="multiple" style="width: 100%; height: 100px;">
                                            <?php
                                            foreach ($categories as $category) {
                                                $selected = in_array($category->term_id, $this->settings['exclude_product_categories']) ? 'selected' : '';
                                                echo '<option value="' . esc_attr($category->term_id) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                                            }
                                            ?>
                                        </select>
                                        <p class="description"><?php _e('Hold Ctrl/Cmd to select multiple categories to exclude from coupon usage.', 'wc-viral-loop-referral'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Exclude Sale Items', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="exclude_sale_items" value="1" <?php checked($this->settings['exclude_sale_items']); ?> />
                                            <?php _e('Exclude products that are already on sale from coupon usage.', 'wc-viral-loop-referral'); ?>
                                        </label>
                                    </td>
                                </tr>
                            </table>
                            
                            <h3><?php _e('Email Settings', 'wc-viral-loop-referral'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Email Subject', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <input type="text" name="email_subject" value="<?php echo esc_attr($this->settings['email_subject']); ?>" class="regular-text" />
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Sender Name', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <input type="text" name="sender_name" value="<?php echo esc_attr($this->settings['sender_name']); ?>" class="regular-text" />
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Sender Email', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <input type="email" name="sender_email" value="<?php echo esc_attr($this->settings['sender_email']); ?>" class="regular-text" />
                                    </td>
                                </tr>
                            </table>
                            
                            <h3><?php _e('Test Emails', 'wc-viral-loop-referral'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Send Test Emails', 'wc-viral-loop-referral'); ?></th>
                                    <td>
                                        <input type="email" id="testEmailAddress" placeholder="<?php _e('Enter email address...', 'wc-viral-loop-referral'); ?>" class="regular-text" value="<?php echo esc_attr(get_option('admin_email')); ?>" />
                                        <br><br>
                                        <button type="button" class="button" onclick="sendTestEmail('regular')"><?php _e('Send Regular Test Email', 'wc-viral-loop-referral'); ?></button>
                                        <?php if ($this->settings['enable_tiered_referrals']) : ?>
                                        <button type="button" class="button" onclick="sendTestEmail('tiered')"><?php _e('Send Tiered Test Email', 'wc-viral-loop-referral'); ?></button>
                                        <?php endif; ?>
                                        <?php if ($this->settings['enable_custom_coupon_mode']) : ?>
                                        <button type="button" class="button" onclick="sendTestEmail('custom')"><?php _e('Send Custom Test Email', 'wc-viral-loop-referral'); ?></button>
                                        <?php endif; ?>
                                        <p class="description"><?php _e('Send test emails to see how they will look in your email client. Test emails will have [TEST] prefix in subject line.', 'wc-viral-loop-referral'); ?></p>
                                        <div id="testEmailResult" style="margin-top: 10px;"></div>
                                    </td>
                                </tr>
                            </table>
                            
                            <?php submit_button(); ?>
                        </form>
                    </div>
                </div>
                
                <!-- Plugin Update Section -->
                <div class="postbox" style="margin-top: 20px;">
                    <h2 class="hndle"><?php _e('Plugin Updates', 'wc-viral-loop-referral'); ?></h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Current Version', 'wc-viral-loop-referral'); ?></th>
                                <td>
                                    <strong><?php echo WC_VIRAL_LOOP_REFERRAL_VERSION; ?></strong>
                                    <p class="description"><?php _e('This is the currently installed version of the plugin.', 'wc-viral-loop-referral'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Check for Updates', 'wc-viral-loop-referral'); ?></th>
                                <td>
                                    <button type="button" class="button" onclick="checkForUpdates()"><?php _e('Check Now', 'wc-viral-loop-referral'); ?></button>
                                    <p class="description"><?php _e('Manually check for plugin updates from the GitHub repository.', 'wc-viral-loop-referral'); ?></p>
                                    <div id="updateCheckResult" style="margin-top: 10px;"></div>
                                </td>
                            </tr>
                            
                        </table>
                    </div>
                </div>
                
                <!-- Test Webhook Section -->
                <div class="postbox" style="margin-top: 20px;">
                    <h2 class="hndle"><?php _e('Test Webhook Simulation', 'wc-viral-loop-referral'); ?></h2>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Simulate Webhook Call', 'wc-viral-loop-referral'); ?></th>
                                <td>
                                    <div style="margin-bottom: 15px;">
                                        <label><?php _e('Referrer Email:', 'wc-viral-loop-referral'); ?></label><br>
                                        <input type="email" id="testReferrerEmail" value="referrer@example.com" class="regular-text" />
                                    </div>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <label><?php _e('New User Email (Referee):', 'wc-viral-loop-referral'); ?></label><br>
                                        <input type="email" id="testRefereeEmail" value="newuser@example.com" class="regular-text" />
                                    </div>
                                    
                                    <div style="margin-bottom: 15px;">
                                        <label><?php _e('Referral Code:', 'wc-viral-loop-referral'); ?></label><br>
                                        <input type="text" id="testReferralCode" value="TEST123" class="regular-text" />
                                    </div>
                                    
                                    <button type="button" class="button button-primary" onclick="simulateWebhook()"><?php _e('🚀 Simulate Referral Webhook', 'wc-viral-loop-referral'); ?></button>
                                    <p class="description"><?php _e('This will simulate a Viral Loop webhook call to test the referral functionality. It will create a coupon and send an email to the referee.', 'wc-viral-loop-referral'); ?></p>
                                    <div id="webhookTestResult" style="margin-top: 15px;"></div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Add AJAX URL for JavaScript
const VAjaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

// Toggle custom coupon settings visibility
function toggleCustomCouponSettings() {
    const isCustomMode = document.querySelector('input[name="enable_custom_coupon_mode"]').checked;
    const customCouponSettings = document.querySelectorAll('.custom-coupon-setting');
    const tieredReferralSection = document.querySelectorAll('.tiered-referral-section');
    
    // Show/hide custom coupon field
    customCouponSettings.forEach(function(element) {
        element.style.display = isCustomMode ? 'table-row' : 'none';
    });
    
    // Hide/show tiered referral settings
    tieredReferralSection.forEach(function(element) {
        element.style.display = isCustomMode ? 'none' : (element.tagName === 'H3' ? 'block' : 'table');
    });
    
    // Show/hide test email buttons - we need to reload the page for proper PHP conditional rendering
    // This is handled by PHP conditionals, but we could add some dynamic visibility here if needed
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleCustomCouponSettings();
});

// Send test email functionality
function sendTestEmail(emailType) {
    const emailAddress = document.getElementById('testEmailAddress').value;
    const resultDiv = document.getElementById('testEmailResult');
    
    if (!emailAddress) {
        resultDiv.innerHTML = '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; color: #721c24;">❌ <?php _e('Please enter an email address', 'wc-viral-loop-referral'); ?></div>';
        return;
    }
    
    const emailTypeText = emailType === 'tiered' ? '<?php _e('tiered', 'wc-viral-loop-referral'); ?>' : '<?php _e('regular', 'wc-viral-loop-referral'); ?>';
    resultDiv.innerHTML = `<p style="color: #666;">⏳ <?php _e('Sending', 'wc-viral-loop-referral'); ?> ${emailTypeText} <?php _e('test email...', 'wc-viral-loop-referral'); ?></p>`;
    
    fetch(VAjaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'preview_referral_email',
            email_type: emailType,
            test_email: emailAddress,
            nonce: '<?php echo wp_create_nonce('wc_viral_loop_email_preview'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `
                <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; color: #155724;">
                    ✅ <strong>${data.data.message}</strong><br>
                    <?php _e('Sample coupon code:', 'wc-viral-loop-referral'); ?> <code>${data.data.coupon_code}</code>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; color: #721c24;">
                    ❌ <strong><?php _e('Failed to send test email:', 'wc-viral-loop-referral'); ?></strong><br>
                    ${data.data || '<?php _e('Unknown error', 'wc-viral-loop-referral'); ?>'}
                </div>
            `;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; color: #721c24;">
                ❌ <strong><?php _e('Network error:', 'wc-viral-loop-referral'); ?></strong><br>
                ${error.message}
            </div>
        `;
    });
}

// Check for plugin updates
function checkForUpdates() {
    const resultDiv = document.getElementById('updateCheckResult');
    resultDiv.innerHTML = '<p style="color: #666;">⏳ <?php _e('Checking for updates...', 'wc-viral-loop-referral'); ?></p>';
    
    fetch(VAjaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'check_plugin_updates',
            nonce: '<?php echo wp_create_nonce('wc_viral_loop_update_check'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.data.update_available) {
                resultDiv.innerHTML = `
                    <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 4px; color: #856404;">
                        🔄 <strong><?php _e('Update Available!', 'wc-viral-loop-referral'); ?></strong><br>
                        <?php _e('Current version:', 'wc-viral-loop-referral'); ?> <code>${data.data.current_version}</code><br>
                        <?php _e('Available version:', 'wc-viral-loop-referral'); ?> <code>${data.data.remote_version}</code><br>
                        <br>
                        <a href="${data.data.update_url}" class="button button-primary"><?php _e('Go to Updates Page', 'wc-viral-loop-referral'); ?></a>
                    </div>
                `;
            } else {
                resultDiv.innerHTML = `
                    <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; color: #155724;">
                        ✅ <strong><?php _e('Plugin is up to date!', 'wc-viral-loop-referral'); ?></strong><br>
                        <?php _e('Version:', 'wc-viral-loop-referral'); ?> <code>${data.data.current_version}</code>
                    </div>
                `;
            }
        } else {
            resultDiv.innerHTML = `
                <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; color: #721c24;">
                    ❌ <strong><?php _e('Failed to check for updates:', 'wc-viral-loop-referral'); ?></strong><br>
                    ${data.data || '<?php _e('Unable to connect to update server', 'wc-viral-loop-referral'); ?>'}
                </div>
            `;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; color: #721c24;">
                ❌ <strong><?php _e('Network error:', 'wc-viral-loop-referral'); ?></strong><br>
                ${error.message}
            </div>
        `;
         });
}

// Product selector functionality
function openProductSelector(fieldId) {
    // Check if modal already exists
    let modal = document.getElementById('product-selector-modal');
    if (modal) {
        modal.remove();
    }
    
    // Create modal
    const modalHtml = `
        <div id="product-selector-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; display: flex; align-items: center; justify-content: center;">
            <div style="background: white; padding: 20px; border-radius: 8px; max-width: 80%; max-height: 80%; overflow: auto; min-width: 500px;">
                <h3><?php _e('Select Products', 'wc-viral-loop-referral'); ?></h3>
                <div id="product-search-container">
                    <input type="text" id="product-search" placeholder="<?php _e('Search products...', 'wc-viral-loop-referral'); ?>" style="width: 100%; margin-bottom: 10px; padding: 8px;">
                    <div id="product-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                        <p><?php _e('Loading products...', 'wc-viral-loop-referral'); ?></p>
                    </div>
                </div>
                <div style="margin-top: 15px; text-align: right;">
                    <button type="button" class="button" onclick="closeProductSelector()"><?php _e('Cancel', 'wc-viral-loop-referral'); ?></button>
                    <button type="button" class="button button-primary" onclick="selectProducts('${fieldId}')"><?php _e('Select', 'wc-viral-loop-referral'); ?></button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Load products
    loadProducts();
}

function loadProducts() {
    const productList = document.getElementById('product-list');
    
    fetch(VAjaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'get_products_for_selector',
            nonce: '<?php echo wp_create_nonce('wc_viral_loop_products'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            let productHtml = '';
            data.data.forEach(product => {
                productHtml += `
                    <div class="product-item" style="padding: 8px; border-bottom: 1px solid #eee; cursor: pointer;" data-product-id="${product.id}">
                        <label style="cursor: pointer; display: flex; align-items: center;">
                            <input type="checkbox" value="${product.id}" style="margin-right: 8px;">
                            <span><strong>${product.name}</strong> - ${product.price} (ID: ${product.id})</span>
                        </label>
                    </div>
                `;
            });
            productList.innerHTML = productHtml;
            
            // Add search functionality
            const searchInput = document.getElementById('product-search');
            searchInput.addEventListener('input', filterProducts);
            
            // Add click handlers for product items
            const productItems = productList.querySelectorAll('.product-item');
            productItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    if (e.target.type !== 'checkbox') {
                        const checkbox = this.querySelector('input[type="checkbox"]');
                        checkbox.checked = !checkbox.checked;
                    }
                });
            });
        } else {
            productList.innerHTML = `<p style="color: red;"><?php _e('Failed to load products', 'wc-viral-loop-referral'); ?></p>`;
        }
    })
    .catch(error => {
        productList.innerHTML = `<p style="color: red;"><?php _e('Error loading products:', 'wc-viral-loop-referral'); ?> ${error.message}</p>`;
    });
}

function filterProducts() {
    const searchTerm = document.getElementById('product-search').value.toLowerCase();
    const productItems = document.querySelectorAll('.product-item');
    
    productItems.forEach(item => {
        const productText = item.textContent.toLowerCase();
        if (productText.includes(searchTerm)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

function selectProducts(fieldId) {
    const checkedProducts = document.querySelectorAll('#product-list input[type="checkbox"]:checked');
    const productIds = Array.from(checkedProducts).map(cb => cb.value);
    
    const field = document.getElementById(fieldId);
    field.value = productIds.join(',');
    
    closeProductSelector();
}

function closeProductSelector() {
    const modal = document.getElementById('product-selector-modal');
    if (modal) {
        modal.remove();
    }
}

// Simulate webhook functionality
function simulateWebhook() {
    const referrerEmail = document.getElementById('testReferrerEmail').value;
    const refereeEmail = document.getElementById('testRefereeEmail').value;
    const referralCode = document.getElementById('testReferralCode').value;
    const resultDiv = document.getElementById('webhookTestResult');
    
    if (!referrerEmail || !refereeEmail || !referralCode) {
        resultDiv.innerHTML = '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; color: #721c24;">❌ <?php _e('Please fill in all fields', 'wc-viral-loop-referral'); ?></div>';
        return;
    }
    
    if (referrerEmail === refereeEmail) {
        resultDiv.innerHTML = '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; color: #721c24;">❌ <?php _e('Referrer and referee emails must be different (self-referral is blocked)', 'wc-viral-loop-referral'); ?></div>';
        return;
    }
    
    resultDiv.innerHTML = '<p style="color: #666;">⏳ <?php _e('Simulating webhook call...', 'wc-viral-loop-referral'); ?></p>';
    
    fetch(VAjaxurl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            action: 'simulate_viral_loop_webhook',
            referrer_email: referrerEmail,
            referee_email: refereeEmail,
            referral_code: referralCode,
            nonce: '<?php echo wp_create_nonce('wc_viral_loop_webhook_test'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            resultDiv.innerHTML = `
                <div style="background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 4px; color: #155724;">
                    ✅ <strong><?php _e('Webhook simulation successful!', 'wc-viral-loop-referral'); ?></strong><br>
                    <strong><?php _e('Coupon Created:', 'wc-viral-loop-referral'); ?></strong> <code>${data.data.coupon_code}</code><br>
                    <strong><?php _e('Email Sent To:', 'wc-viral-loop-referral'); ?></strong> ${data.data.referee_email}<br>
                    <strong><?php _e('Referred By:', 'wc-viral-loop-referral'); ?></strong> ${data.data.referrer_email}<br>
                    ${data.data.email_sent ? '✅ <?php _e('Email sent successfully', 'wc-viral-loop-referral'); ?>' : '❌ <?php _e('Email failed to send', 'wc-viral-loop-referral'); ?>'}
                </div>
            `;
        } else {
            resultDiv.innerHTML = `
                <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; color: #721c24;">
                    ❌ <strong><?php _e('Webhook simulation failed:', 'wc-viral-loop-referral'); ?></strong><br>
                    ${data.data || '<?php _e('Unknown error', 'wc-viral-loop-referral'); ?>'}
                </div>
            `;
        }
    })
    .catch(error => {
        resultDiv.innerHTML = `
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 4px; color: #721c24;">
                ❌ <strong><?php _e('Network error:', 'wc-viral-loop-referral'); ?></strong><br>
                ${error.message}
            </div>
        `;
    });
}
</script>

<style>
.postbox h2.hndle {
    padding: 8px 12px;
    margin: 0;
    line-height: 1.4;
}

.postbox .inside {
    padding: 0 12px 12px;
    margin: 0;
}

.form-table th {
    width: 200px;
}

code {
    background: #f1f1f1;
    padding: 2px 5px;
    border-radius: 3px;
    font-family: Consolas, Monaco, monospace;
    display: inline-block;
    margin: 5px 0;
}

/* Initially hide custom coupon settings */
.custom-coupon-setting {
    display: none;
}

/* Custom mode warning */
.custom-mode-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    padding: 15px;
    border-radius: 4px;
    color: #856404;
    margin: 15px 0;
}
.custom-mode-warning strong {
    color: #856404;
}
</style> 