#!/usr/bin/env bash

set -e

if [ $# -lt 1 ]; then
	echo "usage: $0 <plugin-name>"
	exit 1
fi

name=$1

rm -rf distribution
rm ${name}.zip
