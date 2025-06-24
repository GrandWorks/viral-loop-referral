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
                            
                            <h3><?php _e('Tiered Referral System', 'wc-viral-loop-referral'); ?></h3>
                            <table class="form-table">
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
                                        <p class="description"><?php _e('Send test emails to see how they will look in your email client. Test emails will have [TEST] prefix in subject line.', 'wc-viral-loop-referral'); ?></p>
                                        <div id="testEmailResult" style="margin-top: 10px;"></div>
                                    </td>
                                </tr>
                            </table>
                            
                            <?php submit_button(); ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Add AJAX URL for JavaScript
const VAjaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

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
</style> 