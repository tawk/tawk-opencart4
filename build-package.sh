#!/bin/sh
echo "Creating temporary directory"
rm -rf ./tmp;
mkdir -p ./tmp/admin/controller/module/tawkto;
mkdir -p ./tmp/catalog/controller/module/tawkto;

echo "Copying files"
cp README.md ./tmp;
cp install.json ./tmp;
cp -r admin ./tmp;
cp -r catalog ./tmp;
cp -r vendor ./tmp;
cp -r system ./tmp;

echo "Creating opencart 4 zip files"
$(cd ./tmp && zip -9 -rq tawkto.ocmod.zip admin catalog system vendor install.json README.md);

echo "Done!"
