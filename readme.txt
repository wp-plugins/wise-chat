=== Wise Chat ===
Contributors: marcin.lawrowski
Donate link: http://kaine.pl/projects/wp-plugins/wise-chat-donate
Tags: chat, plugin, ajax, javascript, shortcode, social
Requires at least: 3.6
Tested up to: 4.1
Stable tag: 1.2.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Just another chat plugin for WordPress. It requires no server, supports multiple channels and bad words filtering.

== Description ==

The plugin displays customizable chat window on WordPress pages, posts or templates. 

= List of features: =
* multiple channels support
* multiple chats on the same page
* dedicated bad words filter (supports English and Polish language)
* flexible cofiguration page (general settings, appearance and bans control)
* colors adjustment
* no server required
* blocking IP address from posting messages (bans)
* anonymous users (temporary user name)
* logged users (WordPress users name)
* installation using widget or shortcode
* chat user settings (e.g. name changing)

Settings page is available on `Settings -> Wise Chat Settings` page.

== Installation ==

1. Upload the entire `wise-chat` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Place a shortcode `[wise-chat]` in your posts or pages.
1. Alternatively install it in your templates via `<?php if (function_exists('wise_chat')) { wise_chat(); } ?>` code.
1. Alternatively install it using dedicated widget in `Appearance -> Widgets`, it's called Wise Chat Window.

In order to use all features of bad words filter you need to have `mbstring` PHP extension installed on your server. 

== Frequently Asked Questions ==

= How can I specify a channel to open? =

It can be done via short code: 
`[wise-chat channel="my-channel"]`
or in PHP: 
`<?php if (function_exists('wise_chat')) { wise_chat('my-channel'); } ?>`

= How can I install chat using a widget? =

Go to Appearance -> Widget page, drag and drop "Wise Chat Window" widget on desired sidebar. Channel name can be also specified. 

= How does the bad words filter work? =

The plugin has its own implementation of bad words filtering mechanism. Currently it supports two languages: English and Polish. It is turned on by default. It detects not only simple words but also variations of words like: "H.a.c_ki.n_g" (assuming that "hacking" is a bad word).

= Are there other shortcode options? =

Yes, they are. Here is an example with full range of options:
`[wise-chat channel="my-channel" hint_message="Enter message here ..." background_color="#034796" background_color_input="#034796" text_color="#81d742"]`

= How to ban a user? =

Log in as an administrator and type:
`/ban [UserName] [Duration]`
where "UserName" is the choosen user's name and "Duration" is constructed as follows: 1m (a ban for 1 minute), 7m (a ban for 7 minutes), 1h (a ban for one hour), 2d (a ban for 2 days), etc. Notice: IP addresses are actually blocked. 

Alternatively you can go to Settings -> Wise Chat Settings and select tab Bans. Then you can add a ban. 

= How to list banned users? =

Log in as an administrator and type:
`/bans`
or go to Settings -> Wise Chat Settings and select tab Bans.

= How to remove a ban of an user? =

Log in as an administrator and type:
`/unban [IP address]`
or go to Settings -> Wise Chat Settings and select tab Bans and then delete desired ban from the list.

== Screenshots ==

1. The chat after installation.
2. Multiple chats on the same page.
3. Customizations.
4. User customizations.
5. General settings.
6. Appearance settings.
7. Bans control.

== Changelog ==

= 1.2 =
* Wise Chat widget
* Option to allow unlogged user change his/her name
* Option to show user's name
* Bans control on settings page
* Changing text color of a logged in user

= 1.1 =
* Minor rearrangements of the settings panel

= 1.0 =
* Initial version

== Upgrade Notice ==
