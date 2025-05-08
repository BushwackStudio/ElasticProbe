#!/bin/bash

npm ci
npm run build

rm ./elasticprobe.zip

git archive --output=elasticprobe.zip HEAD
zip -ur elasticprobe.zip dist vendor-prefixed
