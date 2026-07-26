# Jellyfin Integration

## Purpose

Receive Jellyfin webhook notifications for playback events and maintain an active streams counter that pauses transcoding work.

## ADDED Requirements

### Requirement: Webhook Receiver

The system SHALL accept POST requests from Jellyfin's Webhook plugin for playback start and stop events.

#### Scenario: Receive playback start notification
- **WHEN** Jellyfin sends a POST to `/webhooks/jellyfin` with event type `playback.start` and a valid token
- **THEN** the active streams counter increments by 1
- **THEN** a 200 response is returned

#### Scenario: Receive playback stop notification
- **WHEN** Jellyfin sends a POST to `/webhooks/jellyfin` with event type `playback.stop`
- **THEN** the active streams counter decrements by 1 (floor 0)
- **THEN** a 200 response is returned

#### Scenario: Invalid token
- **WHEN** the request lacks a valid `X-Flowarr-Token` header and token validation is enabled
- **THEN** a 401 response is returned
- **THEN** the counter is not modified

#### Scenario: Auth disabled
- **WHEN** `services.jellyfin.webhook_token` is null or empty
- **THEN** all webhook requests are accepted without validation

#### Scenario: Unknown event type
- **WHEN** the event type is not `playback.start` or `playback.stop`
- **THEN** a 200 response is returned
- **THEN** the counter is not modified

#### Scenario: Counter never goes negative
- **WHEN** decrement is called while the counter is 0
- **THEN** the counter stays at 0

### Requirement: Active Streams Counter

The system SHALL maintain a counter of active Jellyfin streams in Cache.

#### Scenario: Counter increments on start
- **WHEN** `playback.start` is received
- **THEN** `Cache::get('active_streams')` increases by 1

#### Scenario: Counter decrements on stop
- **WHEN** `playback.stop` is received
- **THEN** `Cache::get('active_streams')` decreases by 1

### Requirement: Configurable Webhook Token

The webhook token SHALL be configurable.

#### Scenario: Token configured
- **WHEN** `services.jellyfin.webhook_token` contains a non-empty string
- **THEN** the webhook endpoint validates the `X-Flowarr-Token` header against it
