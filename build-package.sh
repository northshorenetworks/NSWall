#!/bin/sh

cd components/$1

tar -czf ../../packages/$1.tgz *;

cd ../..

#cp packages/$1.tgz ../../public_html
