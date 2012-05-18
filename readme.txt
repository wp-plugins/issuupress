=== issuupress ===
Contributors: pixeline 
Donate link:https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=J9X5B6JUVPBHN&lc=US&item_name=pixeline%20%2d%20Wordpress%20plugin&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHostedGuest
Tags: issuu,pdf,catalog,shortcode
Requires at least: 2.9.2
Tested up to: 3.4
Stable tag: trunk

Displays your Issuu catalog of PDF files in your wordpress posts/pages using a shortcode.
You can restrict the list to a specific tag.

== Description ==

Issuu.com is a great place to host your PDF magazines, but you'd rather keep your visitors on your site then send them over, right?

Issuupress fetches (via the Issuu API) a list of all your PDFs hosted on issuu.com and allows you to display that list on your blog via a simple shortcode.

You can optionally restrict the list by tag.

You will need credentials to access the issuu API: login to issuu and access <a href="http://issuu.com/services/api/" target="_blank" title="issuu api">http://issuu.com/services/api/</a> to find your own API key and key secret.

Please <a href="http://wordpress.org/extend/plugins/issuupress/">rate the plugin</a> if you like it.
<a href="http://www.pixeline.be">pixeline</a>

= Usage = 
Simply put the shortcode `[issuupress tag="YOUR_TAG" viewer="yes"]` where you would like the catalog to be, you can leave tag empty if you want the whole catalog.
Should you not want the viewer, only the list of files, you can set it to "no".

Issuupress doesn't have many options yet. I'm waiting for its users to tell me what they would like as options (possibilities are endless). So if you need something to be added to the plugin, say it via the forum, i'll be listening.

== Installation ==

Extract the zip file and just drop the contents in the wp-content/plugins/ directory of your WordPress installation and then activate the Plugin from Plugins page.

Then go to the IssuuPress Settings, enter your API key and API secret, set the cache value, you're done!

== Changelog ==

1. Initial release.

== Screenshots ==
1. Mockup of the Issuu viewer with the list of pdfs underneath, fetched via the Issuu API.


==Readme Generator== 

This Readme file was generated using <a href = 'http://sudarmuthu.com/wordpress/wp-readme'>wp-readme</a>, which generates readme files for WordPress Plugins.