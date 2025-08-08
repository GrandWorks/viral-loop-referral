# WooCommerce Viral Loop Referral

A WordPress plugin that integrates Viral Loop referral system with WooCommerce to automatically generate and distribute unique coupon codes when referrals are accepted.

## Features

- **Automated Coupon Generation**: Creates unique coupon codes for each referral
- **Email Integration**: Sends welcome emails with coupon codes to new users
- **Gmail-Compatible Email Templates**: Professional HTML emails that render properly in all email clients
- **Tiered Referral System**: Enhanced discounts for top referrers (4+ referrals get 10% discount)
- **Flexible Discount Configuration**: Support for percentage and fixed amount discounts
- **Product Restrictions**: Include/exclude specific products or categories
- **Usage Limits**: Control coupon usage per user and total usage
- **WooCommerce Integration**: Seamless integration with WooCommerce cart system
- **Webhook Support**: Handles Viral Loop webhook events
- **Admin Dashboard**: Complete settings management interface
- **Automatic Updates**: Built-in update system via GitHub releases

## Installation

1. Upload the plugin files to the `/wp-content/plugins/viral-loops-woocommerce` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure the plugin settings under WooCommerce > Viral Loop Referrals
4. Set up your Viral Loop webhook to point to your WordPress site

## Requirements

- WordPress 5.0 or higher
- WooCommerce 4.0 or higher
- PHP 7.4 or higher

## Configuration

### Basic Settings
- **Discount Type**: Choose between percentage or fixed amount discounts
- **Discount Amount**: Set the discount value
- **Expiry Days**: Set coupon expiration period
- **Usage Limits**: Control how many times coupons can be used

### Tiered Referrals
- Enable enhanced discounts for referrers who bring in multiple users
- Configurable threshold (default: 4+ referrals = 10% discount)
- Visual indicators in emails for enhanced discounts

### Email Settings
- Customize welcome email subject and sender information
- Professional HTML email templates with inline CSS for maximum compatibility
- Gmail, Outlook, and mobile-friendly design
- Test email functionality included

### Product Restrictions
- Include/exclude specific products
- Include/exclude product categories
- Option to exclude sale items

## Plugin Updates

### GitHub Setup (Required for Updates)

1. **Upload to GitHub**: 
   - Create a GitHub repository for your plugin
   - Upload all plugin files to the repository

2. **Configure Update Settings**:
   - Open `wc-viral-loop-referral.php`
   - Find this line near the bottom:
     ```php
     new WC_Viral_Loop_Referral_Updater(__FILE__, 'your-github-username', 'your-repo-name');
     ```
   - Replace `'your-github-username'` with your actual GitHub username
   - Replace `'your-repo-name'` with your actual repository name

3. **Create Releases**:
   - When you want to release an update, create a new release in GitHub
   - Tag the release with version number (e.g., `v1.0.6`)
   - The tag should match the version number in your plugin header

### How Updates Work

1. **Automatic Checking**: WordPress automatically checks for updates when you visit the Updates page
2. **Manual Checking**: Use the "Check Now" button in the plugin settings
3. **Installing Updates**: Updates appear in **Dashboard → Updates** like any other plugin
4. **Version Management**: Update the version number in the plugin header before creating GitHub releases

### Update Process

1. Make changes to your plugin files
2. Update the version number in `wc-viral-loop-referral.php`:
   ```php
   * Version: 1.1.0  // Update this line
   ```
3. Update the version constant:
   ```php
   define('WC_VIRAL_LOOP_REFERRAL_VERSION', '1.1.0');  // Update this line
   ```
4. Commit and push changes to GitHub
5. Create a new release in GitHub with tag `v1.0.6`
6. WordPress will automatically detect the update

## Usage

### Webhook Integration
Set up your Viral Loop webhook to point to:
```
https://yoursite.com/wp-admin/admin-ajax.php?action=viral_loop_webhook
```

### Manual Referral Acceptance
Users can also accept referrals via URL:
```
https://yoursite.com/?accept_referral=1&referral_token=TOKEN&new_user_email=email@example.com
```

### Shortcode
Use `[referral_success]` shortcode on your referral success page.

### Auto-Apply Coupons
Coupons are automatically applied when users visit:

**Regular Coupons:**
```
https://yoursite.com/?coupon-code=COUPON_CODE&sc-page=referred
```

**Custom Coupons:**
```
https://yoursite.com/?tm-coupon=COUPON_CODE&tm-page=referred
```

## Development

### File Structure
- `wc-viral-loop-referral.php` - Main plugin file with all functionality
- `admin-page.php` - Admin interface template
- `README.md` - Documentation
- `.gitignore` - Git ignore rules

### Hooks and Filters
The plugin provides various WordPress hooks for customization:
- `init` - Handles referral acceptance
- `wp_loaded` - Auto-applies coupons from URL
- `wp_footer` - Adds frontend JavaScript for Viral Loop integration

### Email Template
The email template uses table-based layout with inline CSS for maximum compatibility across email clients. Key features:
- Gmail-compatible HTML structure
- Responsive design for mobile devices
- Professional branding integration
- Tiered referral highlighting

## Email Client Compatibility

✅ **Fully Tested & Compatible:**
- Gmail (web and mobile)
- Outlook (all versions)
- Apple Mail
- Yahoo Mail
- Thunderbird
- Mobile email clients

## How It Works

### The Referral Flow:
1. **REFERRER** creates a referral link/code in Viral Loop
2. **REFEREE** clicks the link and joins/signs up  
3. Viral Loop sends webhook to this plugin (participation event)
4. Plugin creates a coupon **ONLY for the REFEREE**
5. Plugin sends welcome email with coupon **ONLY to the REFEREE**
6. REFERRER's successful referral count is incremented for tiered rewards

### Key Points:
✅ **Only REFEREES get coupons and emails**
✅ **REFERRERS never receive coupons for their own referrals**  
✅ **Only successful referrals (where referee gets coupon) count toward thresholds**
✅ **Self-referral protection prevents users from referring themselves**

## Changelog

### 1.1.0
- **ADDED**: Separate email template for custom coupon mode
- **ADDED**: Custom URL format for custom coupons (`?tm-coupon=CODE&tm-page=referred`)
- **FIXED**: Custom coupon mode now sends emails properly when users accept invitations
- **FIXED**: Skip duplicate user checks for custom coupon mode to allow reusable custom coupons
- **IMPROVED**: Enhanced email messaging specifically for custom coupons
- **IMPROVED**: Backward compatibility maintained for regular coupon URLs
- **IMPROVED**: Test email functionality supports both custom and regular templates

### 1.0.7
- **Removed**: Removed Debugging information from admin dashboard

### 1.0.6
- **ADDED**: Missing fields for product-based restriction for coupons
- **IMPROVED**: Enhanced coupon restriction capabilities

### 1.0.5
- **CHANGED**: URL parameter changed from `apply_coupon` to `coupon-code` for better readability
- **UPDATED**: All coupon URLs now use `?coupon-code=` instead of `?apply_coupon=`
- **IMPROVED**: More semantic and user-friendly URL structure

### 1.0.4
- **CHANGED**: All coupon codes now generate in lowercase format (e.g., `ref-a1b2c3d4` instead of `REF-A1B2C3D4`)
- **IMPROVED**: Consistent lowercase formatting for both live and test coupon codes

### 1.0.3
- **CRITICAL FIX**: Only send emails and create coupons when there's an actual referrer
- **FIXED**: Prevent emails being sent to users who join directly (not via referral)
- **IMPROVED**: Enhanced webhook validation to require referrer for participation events
- **IMPROVED**: Better logging for direct signups vs referrals
- **IMPROVED**: More robust validation in both webhook and manual referral handlers

### 1.0.2
- **IMPROVED**: Enhanced referral flow documentation and code comments
- **ADDED**: Self-referral protection (users can't refer themselves)
- **ADDED**: Duplicate coupon prevention for same referee/referral
- **ADDED**: Clear logging to distinguish referrer vs referee actions  
- **ADDED**: Better error messages and webhook event type validation
- **ADDED**: Helper function for future referrer notifications (without coupons)

### 1.0.1
- **FIXED**: Email HTML rendering in Gmail and other email clients
- **IMPROVED**: Converted CSS to inline styles for better email compatibility  
- **IMPROVED**: Table-based email layout for maximum compatibility
- **ADDED**: Better accessibility with proper alt attributes

### 1.0.0
- Initial release
- Basic referral coupon generation
- Email integration
- Tiered referral system
- WooCommerce integration
- Admin dashboard

## Support

For support and feature requests, please visit our [GitHub repository](https://github.com/GrandWorks/viral-loop-referral).

## License

This plugin is licensed under the GPL v2 or later.

---

Made with ❤️ for WooCommerce and Viral Loop integration 