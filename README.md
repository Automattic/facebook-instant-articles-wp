[![Build Status](https://travis-ci.org/Automattic/facebook-instant-articles-wp.svg?branch=master)](https://travis-ci.org/Automattic/facebook-instant-articles-wp)

# Instant Articles for WP

Enable [Instant Articles for Facebook](https://developers.facebook.com/docs/instant-articles) on your WordPress site.

## Plugin activity
[![Throughput Graph](https://graphs.waffle.io/Automattic/facebook-instant-articles-wp/throughput.svg)](https://waffle.io/Automattic/facebook-instant-articles-wp/metrics/throughput)

## Description

This plugin adds support for Instant Articles for Facebook, which is a new way for publishers to distribute fast, interactive stories on Facebook. Instant Articles are preloaded in the Facebook mobile app so they load instantly.

**Instant Articles for WP only works on sites with non-minimal readership. If you do not have an active Facebook page with  non-minimal readership your site will not be accepted to Instant Articles even if you configure this plugin correctly.**

With the plugin active, a new menu will be available for you to connect to your Facebook Page and start publishing your Instant Articles. You'll also see the status of each Instant Articles submission on the edit page of your posts.

A best effort is made to generate valid Instant Article markup from your posts' content/metadata and publish it to Facebook. The plugin knows how to transform your posts' markup from a set of rules which forms a mapping between elements in you *source markup* and the valid *Instant Article components*. We refer to this “glue” between the two as the ***Transformer Rules***.

Built-in to the plugin are many [pre-defined transformer rules](https://github.com/Automattic/facebook-instant-articles-wp/blob/master/rules-configuration.json) which aims to cover standard WordPress installations. If your WordPress content contains elements which are not covered by the built-in ruleset, you can define your own additional rules to extend or override the defaults in the Settings of this plugin, under: **Plugin Configuration** > **Publishing Settings** > **Custom transformer rules**.

## Feed submission to Facebook

Facebook has a review process where they verify that your site has non-minimal readership, your Facebook page is active, all Instant Articles are properly formatted, have content consistency with their mobile web counterparts, and adhere to their community standards and content policies. You will not be able to publish Instant Articles in Facebook until your feed has been approved. 

It's important to note that if you use meta fields to add extra text, images or videos to your Posts, Facebook will expect you to add these to your Instant Articles output too. This plugin includes hooks to help you do that.

[See Facebook's documentation for full details of the submission process.](https://developers.facebook.com/docs/instant-articles)

Facebook requires a minimum number of articles in your feed before they will review it. Once your feed has been approved, new posts will automatically be taken live on Instant Articles, and existing posts will be taken live once you update them.

## Installation

1. Run `composer install` on the root of the plugin folder. Make sure you have [Composer](https://github.com/composer/composer) installed.
2. Upload the folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress

## Frequently Asked Questions

**Why is there content from my post missing in the generated Instant Article?**

More likely than not, this is because there is markup in the body of your post that is not mapped to a recognized Instant Article component. On the “Edit Post” screen for your post, look for additional information about the *transformed* output shown within the **Facebook Instant Articles** module located at the bottom of the screen.

**Why doesn't my post appear in the list of Instant Articles in the publisher tools?**
Your posts are imported to your library when they are shared on Facebook for the first time.
 
Alternatively, you can trigger a manual scrape by pasting your URL on our [Share Debugger](http://developers.facebook.com/tools/debug)

Only Instant Articles with URLs in [domains you have claimed](https://developers.facebook.com/docs/instant-articles/guides/publishertools#connect) will show up in the Publishing Tools section.
**In the Instant Articles module for my post, what does the “This post was transformed into an Instant Article with some warnings” message mean?**

When transforming your post into an Instant Article, this plugin will show warnings when it encounters content which might not be valid when published to Facebook. When you see this message, it is recommended to resolve each warning individually.

**What does the “No rules defined for ____ in the context of  ____” warning mean?**

This plugin transforms your post into an Instant Article by matching markup in your content to one of the [components available](https://github.com/facebook/facebook-instant-articles-sdk-php/blob/master/docs/QuickStart.md#transformer-classes) in Instant Articles markup. Although the plugin contains many [built-in rules](https://github.com/Automattic/facebook-instant-articles-wp/blob/master/rules-configuration.json) to handle common cases, there may be markup in your post which is not recognized by these existing rules. In this case, you may be required to define some of your own rules. See below for more details about where and how.

**How do I define my own transformer rules so that content from my site is rendered appropriately in an Instant Article?**

Your custom rules can be defined in the Settings of this plugin, under: **Plugin Configuration** > **Publishing Settings** > **Custom transformer rules**. More detailed instructions about all the options available is documented in the [Custom Transformer Rules](https://github.com/facebook/facebook-instant-articles-sdk-php/blob/master/docs/QuickStart.md#custom-transformer-rules) section of the Facebook Instant Articles SDK.

**I know of a custom transformer rule which is pretty common in the community. How can it be included by default in the plugin?**

You can propose popular transformer rules to be included in the plugin by [suggesting it on GitHub](https://github.com/Automattic/facebook-instant-articles-wp/issues/new).

**How do I post articles to Instant Articles after plugin is installed?**

In order to import your posts to your Instant Articles library on Facebook you need to [Connect Your Site](https://developers.facebook.com/docs/instant-articles/guides/publishertools#connect) first. Then either share your posts on your page or use the [Sharing Debugger](https://developers.intern.facebook.com/tools/debug/sharing/) to scrape them. After you have 10 articles imported, you will be able to submit them for review.

**How do I change the feed slug/URL if I'm using the RSS integration?**

To change the feed slug, set the constant INSTANT_ARTICLES_SLUG to whatever you like. If you do, remember to flush the rewrite rules afterwards.
By default it is set to `instant-articles` which usually will give you a feed URL set to `/feed/instant-articles`

**How do I flush the rewrite rules after changing the feed slug?**

Usually simply visiting the permalinks settings page in the WordPress dashboard will do the trick (/wp-admin/options-permalink.php)

## Development Environment
You are more than welcome to help us to make this plugin even better!

### Setup SDK and WP to use the Github code
Most of development and debugging we use the master or any feature branch for the development of WP plugin. 
Sometimes it needs specific or newest version of the SDK to match all the new features that are release candidates to every new release of SDK and WP plugin.

The SDK is also under development @ GitHub: <https://github.com/facebook/facebook-instant-articles-sdk-php>

#### Pre-requisites
- Have PHP installed (you can install with homebrew)
- Have WebServer installed (you can use MAMP)
- Have WP installed (downloading from wordpress.org)

#### Setup
Clone both repositories into your developer folder (ex: ~/instant-articles).
```
git clone git@github.com:Automattic/facebook-instant-articles-wp.git
git clone git@github.com:facebook/facebook-instant-articles-sdk-php.git
```

Build both source folders:
```
cd facebook-instant-articles-sdk-php
composer install

cd ../facebook-instant-articles-wp
composer install
```

Now remove the build from your WordPress, so it will include the code you've just built.
```
rm -rf vendor/facebook/facebook-instant-articles-sdk-php
ln -s ~/facebook-instant-articles-sdk-php vendor/facebook/facebook-instant-articles-sdk-php
```

and now you can create a link inside your /wp-content/plugins to your folder 
```
ln -s ~/facebook-instant-articles-wp wp-content/plugins/facebook-instant-articles-wp
```
