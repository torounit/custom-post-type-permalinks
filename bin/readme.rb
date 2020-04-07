#!/usr/bin/env ruby
txtpath = File.join(File.dirname(__FILE__), "../readme.txt");
mdpath = File.join(File.dirname(__FILE__), "../readme.md");

badges = <<"EOS"
[![Latest Stable Version](https://poser.pugx.org/torounit/custom-post-type-permalinks/v/stable)](https://packagist.org/packages/torounit/custom-post-type-permalinks)
[![License](https://poser.pugx.org/torounit/custom-post-type-permalinks/license)](https://packagist.org/packages/torounit/custom-post-type-permalinks)
[![Downloads](https://img.shields.io/wordpress/plugin/dt/custom-post-type-permalinks.svg)](https://wordpress.org/plugins/custom-post-type-permalinks/)
[![Tested up](https://img.shields.io/wordpress/v/custom-post-type-permalinks.svg)](https://wordpress.org/plugins/custom-post-type-permalinks/)
[![wp.org rating](https://img.shields.io/wordpress/plugin/r/custom-post-type-permalinks.svg)](https://wordpress.org/plugins/custom-post-type-permalinks/)
[![Build Status](https://travis-ci.org/torounit/custom-post-type-permalinks.svg)](https://travis-ci.org/torounit/custom-post-type-permalinks)
[![Donation](https://img.shields.io/badge/bitcoin-donate-yellow.svg)](https://blockchain.info/ja/address/3HwkojX2pd9wc5kPFdXnDXMTNbgBmPRygX)
[![](https://ps.w.org/custom-post-type-permalinks/assets/banner-1544x500.png?rev=1044335)](https://wordpress.org/plugins/custom-post-type-permalinks/)
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
string.gsub!(/License:.*\r?\n?/, "")
string.gsub!(/## Description/, "\n"+badges+"\n"+"## Description")

File.write(mdpath, string);
