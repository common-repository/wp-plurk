=== Plugin Name ===
Contributors: herdianf
Donate link: http://www.ferdianto.com/
Tags: post, plurk
Requires at least: 1.5
Tested up to: 2.7
Stable tag: trunk

Send a post to your plurk account everytime you publish a blog post.

== Description ==

WP-Plurk is a plugin to put a post to your [Plurk](http://plurk.com/ "Plurk") account everytime you publish a blog post.
All you have to do is setup this plugin, enter your username and plurk password and a post template.

= About Post Template =

The post template has a certain format
1. Plurk qualifier (verbs) must be in the first of the template closed with square bracket.
Examples:
`[shares]` or `[says]`

2. There are other tags, `title` and `url`, `title` will be replaced with your wordpress post title 
and `url` replaced with the permalink. Tags is encloded by double bracket.
Example:
`{{title}}` or `{{url}}`

So to post plurk using shares qualifier we can use template
`[shares] {{url}} {{title}}`


== Installation ==

1. Extract the wp-plurk.zip
2. Upload `wp-plurk` directory to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to `Settings` menu, select the `wp-plurk` menu
5. Enter your plurk username and password

== Frequently Asked Questions ==

= no FAQ yet =

== Screenshots ==

1. Setting Page

