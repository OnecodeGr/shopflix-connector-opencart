#!/usr/bin/env bash

rm onecode-shopflix.ocmod.zip;
zip -r onecode-shopflix.ocmod.zip install.xml README.md upload/ -x upload/composer.json \
-x upload/composer.lock -x upload/composer.phar upload/.gitignore;