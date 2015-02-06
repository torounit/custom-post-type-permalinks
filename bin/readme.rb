#!/usr/bin/env ruby
txtpath = File.join(File.dirname(__FILE__), "../readme.txt");
mdpath = File.join(File.dirname(__FILE__), "../readme.md");

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



File.write(mdpath, string);