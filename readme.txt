=== Wise Chat ===
Contributors: marcin.lawrowski
Donate link: http://kaine.pl/projects/wp-plugins/wise-chat-donate
Tags: chat, plugin, ajax, javascript, shortcode, social, widget, responsive
Requires at least: 3.6
Tested up to: 4.1
Stable tag: 1.4.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Just another chat plugin for WordPress. It requires no server, supports multiple channels, bad words filtering, appearance settings, bans and more.

== Description ==

The plugin displays a fully customizable chat window on WordPress pages, posts or templates. 

= List of features: =
* easy installation using widget, shortcode or function (PHP)
* fully responsive and mobile ready
* no server required
* multiple channels (chat rooms) support
* channels statistics (active users, published messages)
* emoticons support
* multiple chat instances on the same page
* language localization for end-users
* built-in bad words filter (supports English and Polish languages)
* flexible cofiguration page (general settings, messages posting control, appearance, channels statistics, bans control and localization)
* colors adjustments
* blocking IP addresses from posting messages (bans)
* auto-blocking IP addresses for abuses (auto-bans)
* anonymous users (temporary user name with configurable prefix)
* logged in users (WordPress user name)
* option to allow access for logged in users only
* chat user settings (e.g. name changing)
* messages history (recently published messages in input field)

All settings are available on `Settings -> Wise Chat Settings` page. See screenshots for detailed features. 

== Installation ==

1. Upload the entire `wise-chat` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Place a shortcode `[wise-chat]` in your posts or pages.
1. Alternatively install it in your templates via `<?php if (function_exists('wise_chat')) { wise_chat(); } ?>` code.
1. Alternatively install it using dedicated widget in `Appearance -> Widgets`, it's called `Wise Chat Window`.

In order to use all features of bad words filter you need to have `mbstring` PHP extension installed on your server. 

== Frequently Asked Questions ==

= How can I specify a channel to open? =

It can be done via the short code: 
`[wise-chat channel="my-channel"]`
or in PHP: 
`<?php if (function_exists('wise_chat')) { wise_chat('my-channel'); } ?>`
or in the widget: set desired channel name in Channel configuration field.

= How can I install the chat using the widget? =

Go to Appearance -> Widgets page, drag and drop "Wise Chat Window" widget on desired sidebar. Channel name for the widget can be specified as well. 

= How can I localize the chat for end-user? =

Go to Settings -> Wise Chat Settings page, select Localization tab and translate texts in each field into your own language.

= What about support for mobile devices? =

Wise Chat works on any mobile device that supports Javascript and cookies. The interface is responsive, but you should enable submit button in order an user could send a message. Go to Settings -> Wise Chat Settings page, select Appearance tab and select checkbox "Show Submit Button". 

= How does the bad words filter work? =

The plugin has its own implementation of bad words filtering mechanism. Currently it supports two languages: English and Polish. It is turned on by default. It detects not only simple words but also variations of words like: "H.a.c_ki.n_g" (assuming that "hacking" is a bad word).

= Are there other shortcode options? =

Yes, they are. Here is an example with full range of options:

`[wise-chat channel="my-channel" 
restrict_to_wp_users="0"
messages_limit="30"
message_max_length="400"
filter_bad_words="1"
allow_post_links="0"
background_color="#e5e5e5"
background_color_input="#e2e2e2"
text_color="#042393"
text_color_logged_user="#dd3333"
show_message_submit_button="1"
show_user_name="1"
link_wp_user_name="0"
allow_change_user_name="0"
hint_message="Enter message here"
user_name_prefix="Anonymous"
message_submit_button_caption="Send"
message_save="Save"
message_name="Name"
message_customize="Customize"
message_error_1="Only letters, number, spaces, hyphens and underscores are allowed"
message_error_2="This name is already occupied"
message_error_3="You were banned from posting messages"
message_error_4="Only logged in users are allowed to enter the chat"]`

= How to ban a user? =

Log in as an administrator and type the command:
`/ban [UserName] [Duration]`
where "UserName" is the choosen user's name and "Duration" is constructed as follows: 1m (a ban for 1 minute), 7m (a ban for 7 minutes), 1h (a ban for one hour), 2d (a ban for 2 days), etc. Notice: IP addresses are actually blocked. 

Alternatively you can go to Settings -> Wise Chat Settings page, select Bans tab, fill IP address and duration fields and finally click "Add Ban" button.

= How to get the list of banned users? =

Log in as an administrator and type the command:
`/bans`
or go to Settings -> Wise Chat Settings page and select Bans tab.

= How to remove a ban of an user? =

Log in as an administrator and type the command:
`/unban [IP address]`
or go to Settings -> Wise Chat Settings page, select Bans tab and then delete desired ban from the list.

= How can I use the messages history feature? =

Click on the message input field and use arrow keys (up and down) to scroll through the history of recently sent messages.

= How can I prevent from accessing the chat by anonymous users? =

Go to Settings -> Wise Chat Settings page, select General tab and select "Only For Logged In Users" option.

= How does auto-ban feature work? =

There is a counter for each user. Everytime an user uses a bad word in a message the counter is incremented. If it reaches the threshold (default - 3 abuses) the user is banned for 1 day. 

== Screenshots ==

1. The chat after installation.
2. Multiple chats on the same page.
3. Customizations.
4. Custom user name, WordPress user highlighted and submit button.
5. General settings.
6. Messages settings.
7. Appearance adjustments.
8. Channels statistics.
9. Bans control.
10. Localizations for end-user.

== Changelog ==

= 1.4 =
* Configurable width and height of the chat window
* Channels statistics on settings page, including message and active users counters
* User name to link conversion template
* Emoticons support
* Auto-ban feature
* Multiline messages support

= 1.3 =
* Messages history (using arrow keys) in input field
* Posting links in chat messages
* Linking WP user name to the author page
* Fixed bug with duplicated messages in chat window
* Message submit button
* Rearrangements of the settings page
* Language localization of end-user messages and texts
* Timezones support
* Access only for logged in WP users

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
