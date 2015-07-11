=== Wise Chat ===
Contributors: marcin.lawrowski
Donate link: http://kaine.pl/projects/wp-plugins/wise-chat/wise-chat-donate
Tags: chat, plugin, ajax, javascript, shortcode, social, widget, responsive
Requires at least: 3.6
Tested up to: 4.2
Stable tag: 1.7.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Advanced chat plugin for WordPress. It requires no server, supports multiple channels, bad words filtering, appearance settings, bans and more.

== Description ==

Wise Chat is a chat plugin that helps to build a social network and to increase user engagement of your website by providing possibility to exchange real time messages in chat rooms. The plugin is easy installable and extremely configurable. It also has a growing list of features and the constant support.

**[Try demo here](http://kaine.pl/projects/wp-plugins/wise-chat/wise-chat-demo)**

= List of features: =

* **Easy installation**: The chat can be installed using a convenient shortcode, widget or PHP function. Multiple chat installations can be embedded on the same page.

* **Mobile ready**: Fully responsive design and mobile ready interface.

* **Themes**: Use one of 3 preconfigured themes to quickly adjust the style of the chat.

* **No server required**: There is no need to set up, maintain or pay for a server.

* **Chat Rooms**: Let people post messages in dedicated chat rooms (called channels). Run multiple channels either simultaneously on the same page or on separate pages.

* **Anonymous Users**: Unregistered or anonymous users can be allowed to participate in the chat. Auto-generated name (configurable) is assigned to such user.

* **Registered Users**: Registered and logged-in users are properly recognized by the plugin.

* **Single sign-on**: No separate login to the chat is required. If an user is logged-in to your website, then the chat automatically logs her / him in.

* **Users limit in channels**: Limit the amount of users allowed to participate in each channel.

* **User settings**: Chat users have ability (configurable) to change their names or mute sounds.

* **Restrict access**: Option to permit access for registered and logged-in users only.

* **Flood Control**: Detect spammers and block them from posting messages in the chat.

* **Posting links and images**: Option to allow clickable links and images. Posted images are downloaded into Media Library.

* **Pictures from camera**: On mobile devices users can take a picture directly and post it with a message.

* **Notifications**: Notifications signalizing new messages. It can be a sound or a counter in browser's title.

* **Messages auto cleanup**: Auto cleanup messages older than defined amount of time.

* **Localization**: Provide a translation for your language. All texts in the chat plugin are translatable.

* **Appearance adjustments**: Adjust visual appearance to match your website by editing colors, showing / hidding chat elements, changing sort order of messages and positioning chat elements. All this can be done using settings page.

* **Chat Moderation**: Protect your chat by enabling swearing control using built-in bad words filter. It supports English and Polish languages.

* **Messages filtering**: Create rules that detect and replace specific words / patterns in posted messages. It is good for cutting links for example.

* **Messages history**: All recently published messages are available in input field for resending.

* **Chat Opening Hours / Days**: Define days and range of hours when the chat is open for users.

* **Bans**: Administrator can ban (IP based ban) users who do not follow the rules of the chat.

* **Auto-bans**: An option to automatically block an user if he / she exceeds the defined number of bad words.

* **Deleting chat messages**: Administrator can delete a message or clear entire history of any channel. This feature can be disabled.

* **Emoticons**: See nice smiles in messages by enabling emoticons support.

* **Custom CSS**: Custom CSS styles can be applied for each element of the chat.

* **Channels statistics**: Observe various statistics of channels, including amount of posted messages or users online.

* **Detailed configuration page**: All features can be configured on the settings page (see the screenshots). You can control messages posting, appearance, channels statistics, filters, bans and localization.

* **Twitter hash tags**: Detecting Twitter hash tags and converting them into links.

* **List and counter of current users**: List of online users in sidebar of the chat. Display amount of online users.

* **Backups**: Backup all messages from given channel by downloading them as a single CSV file.

All settings are available on `Settings -> Wise Chat Settings` page. 

**See screenshots for detailed features.**

== Installation ==

1. Upload the entire `wise-chat` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Place a shortcode `[wise-chat]` in your posts or pages.
1. Alternatively install it in your templates via `<?php if (function_exists('wise_chat')) { wise_chat(); } ?>` code.
1. Alternatively install it using dedicated widget in `Appearance -> Widgets`, it's called `Wise Chat Window`.

= Requirements: =
* jQuery JS library (available in most themes)

= Optional requirements: =
* mbstring PHP extension (in order to use all features of bad words filter)
* Lightbox 2 library (for showing images on the nice looking popup)

= Post Installation Notices: =
* After installation go to Settings -> Wise Chat Settings page, select Localization tab and translate all the messages into your own language.
* Posting pictures from camera / local storage is limited to the specific range of Web browsers. See FAQ for details.

== Frequently Asked Questions ==

= How can I specify a channel to open? =

It can be done via the short code: 
`[wise-chat channel="my-channel"]`
or in PHP: 
`<?php if (function_exists('wise_chat')) { wise_chat('my-channel'); } ?>`
or in the widget: set desired channel name in Channel configuration field. The channel will be created during first usage.

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
messages_limit="30"
message_max_length="400"
allow_post_links="0"
allow_post_images="0"
enable_images_uploader="0"
enable_message_actions="0"
enable_twitter_hashtags="0"
theme=""
background_color=""
background_color_input=""
text_color=""
text_color_logged_user=""
chat_width="100%"
chat_height="200px"
show_user_name="1"
link_wp_user_name="1"
link_user_name_template=""
show_message_submit_button="1"
allow_change_user_name="1"
emoticons_enabled="1"
multiline_support="0"
show_users="1"
input_controls_location=""
messages_order="descending"
custom_styles=""
enable_title_notifications="0"
sound_notification=""
allow_mute_sound="0"
auto_clean_after=""
filter_bad_words="1"
enable_autoban="0"
autoban_threshold="3"
enable_flood_control="0"
flood_control_threshold="200"
flood_control_time_frame="1"
hint_message="Enter message here"
user_name_prefix="Anonymous"
message_submit_button_caption="Send"
message_save="Save"
message_name="Name"
message_customize="Customize"
message_sending="Sending ..."
message_error_1="Only letters, number, spaces, hyphens and underscores are allowed"
message_error_2="This name is already occupied"
message_error_3="You were banned from posting messages"
message_error_4="Only logged in users are allowed to enter the chat"
message_error_5="The chat is closed now"
message_error_6="The chat is full now. Try again later."
window_title="The Mighty Chat !"
message_mute_sounds="Mute sounds"
ajax_engine="lightweight"
messages_refresh_time="3000"
show_users_counter="1"
message_total_users="Total users"
channel_users_limit=""]`

= How to ban an user? =

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
or go to Settings -> Wise Chat Settings page, select Bans tab and then delete the desired ban from the list.

= How to get some information (e.g IP address) about an user? =

Log in as an administrator and type the command:
`/whois [UserName]`
where "UserName" is the choosen user's name.

= How can I use the messages history feature? =

Click on the message input field and use arrow keys (up and down) to scroll through the history of recently sent messages.

= How can I prevent from accessing the chat by anonymous users? =

Go to Settings -> Wise Chat Settings page, select General tab and select "Only For Logged In Users" option.

= How does auto-ban feature work? =

There is a counter for each user. Everytime an user uses a bad word in a message the counter is incremented. If it reaches the threshold (default - 3 abuses) the user is banned for 1 day. 

= How as an administrator can I delete single message from the channel? =

Go to Settings -> Wise Chat Settings, select Messages tab and enable "Enable Admin Actions" option. From now each message in each channel has its own delete button ("X" icon). The button appears only for logged in administrators. 

= How does "Enable Images" option actually work? =

If you enable "Enable Images" option every link posted in the chat which points to an image will be converted into image. The image will be downloaded into Media Library and then displayed on the chat window. Those downloaded images will be removed from Media Library together with the related chat messages (either when removing all messages or a single one). If an image cannot be downloaded the regular link is displayed instead. 

= Option "Enable Images" does not work. I see regular hyperlinks instead of images. What is wrong? =

The option requires a few prerequisites in order to operate correctly: GD and Curl extensions must be installed, Media Library must operate correctly, posted image link must have a valid extension (jpg, jpeg, gif, bmp, tiff or png), HTTP status code of the response must be equal 200, image cannot be larger than 2MB. Try to read PHP logs in case of problems. 

= What if I would like the images to be opened in a popup layer using Lightbox? =

By default all images open using Lightbox 2 library but only if the library is installed within current theme or a dedicated plugin. Without Lightbox 2 each image opens in the new tab / window.

= I have installed a plugin containing Lightbox library and newly added messages are not displayed on the Lightbox layer. What is wrong?

If you installed a plugin that incorporates Lightbox library and it still doesn't work make sure that the plugin uses original Lightbox 2 Javascript library. The chat is compatible only with the original Lightbox 2 library. 

= Image uploader does not work. What is wrong? =

Uploading of images is supported in the following Web browsers: IE 10+, Firefox 31+, Chrome 31+, Safari 7+, Opera 27+, iOS Safari 7.1+, Android Browser 4.1+, Chrome For Android 41+.

= How can I replace specific phrase in every message posted by users? =

You can use filters feature. Go to Settings -> Wise Chat Settings, select Filters tab and add new filter. From now each occurence of the phrase will be replaced by desired text in every message that is posted to any chat channel. 

= Chat window is showing up but it does not work. I cannot receive or send messages. What is wrong? =

Ensure that jQuery library is installed in your theme. Wise Chat cannot operate without jQuery. 

= Wise Chat plugin is making a lot of long-running HTTP requests. How to improve the performance? =

Every 3 seconds the plugin checks for new messages using AJAX request. By default admin-ajax.php is used as a backend script and this script has poor performance. However, it is the most compatible solution. If you want to reduce server load try to change "AJAX Engine" property to "Lightweight AJAX". It can be set on Settings -> Wise Chat Settings page, select Advanced tab and then select "Lightweight AJAX" from the dropdown list. This option enables dedicated backend script that has a lot better performance. 

= Can I customize all CSS styles using "Custom CSS Styles" setting? =

Yes, you can. All CSS classes related to the chat begins with "wc" prefix. All those classes may be redefined using "Custom CSS Styles" setting. Valid CSS syntax is required. 

= How to backup messages posted in a channel? I would like to store all messages offline. =

Go to Settings -> Wise Chat Settings, select Channels tab and click "Backup" link next to the desired channel. All messages from the selected channel will be downloaded as a CSV file. You can open it using MS Excel, OpenOffice or any other editor that supports CSV format. 

= Are old messages being deleted from the chat window? =

By default - no, however, you can enable auto-cleaning messages feature. Go to Settings -> Wise Chat Settings, select Channels tab. In the field "Auto-clean Messages" type number of minutes. Messages older than given amount of minutes will be auto-deleted. 

= How can I limit the amount of users that are allowed to enter a channel? =

Go to Settings -> Wise Chat Settings, select Channels tab. In the field "Users Limit" type number of users that allow to participate in a channel.  

== Screenshots ==

01. Simple configuration
02. Pictures posting, links, customizations, users list
03. Themes comparison: Default, Light Gray, Cold Dark
04. Preview on mobile - Light Gray theme
05. Preview on mobile - Cold Dark theme
06. Settings page - compilation of all tabs

== Changelog ==

= 1.7 =
* Custom CSS styles setting
* Checking for new messages right after sending a message (the delay eliminated)
* Messages backup feature (for each channel)
* Messages auto-clean after defined time
* /whois command for getting details of users
* Option to choose location of the input controls (input field, submit button, customizations panel): bottom or top
* Option to choose messages order: newest on the top or on the bottom
* More intelligent auto-scrolling of messages (scrolls only if scrollbar is on the bottom)
* New messages notification in browser's title when window is hidden
* New messages notification by playing a sound
* Flood control feature
* Additional "displayname" variable in user name link templates, "username" fixed to truly show user's name
* Chat opening hours / days
* Counter of current users
* Limit of users per channel

= 1.6 =
* Three themes
* Twitter hash tags support
* Custom filters (filtering texts, hyperlinks, e-mails and phrases that match a regular expressions)
* Chat window title
* Advanced configuration (AJAX engine and refresh time)
* Lightweight AJAX engine

= 1.5 =
* Posting images (stored in Media Library)
* Images uploader - posting pictures from camera (in case of mobile devices) or from local storage (in case of desktop)
* List of users in the side bar
* Flexible messages list on small devices
* Option to moderate channels by removing single messages

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
