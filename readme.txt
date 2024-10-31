=== Poshtiban.com Wordpress Plugin ===
Contributors: mojtabad,poshtibancom
Tags: storage,backup,woocommerce,secure download, download, external media, API, Upload,
Requires at least: 4.9.1
Tested up to: 5.8
Requires PHP: 5.6
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/quick-guide-gplv3.en.html

A complete storage solution for WordPress websites.

== Description ==
WordPress Poshtiban Plugin allows uploading media files to a partition of Poshtiban Cloud Storage(poshtiban.com). So you don't need to high capacity hosting anymore. Because Poshtiban is cheaper than any file hosting services.  
Wp-Poshtiban gives you the power and ease of use of serving your downloadable products (in Woocommerce) through the Poshtiban infrastructure. 
Also, you can to Backup your files or database backups into the cloud with a single click!

BENEFITS

Using Poshtibn cloud storage to store the wordpress media and downloadable product has the following benefits:
You can setup your own subdomain on your poshtiban's partition by using CNAME record (cname.poshtiban.com).
Poshtiban Cloud storage gives you a 5 Gigabyte partition for free. You can also bay bigger partition up to 1 Terabyte.
For your downloadable product in woocommerce, you can easily sell your product and pay very littel for storing your files.  

FEATURES
Upload a file or a whole folder in to add media/upload to poshtiban.
Remote upload option by using direct link.
Remote upload list for checking upload status. 
In backup menu you can to create backup database or All files (backup for your all wordpress files), themes (backup from themes directory /wp-content/themes/), plugins (backup from plugins directory  /wp-content/plugins/) and uploads (backup from uploads directory  /wp-content/uploads/)


== Installation ==
* Setup your own subdomain on your Poshtiban's partition by using CNAME record (cname.poshtiban.com).
* In your Poshtiban's profile(poshtiban.com) you should activate direct link status and set a username for your Poshtiban account. 
* Download the plugin and place it in your /wp-content/plugins/ directory.
* After installing WordPress plugin, in general setting tab set your partition's Access Token. 
* Now when you are using �add media�s  button in post or product(for Woocommerce) you can see �upload to Poshtiban� in the add media's window.



== Changelog ==

= 2.7.1 =
* Compatibility with version 5.8

= 2.6.0 =
* Fix webhook notice
* Fix media page loading time

= 2.5.0 =
* Fix video upload

= 2.4.0 =
* Activate uppy chuck upload

= 2.3.6 =
* Fix woocommerce download access

= 2.3.5 =
* Fix cropped thumbnail


= 2.3.4 =
* Fix transfer media with special characters name


= 2.3.2 =
* Add force download option to woocommerce settings
* Activate debug mode for admins only
* Fix metadata for old attachments
* Fix woocommerce product gallery lightbox



= 2.3.0 =
* Add debug mode
* Add support of empty permalink for webhook url
* Fix webhook change issue
* Fix actions and filters


= 2.2.0 =
* Add new image sizes if the were not exists when file uploaded


= 2.1.0 =
* Add resend button for remote uploads
* Add admin notice for webhook url problems
* Add default size for get thumbnail image sizes


= 2.0.0 =
* Add import and export
* Replace Flowjs with uppy
* Add duplicator support



= 1.3.0 =
* Add `poshtiban_show_download_template` filter
* Add `poshtiban_downloads_template` action



= 1.2.0 =
* Remove woocommerce settings tabs if woocommerce plugin is not installed
* Fix secure download link generator for woocommrce


== Screenshots ==
1. General settings page
2. Media settings page
3. Backup settings page
4. Woocommerce settings page
5. Duplicator integration
6. Remote downloads events list
7. Upload new file
8. Multiple upload files
9. Media library
10. Media library
