# Custom Post Type Permalinks

Contributors:      Toro_Unit, inc2734, ixkaito, keita_kobayashi, strategio  
Donate link:       https://www.paypal.me/torounit  
Tags:              permalink, url, link, address, custom post type  
Requires at least: 4.7  
Tested up to:      5.6  
Requires PHP:      5.6  
License:           GPLv2 or Later  
License URI:       http://www.gnu.org/licenses/gpl-2.0.txt  
Stable tag:        3.4.3


Edit the permalink of custom post type.

<!-- only:github/ -->
[![Latest Stable Version](https://img.shields.io/wordpress/plugin/v/custom-post-type-permalinks?style=for-the-badge)](https://wordpress.org/plugins/custom-post-type-permalinks/)
[![License](https://img.shields.io/github/license/torounit/custom-post-type-permalinks?style=for-the-badge)](https://github.com/torounit/custom-post-type-permalinks/blob/master/LICENSE)
[![Downloads](https://img.shields.io/wordpress/plugin/dt/custom-post-type-permalinks.svg?style=for-the-badge)](https://wordpress.org/plugins/custom-post-type-permalinks/)
[![Tested up](https://img.shields.io/wordpress/v/custom-post-type-permalinks.svg?style=for-the-badge)](https://wordpress.org/plugins/custom-post-type-permalinks/)
[![wp.org rating](https://img.shields.io/wordpress/plugin/r/custom-post-type-permalinks.svg?style=for-the-badge)](https://wordpress.org/plugins/custom-post-type-permalinks/)
[![Build Status](https://img.shields.io/github/workflow/status/torounit/custom-post-type-permalinks/Test?style=for-the-badge)](https://github.com/torounit/custom-post-type-permalinks/actions)
[![](https://ps.w.org/custom-post-type-permalinks/assets/banner-1544x500.png?rev=1044335)](https://wordpress.org/plugins/custom-post-type-permalinks/)
<!-- /only:github -->

## Description

Custom Post Type Permalinks allow you edit the permalink structure of custom post type.

Change custom taxonomy archive's permalink to "example.org/post_type/taxonomy_name/term_slug". Can disable this fix.

And support `wp_get_archives( 'post_type=foo' )` and post type date archive (ex. `example.com/post_type_slug/date/2010/01/01` ).

[This Plugin published on GitHub.](https://github.com/torounit/custom-post-type-permalinks)

Donation: Please send [My Wishlist](http://www.amazon.co.jp/registry/wishlist/COKSXS25MVQV) or [Paypal](https://www.paypal.me/torounit)


### Translators

* Japanese(ja) - [Toro_Unit](http://www.torounit.com/)
* French(fr_FR) - [Geoffrey Crofte](http://geoffrey.crofte.fr/)
* Russian(ru_RU) - [Olart](http://olart.ru), [Natali_Z](https://profiles.wordpress.org/natali_z)

### Also checkout

* [Simple Post Type Permalinks](https://wordpress.org/plugins/simple-post-type-permalinks/)


## Setting on Code

Example:

```php
register_post_type( 'foo',
	array(
		'public' => true,
		'has_archive' => true,
		'rewrite' => array(
			"with_front" => true
		),
		'cptp_permalink_structure' => '%post_id%'
	)
);
```

### Exclude specific post type

```php
add_filter(  'cptp_is_rewrite_supported_by_foo',  '__return_false' );

// or

add_filter(  'cptp_is_rewrite_supported', function ( $support , $post_type ) {
    if ( 'foo' === $post_type ) {
        return false;
    }
    return $support;
}, 10, 2);
```

## Installation

* Download the custom-post-type-permalinks.zip file to your computer.
* Unzip the file.
* Upload the `custom-post-type-permalinks` directory to your `/wp-content/plugins/` directory.
* Activate the plugin through the 'Plugins' menu in WordPress.

That's it. You can access the permalinks setting by going to *Settings -> Permalinks*.


## Screenshots

* screenshot-1.png

## Changelog

### 3.4.3
* Fix archive link bug fix.

### 3.4.2
* Tested WP 5.6.

### 3.4.1
* Fix readme.

### 3.4.0

* Tested 5.5 beta3
* WPML support: custom post type slug translation. ( Props @strategio )
* Add new filter `cptp_post_link_category` and `cptp_post_link_term` .
* Use Lowercase prefix for action and filter.

### 3.3.5

* Tested 5.4
* fix CPTP_Module_Permalink::post_type_link.

### 3.3.1

* Add disable option for date / author and post type archive.
* Bug fix for `parse_request`.

### 3.2.2

* Fix readme.txt

### 3.2.0

* Support only public post type.
* Add `CPTP_is_rewrite_supported_by_${post_type}` and `CPTP_is_rewrite_supported` filter.
* Remove post_type query wp_get_archives.

### 3.1.4

* Test for WordPress 4.9.
* PHPCS fix.

### 3.1.3

* Test for WordPress 4.8.
* Bug fix for attachment link.

### 3.1.1

* Bug fix in `CPTP_Module_Setting::upgrader_process_complete`.

### 3.1.0

* Add filter `CPTP_date_front`.
* Fix sort term by `wp_list_sort` .

### 3.0.0

* Admin notice on update plugin.
* Large bug fix.
* no_taxonomy_structure bug fix.
* Add default value for options.

### 2.2.0

* add `CPTP_Util::get_no_taxonomy_structure`.

### 2.1.3

* Set `no_taxonomy_structure` default `true`.

### 2.1.2

* `rewirte => false` post type support.

### 2.1.0

* Create rewrite rule on `registered_post_type` and `registered_taxonomy` action.
* Not create taxonomy rewrite rule when `rewrite` is `false`.

### 2.0.2

* pointer html bug fix.

### 2.0.0

* `add_rewrite_rules` on `wp_loaded` priority is changed 10 from 100. [fix issue #53](https://github.com/torounit/custom-post-type-permalinks/issues/53)
* Replace `wp_get_post_terms` by `get_the_terms`. [fix issue #55](https://github.com/torounit/custom-post-type-permalinks/issues/55)
* Fix bug `register_uninstall_hook` called twice on each page. [fix issue #56](https://github.com/torounit/custom-post-type-permalinks/issues/56)

### 1.5.4

* Fixed removed parent post problem.


### 1.5.3

* readme fix.

### 1.5.0

* Tested for 4.5.
* Add filter `CPTP_set_{$module_name}_module`.


### 1.4.0

* Fix Translation Problem.


### 1.3.1

* bugfix `wp_get_archives`.

### 1.3.0

* bugfix for polylang.

### 1.2.0

* Add filter `cptp_post_type_link_priority`, `cptp_term_link_priority`, `cptp_attachment_link_priority`.
* Add action `CPTP_registered_modules`.

### 1.1.0

* WPML Test. thanks [keita_kobayashi](https://profiles.wordpress.org/keita_kobayashi) !

### 1.0.5

* admin bug fix. thanks [ixkaito](https://profiles.wordpress.org/ixkaito) !
* Translation Update Thanks [Natali_Z](https://profiles.wordpress.org/natali_z) !

### 1.0.4

* option bug fix.

### 1.0.3

* add category rule, if only attached category to post type.

### 1.0.2

* category slug bug fix.

### 1.0.0

* Set Permalink enable `register_post_type`.
* Enable add post type query to taxonomy archives.
* Use Class Autoloader.
* Create Rewrite Rule on `wp_loaded` action.
* WordPress Code Format Fix.
* `CPTP_Module_Permalink` Bug Fix.
* Bug Fix.
* Use Semantic Versioning.
* Date Structure Fix.
* Use Category Base.

### 0.9.7

* Adding date slug only conflicting `%post_id%`.
* Change taxonomy link rewrite rule. Use `post_type`.
* Can change template include custom taxonomy.

### 0.9.6

* Category and author.
* French Transration. Thanks Geoffrey!
* Hierarchial Term Fix.

### 0.9.5.6

* Strict Standard Error Fix.

### 0.9.5.4

* archive link bug fix.
* Tested Up 3.9

### 0.9.5.3

* “/”bug fix.
* taxonomy tmplate bug fix.

### 0.9.5.2

* Archives Rewrite Fix.

### 0.9.5.1

* Admin Bug Fix.

### 0.9.5

* Big change plugin architecture.
* Show `has_archive`, `with_front`.

### 0.9.4

* Internal release.

### 0.9.3.3

* `has_archive` Bug Fix.
* Fixed a bug in the link, including the extension.

### 0.9.3.2

* `wp_get_archives` Bug Fix.

### 0.9.3.1

* Tested 3.6
* Bug Fix.


### 0.9.3

* Admin page fix.
* slngle pageing link fix.
* Add Russian translation.


### 0.9

* Add custom post type archive only `has_archive` is `true`.
* Change method name.
* Change hook custom post link.
* Use Slug in `wp_get_archive()`.
* Fix attachment link.


### 0.8.7

* Translate Bug Fix.

### 0.8.6

* Paging Bug Fix.
* Commnent Paging.
* Show pointer.
*

### 0.8.1

* Bug Fix.

### 0.7.9.1

* Support Comment permalink.
* Small change in setting page.
* Change default value.
* Bug Fix.

### 0.7.8

* Bug fix.


### 0.7.7

* Bug fix.

### 0.7.6

* Add parent's slug to hierarchical post type.


### 0.7.5

* Add ability to disable to change custom taxonomy archive's permalink.


### 0.7.4

* Bug fix taxonomy rewrite.


### 0.7.3

* Changed part for saving the data.

### 0.7.2

* Reweite bug fix.
* Prewview bug fix.

### 0.7.1

* Bug fix.

### 0.7

* Add `%{taxonomy}%` tag.
* A large number of Bug Fix.
* Change Setting Page. Use Setting API.


### 0.6.2

* Fix `%author%` tag.

### 0.6

* First release on wordpress.org
