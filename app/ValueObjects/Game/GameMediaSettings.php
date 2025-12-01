<?php

namespace App\ValueObjects\Game;

/**
 * Value Object für Medien-Einstellungen.
 *
 * Kapselt alle Medien- und Streaming-bezogenen Einstellungen eines Spiels.
 */
final class GameMediaSettings
{
    public function __construct(
        private readonly bool $isStreamed = false,
        private readonly ?string $streamUrl = null,
        private readonly ?array $mediaLinks = null,
        private readonly bool $allowRecording = false,
        private readonly bool $allowPhotos = true,
        private readonly bool $allowStreaming = false,
        private readonly bool $allowSpectators = true,
        private readonly bool $allowMedia = true,
    ) {}

    // ============================
    // FACTORY METHODS
    // ============================

    public static function create(): self
    {
        return new self();
    }

    public static function fromArray(array $data): self
    {
        return new self(
            isStreamed: $data['is_streamed'] ?? false,
            streamUrl: $data['stream_url'] ?? null,
            mediaLinks: $data['media_links'] ?? null,
            allowRecording: $data['allow_recording'] ?? false,
            allowPhotos: $data['allow_photos'] ?? true,
            allowStreaming: $data['allow_streaming'] ?? false,
            allowSpectators: $data['allow_spectators'] ?? true,
            allowMedia: $data['allow_media'] ?? true,
        );
    }

    public static function forLiveStream(string $streamUrl): self
    {
        return new self(
            isStreamed: true,
            streamUrl: $streamUrl,
            allowStreaming: true,
        );
    }

    public static function closedDoors(): self
    {
        return new self(
            isStreamed: false,
            allowRecording: false,
            allowPhotos: false,
            allowStreaming: false,
            allowSpectators: false,
            allowMedia: false,
        );
    }

    // ============================
    // ACCESSORS
    // ============================

    public function isStreamed(): bool
    {
        return $this->isStreamed;
    }

    public function streamUrl(): ?string
    {
        return $this->streamUrl;
    }

    public function mediaLinks(): ?array
    {
        return $this->mediaLinks;
    }

    public function allowRecording(): bool
    {
        return $this->allowRecording;
    }

    public function allowPhotos(): bool
    {
        return $this->allowPhotos;
    }

    public function allowStreaming(): bool
    {
        return $this->allowStreaming;
    }

    public function allowSpectators(): bool
    {
        return $this->allowSpectators;
    }

    public function allowMedia(): bool
    {
        return $this->allowMedia;
    }

    // ============================
    // CALCULATED PROPERTIES
    // ============================

    public function hasStreamUrl(): bool
    {
        return !empty($this->streamUrl);
    }

    public function hasMediaLinks(): bool
    {
        return !empty($this->mediaLinks);
    }

    public function mediaLinkCount(): int
    {
        return count($this->mediaLinks ?? []);
    }

    public function isOpenToPublic(): bool
    {
        return $this->allowSpectators;
    }

    public function isClosedDoors(): bool
    {
        return !$this->allowSpectators && !$this->allowMedia;
    }

    public function canBeRecorded(): bool
    {
        return $this->allowRecording || $this->allowStreaming;
    }

    public function getMediaLink(string $type): ?string
    {
        if (!$this->mediaLinks) {
            return null;
        }

        foreach ($this->mediaLinks as $link) {
            if (is_array($link) && ($link['type'] ?? null) === $type) {
                return $link['url'] ?? null;
            }
        }

        return null;
    }

    public function getYouTubeLink(): ?string
    {
        return $this->getMediaLink('youtube');
    }

    public function getTwitchLink(): ?string
    {
        return $this->getMediaLink('twitch');
    }

    public function getHighlightsLink(): ?string
    {
        return $this->getMediaLink('highlights');
    }

    // ============================
    // IMMUTABLE OPERATIONS
    // ============================

    public function withStream(string $streamUrl): self
    {
        return new self(
            true,
            $streamUrl,
            $this->mediaLinks,
            $this->allowRecording,
            $this->allowPhotos,
            true,
            $this->allowSpectators,
            $this->allowMedia,
        );
    }

    public function withMediaLink(array $link): self
    {
        $mediaLinks = $this->mediaLinks ?? [];
        $mediaLinks[] = $link;

        return new self(
            $this->isStreamed,
            $this->streamUrl,
            $mediaLinks,
            $this->allowRecording,
            $this->allowPhotos,
            $this->allowStreaming,
            $this->allowSpectators,
            $this->allowMedia,
        );
    }

    public function withRecordingAllowed(bool $allowed): self
    {
        return new self(
            $this->isStreamed,
            $this->streamUrl,
            $this->mediaLinks,
            $allowed,
            $this->allowPhotos,
            $this->allowStreaming,
            $this->allowSpectators,
            $this->allowMedia,
        );
    }

    public function withPhotosAllowed(bool $allowed): self
    {
        return new self(
            $this->isStreamed,
            $this->streamUrl,
            $this->mediaLinks,
            $this->allowRecording,
            $allowed,
            $this->allowStreaming,
            $this->allowSpectators,
            $this->allowMedia,
        );
    }

    public function withSpectatorsAllowed(bool $allowed): self
    {
        return new self(
            $this->isStreamed,
            $this->streamUrl,
            $this->mediaLinks,
            $this->allowRecording,
            $this->allowPhotos,
            $this->allowStreaming,
            $allowed,
            $this->allowMedia,
        );
    }

    public function withMediaAllowed(bool $allowed): self
    {
        return new self(
            $this->isStreamed,
            $this->streamUrl,
            $this->mediaLinks,
            $this->allowRecording,
            $this->allowPhotos,
            $this->allowStreaming,
            $this->allowSpectators,
            $allowed,
        );
    }

    // ============================
    // SERIALIZATION
    // ============================

    public function toArray(): array
    {
        return [
            'is_streamed' => $this->isStreamed,
            'stream_url' => $this->streamUrl,
            'media_links' => $this->mediaLinks,
            'allow_recording' => $this->allowRecording,
            'allow_photos' => $this->allowPhotos,
            'allow_streaming' => $this->allowStreaming,
            'allow_spectators' => $this->allowSpectators,
            'allow_media' => $this->allowMedia,
            'is_open_to_public' => $this->isOpenToPublic(),
            'is_closed_doors' => $this->isClosedDoors(),
        ];
    }
}
