# Flowarr - Media Library File Transformation Automations made simple

I have a media server running on my steam deck, and it works well, but I had two problems:
- Transcoding media on-the-fly was a bit too much for the little guy to handle (especially if more than one stream was happening at a time)
- I was low on space and couldn't expand storage at the moment

I noticed the majority of my library was in h.264, and by transcoding to hevc, I could reduce disk space by like 60%.
I also noticed that I had a lot of .ass subtitle files, which wasn't something all clients could handle, causing streams that would otherwise
be direct streams to need to have their .ass subtitles in .srt transcoded.

So I needed a tool to let me fix these two problems easily. An automation tool that could:
- Transcode all existing and new files to my target format (hevc)
- Extract all subtitle streams from my videos and transform them all to .srt's.
- Pause all work whenever a stream starts on Jellyfin

I tried Unmanic, and it would work, but for some reason GPU passthrough was being weird and the workers would often just stall and fail randomly.
I tried Tdarr, but it was so scary and complex I uninstalled immediately.

This seemed like a simple problem. I didn't need an entire world of plugins and worker nodes and clusters of machines...

So I decided I'll just go ahead and try building it myself.

And so, Flowarr was born.

# Current State
Literally completely non-functional, still scaffolding out the architecture
# Features/Roadmap - Ordered by priority
- [ ] Multi-Directory support (Run certain jobs only on certain folders)
- [ ] Transcode Worker
- [ ] Extract Embedded Subtitles Worker
- [ ] Convert Subtitle to SRT Worker
- [ ] Simple and clean Admin UI
- [ ] GPU-Accelerated File Transformations
- [ ] Start/Stop Worker Webhook
- [ ] SMB mount storage support
- [ ] Customizable File Rules
- [ ] Filewatcher that detects changes/additions to watched folder and auto-runs transformations
 

# Quickstart
- Clone the repo
- ./vendor/bin/sail up -d
