#!/bin/bash
rm -rf tawkto
mkdir tawkto

cd ../
composer run build:prod
composer run package
cp tmp/tawkto.ocmod.zip docker/tawkto