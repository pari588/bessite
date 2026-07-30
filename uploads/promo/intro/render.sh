#!/bin/bash
# Intro post is image-only — the words live in the Instagram caption, so there
# is no HTML artboard to screenshot. This conforms the two Nano Banana renders
# to Instagram's exact pixel sizes.
#
#   assets/hero.jpg        4:5  -> out/intro-post.jpg   1080x1350
#   assets/hero-story.jpg  9:16 -> out/intro-story.jpg  1080x1920
#
# JPEG only: Instagram rejects PNG on publish.
# (template.html is kept in this folder in case the text version is ever wanted
#  back; it is not used by this script.)
set -e
cd "$(dirname "$0")"
mkdir -p out

conform() {  # conform <src> <WxH> <outfile>
  convert "$1" \
    -resize "$2^" -gravity center -extent "$2" \
    -unsharp 0x0.6+0.5+0.02 \
    -quality 92 -strip -colorspace sRGB "out/$3"
  identify -format "  %f  %wx%h  %b\n" "out/$3"
}

conform assets/hero.jpg       1080x1350 intro-post.jpg
conform assets/hero-story.jpg 1080x1920 intro-story.jpg

# review thumbnails for the draft page
convert out/intro-post.jpg  -resize 520x -quality 82 out/thumb-post.jpg
convert out/intro-story.jpg -resize 520x -quality 82 out/thumb-story.jpg
