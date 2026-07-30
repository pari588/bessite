#!/bin/bash
# Render the social template to PNG artboards.
#   ./render.sh
# Chromium comes from the Playwright cache; no node/python packages needed.
set -e
cd "$(dirname "$0")"
CHROME="$HOME/.cache/ms-playwright/chromium-1228/chrome-linux64/chrome"
[ -x "$CHROME" ] || { echo "chromium not found at $CHROME"; exit 1; }
mkdir -p out

shoot() {  # shoot <hash> <WxH> <outfile>
  "$CHROME" --headless --disable-gpu --no-sandbox --hide-scrollbars \
    --force-device-scale-factor=1 --virtual-time-budget=15000 \
    --window-size="$2" --screenshot="out/$3" \
    "file://$PWD/template.html#$1" 2>/dev/null
  identify -format "  $3  %wx%h  %b\n" "out/$3"
}

shoot post  1080,1350 monsoon-post.png
shoot story 1080,1920 monsoon-story.png

# JPEG copies — WhatsApp recompresses anyway, and these upload faster.
for f in monsoon-post monsoon-story; do
  convert "out/$f.png" -quality 92 -strip "out/$f.jpg"
  identify -format "  $f.jpg  %wx%h  %b\n" "out/$f.jpg"
done
