#!/usr/bin/env bash
set -euo pipefail

DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# 1. Restore/overwrite test files from their .bak sources and embed dummy subtitles
for bak in "$DIR"/*.bak; do
    [ -e "$bak" ] || continue
    original="${bak%.bak}"
    cp "$bak" "$original"

    # Define temporary dummy subtitle file paths
    srt_file="$DIR/dummy.srt"
    ass_file="$DIR/dummy.ass"
    temp_output="$DIR/temp_muxed_$(basename "$original")"

    # Create dummy .srt content (English)
    cat << 'EOF' > "$srt_file"
1
00:00:01,000 --> 00:00:04,000
Dummy English SRT Subtitle

EOF

    # Create dummy .ass content (German)
    cat << 'EOF' > "$ass_file"
[Script Info]
ScriptType: v4.00+
[V4+ Styles]
Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding
Style: Default,Arial,20,&H00FFFFFF,&H000000FF,&H00000000,&H64000000,0,0,0,0,100,100,0,0,1,2,2,2,10,10,10,1
[Events]
Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text
Dialogue: 0,0:00:01.00,00:00:04.00,Default,,0,0,0,,Dummy German ASS Subtitle
EOF

    # Mux the subtitles into the media file using ffmpeg
    ffmpeg -y -i "$original" -i "$srt_file" -i "$ass_file" \
        -map 0:v? -map 0:a? -map 1:0 -map 2:0 \
        -c:v copy -c:a copy \
        -c:s:0 srt -metadata:s:s:0 language=eng -metadata:s:s:0 title="English SRT" \
        -c:s:1 ass -metadata:s:s:1 language=ger -metadata:s:s:1 title="German ASS" \
        "$temp_output" >/dev/null 2>&1

    mv "$temp_output" "$original"
    rm "$srt_file" "$ass_file"

    echo "Restored and embedded subtitles: $(basename "$original")"
done

# 2. Delete any generated files that do not have a .bak counterpart or aren't scripts/backups
for file in "$DIR"/*; do
    [ -e "$file" ] || continue

    filename=$(basename "$file")

    # Skip directories, the script itself, and .bak files
    if [ -d "$file" ] || [ "$filename" = "restore-test-media.sh" ] || [[ "$filename" == *.bak ]]; then
        continue
    fi

    # If a corresponding .bak file does not exist, delete it
    if [ ! -f "$DIR/$filename.bak" ]; then
        rm "$file"
        echo "Deleted generated file: $filename"
    fi
done

echo "Test media reset complete."
