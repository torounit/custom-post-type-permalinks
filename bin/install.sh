#!/bin/sh

sleep 10
if [ $1 = 80 ]; then
  wp core install --url="http://localhost" --title="CPTP" --admin_user="admin" --admin_password="admin" --admin_email="admin@example.com" --path="/var/www/html"
else
  wp core install --url="http://localhost:$1" --title="CPTP" --admin_user="admin" --admin_password="admin" --admin_email="admin@example.com" --path="/var/www/html"
fi

wp plugin install wordpress-importer --activate
wp plugin activate custom-post-type-permalinks

curl https://raw.githubusercontent.com/WPTRT/theme-unit-test/master/themeunittestdata.wordpress.xml > /tmp/themeunittestdata.wordpress.xml
wp import /tmp/themeunittestdata.wordpress.xml --authors=create  --quiet

wp rewrite structure "/%postname%/"
wp option update posts_per_page 5
wp option update page_comments 1
wp option update comments_per_page 5
wp option update show_on_front page
wp option update page_on_front 701
wp option update page_for_posts 703
