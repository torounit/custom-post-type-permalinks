#!/usr/bin/env bash

set -ex

if [ $# -lt 1 ]; then
	echo "usage: $0 <plugin-name>"
	exit 1
fi

pluginname=$1

cd distribution
zip -r ../${pluginname}.zip ./
cd ../
rm -rf distribution
