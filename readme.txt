=== BoardGamePrices ===
Contributors: keanpedersen
Tags: thumbnail, boardgameprices, price, prices, boardgame, board, game, shortcode
Requires at least: 3.0
Tested up to: 5.7
Stable tag: 1.1.5

Short code for embedding the best price for board games from BoardGamePrices

== Description ==

This plugins adds two short codes [boardgameprice id=xxxx] and
[boardgamepricebox id=xxxx] for embedding the best price for purchasing a
specific board game. The information is taken from
https://boardgameprices.co.uk

You get the product ID from the URL of the product you would like to show
the price for. For instance
https://boardgameprices.co.uk/item/show/7595/caverna-the-cave-farmers
Here the ID is 7595.

Some options can be changes in the settings panel in the administration of
WordPress. Here you can change the currency, shipping destination
calculation and stock preference.

Attributes available are:
* id: A comma-separated list of product IDs to show. Will take the first for [boardgameprice] and will list them all for [boardgamepricebox]
* currency: An override of the currency setting.
* destination: An override of the destination setting.
* delivery: An override of the delivery setting.
* sort: An override of the sorting setting.

The plugin can be translated with the i18n part of WordPress.

== Installation ==

1. Upload `boardgameprices` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Insert shortcode [boardgameprice id=xxx] inside your content or [boardgamepricebox id=xxx] to get a nice box.


== Upgrade Notice ==

= 1.0.0 =
First release

= 1.0.1 =
Now uses object cache for API requests

= 1.0.2 =
Display Swedish currency as xx,xx kr.

= 1.0.3 =
Display Swedish currency as xx,xx kr

= 1.1.0 =
Adjust the settings in the new administration part.


== Changelog ==

= 1.0.0 =
* First release

= 1.0.1 =
* Now uses object cache for API requests

= 1.0.2 =
* Display Swedish currency as xx,xx kr.

= 1.0.3 =
* Swedish currency with just kr instead of kr.

= 1.1.0 =
* Uses new API from boardgameprices.co.uk
* Settings panel in the administration part of WordPress
* New shortcode: boardgamepricebox for a nice display of the price.
* Danish translation.

= 1.1.1 =
* Enabled caching

= 1.1.2 =
* Swedish translation

= 1.1.3 =
* Missing translation string added

= 1.1.4 =
* Updated to use https instead of http

= 1.1.5 =
* Updated design of box
* Added more countries
* Option for entering affiliate ID in settings