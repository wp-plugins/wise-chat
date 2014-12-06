=== Wise Chat ===
Contributors: marcin.lawrowski
Donate link: http://kaine.pl/projects/wp-plugins/wise-chat-donate
Tags: chat, plugin, ajax, javascript, shortcode, social
Requires at least: 3.6
Tested up to: 4.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Just another chat plugin for WordPress. It requires no server, supports multiple channels and bad words filtering.

== Description ==

The plugin displays customizable chat window on WordPress pages, posts or templates. 

= List of features: =
* multiple channels support
* multiple chats on the same page
* dedicated bad words filter (supports English and Polish language)
* flexible cofiguration page
* colors adjustment
* no server required
* blocking IP from posting messages (bans)
* anonymous users (temporary user name)
* logged users (WordPress users name)

Settings page is available on `Settings -> Wise Chat Settings` page.

== Installation ==

1. Upload the entire `wise-chat` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Place a shortcode `[wise-chat]` in your posts or pages.
1. Alternatively install it in your templates via `<?php if (function_exists('wise_chat')) { wise_chat(); } ?>` code.

In order to use all features of bad words filter you need to have `mbstring` PHP extension installed on your server. 

== Frequently Asked Questions ==

= How can I specify a channel to open? =

It can be done via short code: 
`[wise-chat channel="my-channel"]`
or in PHP: 
`<?php if (function_exists('wise_chat')) { wise_chat('my-channel'); } ?>`

= How does the bad words filter work? =

The plugin has its own implementation of bad words filtering mechanism. Currently it supports two languages: English and Polish. It is turned on by default. It detects not only simple words but also variations of words like: "H.a.c_ki.n_g" (assuming that "hacking" is a bad word).

= Are there other shortcode options? =

Yes, they are. Here is an example with full range of options:
`[wise-chat channel="my-channel" hint_message="Enter message here ..." background_color="#034796" background_color_input="#034796" text_color="#81d742"]`

= How to ban a user? =

Log in as an administrator and type:
`/ban [UserName] [Duration]`
where "UserName" is the choosen user's name and "Duration" is constructed as follows: 1m (a ban for 1 minute), 7m (a ban for 7 minutes), 1h (a ban for one hour), 2d (a ban for 2 days), etc. Notice: IP addresses are actually blocked. 

= How to list banned users? =

Log in as an administrator and type:
`/bans`

= How to remove a ban of an user? =

Log in as an administrator and type:
`/unban [IP address]`

== Screenshots ==

1. The chat after installation.
2. Multiple chats on the same page.
3. Customizatios.
4. Settings page.

== Changelog ==

= 1.0 =
* Initial version

== Upgrade Notice ==
