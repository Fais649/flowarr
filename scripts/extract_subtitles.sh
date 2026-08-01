#!/bin/bash
set -euo pipefail

FILE_PATH="$1"
TARGET_VIDEO_PATH="${2:-$FILE_PATH}"
shift 2
SUPPORTED_CODECS=("$@")

FFPROBE_BIN="${FFPROBE_BIN:-ffprobe}"
FFMPEG_BIN="${FFMPEG_BIN:-ffmpeg}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
CONFIG_FILE="${CONFIG_FILE:-$SCRIPT_DIR/../config/languages.php}"

declare -A LANG_MAP
while IFS=',' read -r three_code two_code; do
    LANG_MAP["$three_code"]="$two_code"
done < <(php -r 'foreach (require $argv[1] as $k => $v) { echo "$k,$v\n"; }' "$CONFIG_FILE")

TARGET_BASENAME="${TARGET_VIDEO_PATH%.*}"

"$FFPROBE_BIN" -v error -select_streams s -show_entries stream=index,codec_name:stream_tags=language -of csv=p=0 "$FILE_PATH" | while IFS=',' read -r index codec language; do
  for supported in "${SUPPORTED_CODECS[@]}"; do
    if [ "$codec" = "$supported" ]; then
      code="${language:-und}"
      short_lang="${LANG_MAP[$code]:-$code}"
      target_filename="${TARGET_BASENAME}.${short_lang}.srt"
      temp_output="${FILE_PATH%.*}.${index}.tmp.srt"

      "$FFMPEG_BIN" -y -i "$FILE_PATH" -map "0:$index" -c:s srt "$temp_output" && mv "$temp_output" "$target_filename"
      break
    fi
  done
done
