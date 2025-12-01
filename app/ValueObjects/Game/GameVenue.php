<?php

namespace App\ValueObjects\Game;

/**
 * Value Object für Spielort.
 *
 * Kapselt alle Venue-bezogenen Daten eines Spiels.
 */
final class GameVenue
{
    public function __construct(
        private readonly ?string $name = null,
        private readonly ?string $address = null,
        private readonly ?string $code = null,
        private readonly ?int $attendance = null,
        private readonly ?int $capacity = null,
        private readonly ?string $weatherConditions = null,
        private readonly ?int $temperature = null,
        private readonly ?string $courtConditions = null,
    ) {}

    // ============================
    // FACTORY METHODS
    // ============================

    public static function create(
        ?string $name = null,
        ?string $address = null,
        ?string $code = null,
    ): self {
        return new self($name, $address, $code);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['venue'] ?? $data['name'] ?? null,
            address: $data['venue_address'] ?? $data['address'] ?? null,
            code: $data['venue_code'] ?? $data['code'] ?? null,
            attendance: $data['attendance'] ?? null,
            capacity: $data['capacity'] ?? null,
            weatherConditions: $data['weather_conditions'] ?? null,
            temperature: $data['temperature'] ?? null,
            courtConditions: $data['court_conditions'] ?? null,
        );
    }

    // ============================
    // ACCESSORS
    // ============================

    public function name(): ?string
    {
        return $this->name;
    }

    public function address(): ?string
    {
        return $this->address;
    }

    public function code(): ?string
    {
        return $this->code;
    }

    public function attendance(): ?int
    {
        return $this->attendance;
    }

    public function capacity(): ?int
    {
        return $this->capacity;
    }

    public function weatherConditions(): ?string
    {
        return $this->weatherConditions;
    }

    public function temperature(): ?int
    {
        return $this->temperature;
    }

    public function courtConditions(): ?string
    {
        return $this->courtConditions;
    }

    // ============================
    // CALCULATED PROPERTIES
    // ============================

    public function hasVenue(): bool
    {
        return $this->name !== null;
    }

    public function hasAddress(): bool
    {
        return $this->address !== null;
    }

    public function hasCode(): bool
    {
        return $this->code !== null;
    }

    public function attendancePercentage(): ?float
    {
        if (!$this->capacity || !$this->attendance) {
            return null;
        }

        return round(($this->attendance / $this->capacity) * 100, 1);
    }

    public function isSoldOut(): bool
    {
        if (!$this->capacity || !$this->attendance) {
            return false;
        }

        return $this->attendance >= $this->capacity;
    }

    public function availableSeats(): ?int
    {
        if (!$this->capacity) {
            return null;
        }

        return max(0, $this->capacity - ($this->attendance ?? 0));
    }

    public function formattedAttendance(): ?string
    {
        if (!$this->attendance) {
            return null;
        }

        $formatted = number_format($this->attendance, 0, ',', '.');

        if ($this->capacity) {
            $capacityFormatted = number_format($this->capacity, 0, ',', '.');
            return "{$formatted} / {$capacityFormatted}";
        }

        return $formatted;
    }

    public function formattedTemperature(): ?string
    {
        if ($this->temperature === null) {
            return null;
        }

        return "{$this->temperature}°C";
    }

    public function fullAddress(): ?string
    {
        if (!$this->name && !$this->address) {
            return null;
        }

        $parts = array_filter([$this->name, $this->address]);
        return implode(', ', $parts);
    }

    // ============================
    // IMMUTABLE OPERATIONS
    // ============================

    public function withAttendance(int $attendance): self
    {
        return new self(
            $this->name,
            $this->address,
            $this->code,
            $attendance,
            $this->capacity,
            $this->weatherConditions,
            $this->temperature,
            $this->courtConditions,
        );
    }

    public function withWeather(?string $conditions, ?int $temperature): self
    {
        return new self(
            $this->name,
            $this->address,
            $this->code,
            $this->attendance,
            $this->capacity,
            $conditions,
            $temperature,
            $this->courtConditions,
        );
    }

    public function withCourtConditions(?string $conditions): self
    {
        return new self(
            $this->name,
            $this->address,
            $this->code,
            $this->attendance,
            $this->capacity,
            $this->weatherConditions,
            $this->temperature,
            $conditions,
        );
    }

    // ============================
    // SERIALIZATION
    // ============================

    public function toArray(): array
    {
        return [
            'venue' => $this->name,
            'venue_address' => $this->address,
            'venue_code' => $this->code,
            'attendance' => $this->attendance,
            'capacity' => $this->capacity,
            'weather_conditions' => $this->weatherConditions,
            'temperature' => $this->temperature,
            'court_conditions' => $this->courtConditions,
            'attendance_percentage' => $this->attendancePercentage(),
            'available_seats' => $this->availableSeats(),
        ];
    }
}
