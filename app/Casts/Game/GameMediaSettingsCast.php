<?php

namespace App\Casts\Game;

use App\ValueObjects\Game\GameMediaSettings;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast für GameMediaSettings Value Object.
 *
 * Ermöglicht automatische Konvertierung zwischen DB-Spalten und GameMediaSettings VO.
 */
class GameMediaSettingsCast implements CastsAttributes
{
    /**
     * Transform the attribute from the underlying model values.
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): GameMediaSettings
    {
        return GameMediaSettings::fromArray([
            'is_streamed' => (bool) ($attributes['is_streamed'] ?? false),
            'stream_url' => $attributes['stream_url'] ?? null,
            'media_links' => isset($attributes['media_links'])
                ? json_decode($attributes['media_links'], true)
                : null,
            'allow_recording' => (bool) ($attributes['allow_recording'] ?? false),
            'allow_photos' => (bool) ($attributes['allow_photos'] ?? true),
            'allow_streaming' => (bool) ($attributes['allow_streaming'] ?? false),
            'allow_spectators' => (bool) ($attributes['allow_spectators'] ?? true),
            'allow_media' => (bool) ($attributes['allow_media'] ?? true),
        ]);
    }

    /**
     * Transform the attribute to its underlying model values.
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if (!$value instanceof GameMediaSettings) {
            return [];
        }

        return [
            'is_streamed' => $value->isStreamed(),
            'stream_url' => $value->streamUrl(),
            'media_links' => $value->mediaLinks() ? json_encode($value->mediaLinks()) : null,
            'allow_recording' => $value->allowRecording(),
            'allow_photos' => $value->allowPhotos(),
            'allow_streaming' => $value->allowStreaming(),
            'allow_spectators' => $value->allowSpectators(),
            'allow_media' => $value->allowMedia(),
        ];
    }
}
