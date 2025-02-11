#!/bin/bash

npm ci
npm run build

rm ./wpprobe.zip

git archive --output=wpprobe.zip HEAD
zip -ur wpprobe.zip dist vendor-prefixed
