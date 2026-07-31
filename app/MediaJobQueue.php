<?php

namespace App;

enum MediaJobQueue: string
{
    case ORCHESTRATE = 'orchestrate';
    case TRANSCODE_MEDIA = 'transcode-media';
    case CONVERT_SUBTITLE = 'convert-subtitle';
    case EXTRACT_SUBTITLES = 'extract-subtitles';
}
