#!/bin/sh

# ex. sudo sh build-package.sh ns-www_common-1.0

cd components/$1

tar -czvf ../../packages/$1.tgz *;

cd ../..

cp packages/$1.tgz ../../public_html
echo "package built and moved to ../../public_html."
