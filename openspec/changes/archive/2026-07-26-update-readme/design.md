## Context

The README still reflects the project's initial scaffolding phase — it says "completely non-functional" and has an all-unchecked roadmap. Every planned feature has been implemented: multi-directory library support, transcode worker, subtitle extraction/conversion, management UI, Jellyfin pause/resume, GPU acceleration, and frontend testing. New contributors have no accurate entry point.

## Goals / Non-Goals

**Goals:**
- Replace the stale README with an accurate, comprehensive project overview
- Document all implemented features as complete
- Add tech stack, architecture overview, development setup, and testing instructions
- Match the current contribution workflow (OpenSpec changes, Vitest + Pest testing)
- Maintain the existing "story-driven" tone in the introduction

**Non-Goals:**
- No code or configuration changes
- No new features or structural changes to the project
- No migration of README content to separate pages (stay as a single file)

## Decisions

- **Single-file README**: Keep everything in one README.md rather than splitting into separate docs/ files. The project is small enough that a single page suffices.
- **Story-driven intro preserved**: The personal narrative ("I have a media server running on my Steam Deck...") is the project's identity and stays, but the "non-functional" admission is replaced with a functional-state summary.
- **Table of contents for longer sections**: As the README grows, use anchor-linked TOC entries to keep it navigable.

## Risks / Trade-offs

- [Staleness] → The README will need periodic updates as features are added. Mitigate by making README updates part of the OpenSpec change workflow (include in task lists when relevant).
