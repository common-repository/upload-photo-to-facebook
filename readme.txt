=== Upload photo to Facebook ===
Contributors: starkinfo, sajid, Prasad Ramji
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=R2FTMBB7DKVLS
Tags: facebook, upload photo, wordpress
Requires at least: 3.0
Tested up to: 3.6.1
Stable tag: 1.0
License: GPLv2 or later

This plugin used to upload photo on your facebook wall using wordpress and curl.

== Description == 

This plugin used to upload photo on your facebook wall using wordpress and curl. We are using facebook graph api for uploading photo.

PS: You'll need a curl enabled on your server to achieve the target.

Visit the plugin official website [here](http://myplugin.hostoi.com/plugin/wordpress-upload-photo-to-facebook/ "Upload photo to facebook")

== Installation ==
1. Upload the plugin to your blog, Activate it, then enter your facebook app details.
2. To use the plugin - add shortcode [uptf] in your post/page. Or you can add if( function_exists('uploadphototofb') ) { uploadphototofb(); } in your page template.

== Frequently Asked Questions ==
= How do i upload large size image using this plugin? =
1. To upload large file using this plugin you have to change 'upload_max_filesize ' and 'post_max_size' in php.ini file.
2. Keep same size for 'upload_max_filesize' and 'post_max_size' in php.ini.

== Screenshots ==
1. facebook app details
2. facebook profile