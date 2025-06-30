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

## Installation

1. Upload the plugin files to the `/wp-content/plugins/viral-loops-woocommerce` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure the plugin settings under WooCommerce > Viral Loop Referrals

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
```
https://yoursite.com/?apply_coupon=COUPON_CODE&sc-page=referred
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

## Changelog

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