#!/usr/bin/env ruby
txtpath = File.join(File.dirname(__FILE__), "../readme.txt");
mdpath = File.join(File.dirname(__FILE__), "../readme.md");

badges = <<"EOS"
[![Build Status](https://travis-ci.org/torounit/custom-post-type-permalinks.svg)](https://travis-ci.org/torounit/custom-post-type-permalinks)
[![](https://img.shields.io/wordpress/plugin/dt/custom-post-type-permalinks.svg)](https://wordpress.org/plugins/custom-post-type-permalinks/)
[![](https://img.shields.io/wordpress/v/custom-post-type-permalinks.svg)](https://wordpress.org/plugins/custom-post-type-permalinks/)
[![](https://img.shields.io/wordpress/plugin/r/custom-post-type-permalinks.svg)](https://wordpress.org/plugins/custom-post-type-permalinks/)

[![](http://www.torounit.com/wp-content/uploads/2011/11/banner-772x250.png)](https://wordpress.org/plugins/custom-post-type-permalinks/)
EOS

string = File.read(txtpath, :encoding => Encoding::UTF_8);

string.gsub!( /^===\s([^=]+)\s===/, '# \1')
string.gsub!(/^==\s([^=]+)\s==/, '## \1')
string.gsub!(/^=\s([^=]+)\s=\r?\n?\*/, '### \1'+"\n\n\*")
string.gsub!(/^=\s([^=]+)\s=/, '### \1')
string.gsub!(/Contributors:.*\r?\n?/, "")
string.gsub!(/Tags:.*\r?\n?/, "")
string.gsub!(/Requires at least:.*\r?\n?/, "")
string.gsub!(/Tested up to:.*\r?\n?/, "")
string.gsub!(/Stable tag:.*\r?\n?/, "")
string.gsub!(/## Description/, "\n"+badges+"\n"+"## Description")

File.write(mdpath, string);