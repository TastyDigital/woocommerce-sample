# WooCommerce Sample

Contributors: twobyte, isikom, zauker  
Tags: ecommerce, e-commerce, commerce, woothemes, wordpress ecommerce, woocommerce, sample, free sample  
Requires at least: 3.5  
Tested up to: 6.2  
Stable tag: 0.9.0  
License: GPLv2  
License URI: http://www.gnu.org/licenses/gpl-2.0.html  

Manage Samples Of Your Products  

## Description

Plugin for Wordpress woocommerce that allow add to cart sample item of a certain products

Compatibility with plugins:

* WooCommerce Chained Products
* Min/Max Quantities
* Measurement Price Calculator

## GET INVOLVED

Developers can checkout and contribute to the source code on the [GitHub Repository](https://github.com/TastyDigital/woocommerce-sample), this has been forked from [isikom’s original repository](https://github.com/isikom/woocommerce-sample) which at this point hasn’t been updated since 2016.

## Installation

### Automatic installation

Using composer and bedrock, ensure `{
"type": "vcs",
"url": "git@github.com:TastyDigital/woocommerce-sample.git"
}` has been added to the repositories section, then: 

`composer require tastydigital/woocommerce-sample`

### Manual installation

The manual installation method involves downloading our plugin and uploading it to your webserver via your favourite FTP application.

1. Download the plugin file to your computer and unzip it
2. Using an FTP program, or your hosting control panel, upload the unzipped plugin folder to your WordPress installation’s wp-content/plugins/ directory.
3. Activate the plugin from the Plugins menu within the WordPress admin.

## Frequently Asked Questions

### Free shipping doesn't work 

The "free shipping" option on Sample back end panel need that "free shipping" methods is enabled for your on your WooCommerce installation.
You could enable it on WooCommerce -> Settings -> Shipping -> Shipping Zone -> Free Shipping.
Make it enabled and set it available for coupon.
(as you can see on screenshot tab)

## Screenshots

1. Front end - "Add Sample" button
2. Back end - Sample setting on product page
3. remember to enable WooCommerce Free Shipping to use it on WooCommerce Sample Plugins
4. remember to set Free Shipping available for coupon

## Changelog

= 0.9.2 – 25/10/2022
* New Sample available badge feature

= 0.9.1 – 30/12/2019
* Defaulting sample shipping and costs to free

= 0.9.0 - 08/10/2019 =
* Original repository forked and fixed deprecated calls and methods

= 0.8.0 - 05/08/2016 =
* added support for WooCommerce Chained Products plugin
* update hooks and functions used by WooCommerce
* update .pot file
* fix CSS for admin panel 

= 0.7.12 - 12/06/2014 =
* added button CSS filter to add custom style
* fix button for products with a variation
* added filter for Measurement Price Calculator plugin

= 0.7.2 - 11/06/2014 =
* added meta info to the sample item ordered

= 0.7.1 - 17/03/2014 =
* fix shipping methods - only free shipping is showed if available

= 0.7.0 - 17/03/2014 =
* sample shipping settings works fine (options available are default or free shipping, custom shipping cost is in todo list)

= 0.6.0 - 12/03/2014 =
* sample price settings works fine

= 0.5.0 - 02/03/2014 =
* Add Localization
* Added file .POT and italian translation
* Fix CSS for some issue (WooCommerce Version 2.1)

= 0.4.1 - 01/03/2014 =
* Fix Min/Max filters.
* Fix CSS for tab

= 0.4 - 24/02/2014 =
* Initial Alpha Release.
