=== DealerSolutions.com.au Inventory Search ===
Contributors: DealerSolutions
Donate link: http://www.dealersolutions.com.au/
Tags: feeds
Requires at least: 3.8
Tested up to: 4.2.2
Stable Tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin embeds Dealer Solution's Inventory Search into your Wordpress Website

== Description ==

This plugin is designed for use by customers of DealerSolutions.com.au and enables them to embed an Inventory Search Page easily into their Wordpress site.

If you are not a customer of DealerSolutions.com.au, you are by all means welcome to the code, but it probably won't do a whole lot for you.

== Installation ==

1. If you downloaded a .zip file of this plugin, extract it to a local directory
1. Upload all files to your `/wp-content/plugins` directory
1. Activate the plugin through the 'Plugins' menu in Wordpress
1. There are a few configuration parameters you will need to set - read more about those below:

= -:: Configuration - Global Settings ::- =

**Search Domain**

This option should be set to your domain name excluding `http://www.` and any folders afterwards

= -:: Configuration - Advanced Options ::- =

**CSS Options**

1. `Enable External Inventory CSS` - means you will use your own CSS
1. `Disable External Inventory CSS` - means you will use the default styling

**Page Title Mode**
1. `Ignore Title (default option)` - means no replacements occur
1. `Replace Title (partial replacement)` - Keeps the wordpress blog name in the title tag
1. `Replace Title (complete replacement)` - Replaces the whole title tag
1. `Append Title` - Existing title is kept, additional title is added to the end
1. `Prepend Title` - Existing title is kept, additional title is added to the start

**Legacy URL Redirects**

1. `Disable Legacy Redirects` - (default option)
1. `Enable Legacy Redirects` - means `/view.php/alias/` will redirect to `/permalink/view/`

**Development Mode**

1. `Production` - http://www.inventorysearch.com.au/ (default option)
1. `Staging` - http://staging.inventorysearch.com.au/
1. `Development` - http://dev.inventorysearch.com.au/
1. `Localhost` - http://localhost/

**Development Folder**

Which folder to use for `localhost` development - Example: `inventory`

= -:: Per Page Configuration ::- =

On the plugin administration page you will see a list of pages which will appear in your Inventory Search feed.  There are a few configuration options you can set for each of them:

**Inventory Type**

1. `Disabled` - Page will not appear
1. `Dealership Stock` - Only Dealership stock will appear
1. `New Car Database` - Only New Car Stock will appear

**Content Mode**

1. `Replace page content` - Inventory Search will replace any other page content (default option)
1. `Display after page content` - Inventory Search will appear *after* any other page content
1. `Display before page content` - Inventory Search will appear *before* any other page content

== Frequently Asked Questions ==

= Why? =

Implementing Dealer Solutions Inventory Search into your automotive dealerships website should be easy!

== Screenshots ==

Will be forthcoming once we've got somewhere to put them

== Changelog ==

= 1.1 =
* Introduced a mechanism to control how the title of the page displays

= 1.0 =
* First Release

== Upgrade Notice ==

- Failure to apply this patch will result in plugin sef-destruct sequence initiation ... it will be as if the plugin were never installed at all o.0
