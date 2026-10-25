#!/usr/bin/env bash
#
# Rebuild the minified front-end assets after editing assets/front.css or
# assets/front.js. Needs Node (npx fetches esbuild on first run).
#
# Usage: bin/build.sh

set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/../assets"

npx --yes esbuild@0.24.0 front.js --minify --target=es2017 --outfile=front.min.js
npx --yes esbuild@0.24.0 front.css --minify --outfile=front.min.css

echo
gzip -c front.min.css | wc -c | awk '{ printf "front.min.css  %.1f KB gzipped\n", $1 / 1024 }'
gzip -c front.min.js | wc -c | awk '{ printf "front.min.js   %.1f KB gzipped\n", $1 / 1024 }'
