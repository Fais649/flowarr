#!/bin/bash
FILE_PATH="$1"
REPLACE_ORIGINAL="${2:-false}"

TARGET_FILENAME="${FILE_PATH%.*}.srt"
TEMP_OUTPUT="${FILE_PATH%.*}.tmp.srt"
FFMPEG_BIN="${FFMPEG_BIN:-ffmpeg}"

"$FFMPEG_BIN" -y \
    -i "$FILE_PATH" \
    -c:s srt \
    "$TEMP_OUTPUT" || exit 1

if [ "$REPLACE_ORIGINAL" = "true" ]; then
    mv "$TEMP_OUTPUT" "$FILE_PATH"
else
    mv "$TEMP_OUTPUT" "$TARGET_FILENAME"
fi
