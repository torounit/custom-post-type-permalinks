=== Custom Post Type Permalinks ===
Contributors: Toro_Unit
Tags: permalink,permalinks,custom post type,custom taxonomy,cms
Requires at least: 3.7
Tested up to: 3.8
Stable tag: 0.9.5.3

Lets you edit the permalink of custom post type.

== Description ==

Custom Post Type Permalinks lets you edit the permalink structure of custom post type.

Change custom taxonomy archive's permalink to "example.org/post_type/taxonomy_name/term_slug". Can disable this fix.

And support wp_get_archives( "post_type=foo" ).

[This Plugin published on GitHub.](https://github.com/torounit/custom-post-type-permalinks)

Donation: Please send amazon.co.jp Gift to donate[at]torounit.com.

= Translators =
* Japanese(ja) - [Toro_Unit](http://www.torounit.com/)
* Russian(ru_RU) - [Olart](http://olart.ru)


== Installation ==

* Download the custom-post-type-permalinks.zip file to your computer.
* Unzip the file.
* Upload the `custom-post-type-permalinks` directory to your `/wp-content/plugins/` directory.
* Activate the plugin through the 'Plugins' menu in WordPress.

That's it. You can access the permalinks setting by going to *Settings -> Permalinks*.


== Screenshots ==

* screenshot-1.png


== Changelog ==

= 0.9.5.2 =
* Archives Rewrite Fix.

= 0.9.5.1 =
* Admin Bug Fix.

= 0.9.5 =
* Big change plugin architecture.
* Show has_archive, with_front.

= 0.9.4 =
* Internal release.

= 0.9.3.3 =
* has_archive Bug Fix.
* Fixed a bug in the link, including the extension.

= 0.9.3.2 =
* wp_get_archives Bug Fix.

= 0.9.3.1 =
* Tested 3.6
* Bug Fix.


= 0.9.3 =
* Admin page fix.
* slngle pageing link fix.
* Add Russian translation.


= 0.9 =
* Add custom post type archive only has_archive->true
* Change method name.
* Change hook custom post link.
* Use Slug in wp_get_archive().
* Fix attachment link.


= 0.8.7 =
* Translate Bug Fix.

= 0.8.6 =
* Paging Bug Fix.
* Commnent Paging.
* Show pointer.
*

= 0.8.1 =
* Bug Fix.

= 0.7.9.1 =
* Support Comment permalink.
* Small change in setting page.
* Change default value.
* Bug Fix.

= 0.7.8 =
* Bug fix.


= 0.7.7 =
* Bug fix.

= 0.7.6 =
* Add parent's slug to hierarchical post type.


= 0.7.5 =
* Add ability to disable to change custom taxonomy archive's permalink.


= 0.7.4 =
* Bug fix taxonomy rewrite.


= 0.7.3 =
* Changed part for saving the data.

= 0.7.2 =
* Reweite bug fix.
* Prewview bug fix.

= 0.7.1 =
* Bug fix.

= 0.7 =
* Add %{taxonomy}% tag.
* A large number of Bug Fix.
* Change Setting Page. Use Setting API.


= 0.6.2 =
* Fix %author% tag.

= 0.6 =
* First release on wordpress.org
