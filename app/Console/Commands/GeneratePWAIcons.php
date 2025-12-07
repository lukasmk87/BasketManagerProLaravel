<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GeneratePWAIcons extends Command
{
    protected $signature = 'pwa:generate-icons {--force : Overwrite existing files}';

    protected $description = 'Generate PWA icons, shortcuts, and screenshot placeholders';

    private array $iconSizes = [16, 32, 72, 96, 128, 144, 152, 192, 384, 512];

    private array $shortcuts = [
        'dashboard' => ['icon' => 'grid', 'color' => '#3b82f6'],
        'live-game' => ['icon' => 'play', 'color' => '#10b981'],
        'players' => ['icon' => 'users', 'color' => '#8b5cf6'],
        'training' => ['icon' => 'target', 'color' => '#f59e0b'],
        'gym' => ['icon' => 'building', 'color' => '#6366f1'],
        'bookings' => ['icon' => 'calendar', 'color' => '#ec4899'],
        'available' => ['icon' => 'clock', 'color' => '#14b8a6'],
    ];

    private array $screenshots = [
        'dashboard' => ['width' => 1280, 'height' => 720, 'label' => 'Dashboard'],
        'mobile-dashboard' => ['width' => 390, 'height' => 844, 'label' => 'Mobile Dashboard'],
        'game-scoring' => ['width' => 1280, 'height' => 720, 'label' => 'Live Game Scoring'],
        'player-stats' => ['width' => 390, 'height' => 844, 'label' => 'Player Statistics'],
    ];

    public function handle(): int
    {
        $this->info('Generating PWA assets...');

        $force = $this->option('force');

        // Ensure directories exist
        $this->ensureDirectories();

        // Generate main logo icons
        $this->generateLogoIcons($force);

        // Generate favicon.ico
        $this->generateFavicon($force);

        // Generate shortcut icons
        $this->generateShortcutIcons($force);

        // Generate screenshot placeholders
        $this->generateScreenshots($force);

        $this->newLine();
        $this->info('PWA assets generated successfully!');

        return Command::SUCCESS;
    }

    private function ensureDirectories(): void
    {
        $dirs = [
            public_path('images'),
            public_path('images/shortcuts'),
            public_path('images/screenshots'),
        ];

        foreach ($dirs as $dir) {
            if (! is_dir($dir)) {
                mkdir($dir, 0755, true);
                $this->line("Created directory: {$dir}");
            }
        }
    }

    private function generateLogoIcons(bool $force): void
    {
        $this->info('Generating logo icons...');

        foreach ($this->iconSizes as $size) {
            $path = public_path("images/logo-{$size}.png");

            if (file_exists($path) && ! $force) {
                $this->line("  Skipped: logo-{$size}.png (exists)");

                continue;
            }

            $image = $this->createBasketballIcon($size);
            imagepng($image, $path);
            imagedestroy($image);
            $this->line("  Created: logo-{$size}.png");
        }
    }

    private function createBasketballIcon(int $size): \GdImage
    {
        $image = imagecreatetruecolor($size, $size);
        imagesavealpha($image, true);

        // Colors
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        $bgDark = imagecolorallocate($image, 45, 55, 72);     // #2d3748
        $bgLight = imagecolorallocate($image, 26, 32, 44);    // #1a202c
        $ballOrange = imagecolorallocate($image, 249, 115, 22); // #f97316
        $ballDark = imagecolorallocate($image, 194, 65, 12);   // #c2410c
        $white = imagecolorallocate($image, 255, 255, 255);

        // Fill transparent
        imagefill($image, 0, 0, $transparent);

        $center = $size / 2;
        $bgRadius = (int) ($size / 2);
        $ballRadius = (int) ($size * 0.35);
        $lineWidth = max(1, (int) ($size * 0.012));

        // Draw background circle
        imagefilledellipse($image, (int) $center, (int) $center, $bgRadius * 2, $bgRadius * 2, $bgDark);

        // Draw basketball
        imagefilledellipse($image, (int) $center, (int) $center, $ballRadius * 2, $ballRadius * 2, $ballOrange);

        // Draw basketball outline
        imagesetthickness($image, $lineWidth);
        imageellipse($image, (int) $center, (int) $center, $ballRadius * 2, $ballRadius * 2, $ballDark);

        // Draw basketball lines
        // Vertical line
        imageline($image, (int) $center, (int) ($center - $ballRadius), (int) $center, (int) ($center + $ballRadius), $ballDark);

        // Horizontal line
        imageline($image, (int) ($center - $ballRadius), (int) $center, (int) ($center + $ballRadius), (int) $center, $ballDark);

        // Curved lines (simplified as arcs)
        $curveOffset = $ballRadius * 0.45;
        imagearc($image, (int) ($center - $curveOffset), (int) $center, $ballRadius, $ballRadius * 2, 270, 90, $ballDark);
        imagearc($image, (int) ($center + $curveOffset), (int) $center, $ballRadius, $ballRadius * 2, 90, 270, $ballDark);

        // Add highlight
        if ($size >= 64) {
            $highlightX = (int) ($center - $ballRadius * 0.3);
            $highlightY = (int) ($center - $ballRadius * 0.4);
            $highlightW = (int) ($ballRadius * 0.4);
            $highlightH = (int) ($ballRadius * 0.25);
            $highlight = imagecolorallocatealpha($image, 255, 255, 255, 100);
            imagefilledellipse($image, $highlightX, $highlightY, $highlightW, $highlightH, $highlight);
        }

        return $image;
    }

    private function generateFavicon(bool $force): void
    {
        $path = public_path('favicon.ico');

        if (file_exists($path) && filesize($path) > 0 && ! $force) {
            $this->line('  Skipped: favicon.ico (exists)');

            return;
        }

        $this->info('Generating favicon.ico...');

        // Create a simple 32x32 favicon PNG (browsers accept PNG favicons)
        $icon32 = $this->createBasketballIcon(32);
        $icon16 = $this->createBasketballIcon(16);

        // For simplicity, we'll create a PNG favicon (widely supported)
        // True ICO format would require additional libraries
        imagepng($icon32, $path);
        imagedestroy($icon32);
        imagedestroy($icon16);

        $this->line('  Created: favicon.ico (PNG format)');
    }

    private function generateShortcutIcons(bool $force): void
    {
        $this->info('Generating shortcut icons...');

        foreach ($this->shortcuts as $name => $config) {
            $path = public_path("images/shortcuts/{$name}.png");

            if (file_exists($path) && ! $force) {
                $this->line("  Skipped: shortcuts/{$name}.png (exists)");

                continue;
            }

            $image = $this->createShortcutIcon(96, $config['icon'], $config['color']);
            imagepng($image, $path);
            imagedestroy($image);
            $this->line("  Created: shortcuts/{$name}.png");
        }
    }

    private function createShortcutIcon(int $size, string $iconType, string $hexColor): \GdImage
    {
        $image = imagecreatetruecolor($size, $size);
        imagesavealpha($image, true);

        // Parse hex color
        $r = hexdec(substr($hexColor, 1, 2));
        $g = hexdec(substr($hexColor, 3, 2));
        $b = hexdec(substr($hexColor, 5, 2));

        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        $bgColor = imagecolorallocate($image, $r, $g, $b);
        $white = imagecolorallocate($image, 255, 255, 255);

        imagefill($image, 0, 0, $transparent);

        $center = $size / 2;
        $radius = $size / 2 - 4;

        // Draw rounded background
        imagefilledellipse($image, (int) $center, (int) $center, (int) ($radius * 2), (int) ($radius * 2), $bgColor);

        // Draw icon based on type
        imagesetthickness($image, max(2, (int) ($size * 0.04)));

        switch ($iconType) {
            case 'grid':
                $this->drawGridIcon($image, $center, $size, $white);
                break;
            case 'play':
                $this->drawPlayIcon($image, $center, $size, $white);
                break;
            case 'users':
                $this->drawUsersIcon($image, $center, $size, $white);
                break;
            case 'target':
                $this->drawTargetIcon($image, $center, $size, $white);
                break;
            case 'building':
                $this->drawBuildingIcon($image, $center, $size, $white);
                break;
            case 'calendar':
                $this->drawCalendarIcon($image, $center, $size, $white);
                break;
            case 'clock':
                $this->drawClockIcon($image, $center, $size, $white);
                break;
        }

        return $image;
    }

    private function drawGridIcon(\GdImage $image, float $center, int $size, int $color): void
    {
        $boxSize = $size * 0.18;
        $gap = $size * 0.08;
        $startX = $center - $boxSize - $gap / 2;
        $startY = $center - $boxSize - $gap / 2;

        for ($row = 0; $row < 2; $row++) {
            for ($col = 0; $col < 2; $col++) {
                $x = $startX + $col * ($boxSize + $gap);
                $y = $startY + $row * ($boxSize + $gap);
                imagefilledrectangle($image, (int) $x, (int) $y, (int) ($x + $boxSize), (int) ($y + $boxSize), $color);
            }
        }
    }

    private function drawPlayIcon(\GdImage $image, float $center, int $size, int $color): void
    {
        $triangleSize = $size * 0.3;
        $points = [
            (int) ($center - $triangleSize * 0.4), (int) ($center - $triangleSize),
            (int) ($center - $triangleSize * 0.4), (int) ($center + $triangleSize),
            (int) ($center + $triangleSize * 0.8), (int) $center,
        ];
        imagefilledpolygon($image, $points, $color);
    }

    private function drawUsersIcon(\GdImage $image, float $center, int $size, int $color): void
    {
        // Main person
        imagefilledellipse($image, (int) $center, (int) ($center - $size * 0.15), (int) ($size * 0.2), (int) ($size * 0.2), $color);
        imagefilledarc($image, (int) $center, (int) ($center + $size * 0.25), (int) ($size * 0.35), (int) ($size * 0.3), 180, 360, $color, IMG_ARC_PIE);

        // Second person (smaller, offset)
        imagefilledellipse($image, (int) ($center + $size * 0.2), (int) ($center - $size * 0.1), (int) ($size * 0.15), (int) ($size * 0.15), $color);
    }

    private function drawTargetIcon(\GdImage $image, float $center, int $size, int $color): void
    {
        $radii = [$size * 0.3, $size * 0.2, $size * 0.1];
        foreach ($radii as $radius) {
            imageellipse($image, (int) $center, (int) $center, (int) ($radius * 2), (int) ($radius * 2), $color);
        }
        imagefilledellipse($image, (int) $center, (int) $center, (int) ($size * 0.08), (int) ($size * 0.08), $color);
    }

    private function drawBuildingIcon(\GdImage $image, float $center, int $size, int $color): void
    {
        $width = $size * 0.4;
        $height = $size * 0.5;
        $x = $center - $width / 2;
        $y = $center - $height / 2;

        imagerectangle($image, (int) $x, (int) $y, (int) ($x + $width), (int) ($y + $height), $color);

        // Roof
        $roofPoints = [
            (int) ($x - $size * 0.05), (int) $y,
            (int) $center, (int) ($y - $size * 0.15),
            (int) ($x + $width + $size * 0.05), (int) $y,
        ];
        imagepolygon($image, $roofPoints, $color);

        // Door
        imagefilledrectangle($image, (int) ($center - $size * 0.06), (int) ($y + $height * 0.5), (int) ($center + $size * 0.06), (int) ($y + $height), $color);
    }

    private function drawCalendarIcon(\GdImage $image, float $center, int $size, int $color): void
    {
        $width = $size * 0.45;
        $height = $size * 0.4;
        $x = $center - $width / 2;
        $y = $center - $height / 2 + $size * 0.05;

        imagerectangle($image, (int) $x, (int) $y, (int) ($x + $width), (int) ($y + $height), $color);

        // Header line
        imageline($image, (int) $x, (int) ($y + $height * 0.25), (int) ($x + $width), (int) ($y + $height * 0.25), $color);

        // Hanging hooks
        imageline($image, (int) ($x + $width * 0.25), (int) ($y - $size * 0.08), (int) ($x + $width * 0.25), (int) ($y + $size * 0.03), $color);
        imageline($image, (int) ($x + $width * 0.75), (int) ($y - $size * 0.08), (int) ($x + $width * 0.75), (int) ($y + $size * 0.03), $color);
    }

    private function drawClockIcon(\GdImage $image, float $center, int $size, int $color): void
    {
        $radius = $size * 0.3;
        imageellipse($image, (int) $center, (int) $center, (int) ($radius * 2), (int) ($radius * 2), $color);

        // Hour hand
        imageline($image, (int) $center, (int) $center, (int) $center, (int) ($center - $radius * 0.5), $color);

        // Minute hand
        imageline($image, (int) $center, (int) $center, (int) ($center + $radius * 0.6), (int) ($center - $radius * 0.2), $color);

        // Center dot
        imagefilledellipse($image, (int) $center, (int) $center, (int) ($size * 0.06), (int) ($size * 0.06), $color);
    }

    private function generateScreenshots(bool $force): void
    {
        $this->info('Generating screenshot placeholders...');

        foreach ($this->screenshots as $name => $config) {
            $path = public_path("images/screenshots/{$name}.png");

            if (file_exists($path) && ! $force) {
                $this->line("  Skipped: screenshots/{$name}.png (exists)");

                continue;
            }

            $image = $this->createScreenshotPlaceholder($config['width'], $config['height'], $config['label']);
            imagepng($image, $path);
            imagedestroy($image);
            $this->line("  Created: screenshots/{$name}.png");
        }
    }

    private function createScreenshotPlaceholder(int $width, int $height, string $label): \GdImage
    {
        $image = imagecreatetruecolor($width, $height);

        // Colors matching the app theme
        $bgDark = imagecolorallocate($image, 26, 32, 44);      // #1a202c
        $bgMedium = imagecolorallocate($image, 45, 55, 72);    // #2d3748
        $orange = imagecolorallocate($image, 249, 115, 22);    // #f97316
        $white = imagecolorallocate($image, 255, 255, 255);
        $gray = imagecolorallocate($image, 160, 174, 192);     // #a0aec0

        // Fill background
        imagefill($image, 0, 0, $bgDark);

        // Draw header bar
        $headerHeight = (int) ($height * 0.08);
        imagefilledrectangle($image, 0, 0, $width, $headerHeight, $bgMedium);

        // Draw basketball icon in header
        $iconSize = (int) ($headerHeight * 0.6);
        $iconX = (int) ($width * 0.03);
        $iconY = (int) (($headerHeight - $iconSize) / 2);
        imagefilledellipse($image, $iconX + $iconSize / 2, $iconY + $iconSize / 2, $iconSize, $iconSize, $orange);

        // Draw "BasketManager Pro" text placeholder
        $textX = $iconX + $iconSize + 10;
        imagefilledrectangle($image, $textX, (int) ($headerHeight * 0.3), $textX + 150, (int) ($headerHeight * 0.6), $white);

        // Draw content area placeholders
        $contentY = $headerHeight + 20;
        $cardWidth = (int) (($width - 60) / 2);
        $cardHeight = (int) (($height - $headerHeight - 60) / 3);

        for ($row = 0; $row < 3; $row++) {
            for ($col = 0; $col < 2; $col++) {
                $x = 20 + $col * ($cardWidth + 20);
                $y = $contentY + $row * ($cardHeight + 10);
                $this->drawRoundedRect($image, $x, $y, $x + $cardWidth, $y + $cardHeight, 8, $bgMedium);
            }
        }

        // Draw label at bottom
        $labelY = $height - 40;
        $font = 5;
        $textWidth = imagefontwidth($font) * strlen($label);
        $textX = ($width - $textWidth) / 2;
        imagestring($image, $font, (int) $textX, $labelY, $label, $gray);

        // Draw "BasketManager Pro" branding
        $brandText = 'BasketManager Pro';
        $brandWidth = imagefontwidth($font) * strlen($brandText);
        imagestring($image, $font, (int) (($width - $brandWidth) / 2), $labelY + 20, $brandText, $orange);

        return $image;
    }

    private function drawRoundedRect(\GdImage $image, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        // Draw the main rectangle parts
        imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);

        // Draw the corner circles
        imagefilledellipse($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }
}
