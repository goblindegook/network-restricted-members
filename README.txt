=== Network Restricted Members ===
Contributors: goblindegook, log_oscon
Tags: access control, membership, multisite, network, user management, users
Requires at least: 4.0
Tested up to: 4.0
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Restrict user access to selected sites on open multisite networks.

== Description ==

If you have a private network where all sites are open to anyone with an account, but still want the ability to invite someone from outside and limit their access to a single site, then Network Restricted Members is for you.

This plugin was developed for our private [P2](http://p2theme.com) network, which is open to all company employees. However, we still wanted to be able to bring contractors and clients over without giving them access to everything on the network.

= Usage =

Network Restricted Members provides a user setting that allows multisite network administrators to restrict a user to the sites he or she is a member of.

1. As a super admin, navigate to the Users dashboard
1. Click 'Edit' on the user you wish to restrict
1. Check the option 'Restrict User Access'
1. Save your changes

From this point on, administrators will need to add this user to their sites before he or she is able to see them.

If you wish to lift the restrictions on a user, repeat the steps above but _uncheck_ the option box instead.

= Other plugins =

Network Restricted Members works best when combined with a network privacy plugin, such as one of the following:

* [More Privacy Options](https://wordpress.org/plugins/more-privacy-options/) by David Sader
* [Network Privacy](https://wordpress.org/plugins/network-privacy/) by Ron Rennick

== Installation ==

= Using the WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Search for 'wp cas server'
3. Click 'Install Now'
4. Activate the plugin on the Plugin dashboard

= Uploading in WordPress Dashboard =

1. Navigate to the 'Add New' in the plugins dashboard
2. Navigate to the 'Upload' area
3. Select `wp-cas-server.zip` from your computer
4. Click 'Install Now'
5. Activate the plugin in the Plugin dashboard

= Using FTP =

1. Download `wp-cas-server.zip`
2. Extract the `wp-cas-server` directory to your computer
3. Upload the `wp-cas-server` directory to the `/wp-content/plugins/` directory
4. Activate the plugin in the Plugin dashboard

== Changelog ==

= 0.1 =
* Initial release.

== Upgrade Notice ==

= 0.1 =
Initial release.
