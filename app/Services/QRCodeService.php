<?php

namespace App\Services;

use App\Models\TeamEmergencyAccess;
use App\Models\EmergencyContact;
use App\Models\Team;
use Illuminate\Support\Facades\Storage;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Intervention\Image\ImageManagerStatic as Image;

class QRCodeService
{
    private array $config;

    public function __construct()
    {
        $this->config = [
            'default_size' => 300,
            'high_quality_size' => 600,
            'print_size' => 800,
            'margin' => 2,
            'error_correction' => 'H', // High error correction for emergency situations
            'formats' => ['png', 'svg', 'pdf'],
            'logo_size_ratio' => 0.2, // Logo size as ratio of QR code size
        ];
    }

    public function generateEmergencyQR(TeamEmergencyAccess $access, array $options = []): array
    {
        $size = $options['size'] ?? $this->config['default_size'];
        $format = $options['format'] ?? 'png';
        $includeVenue = $options['include_venue'] ?? true;
        $includeLogo = $options['include_logo'] ?? false;

        $qrData = $this->buildEmergencyQRData($access, $includeVenue);
        $filename = $this->generateFilename($access, $format);

        $qrCode = $this->generateQRCode($qrData['url'], $size, $format);

        $filePath = $this->saveQRCode($qrCode, $filename, $format);

        // Add logo if requested
        if ($includeLogo && $format === 'png') {
            $filePath = $this->addLogoToQR($filePath, $size);
        }

        return [
            'file_path' => $filePath,
            'public_url' => Storage::url($filePath),
            'qr_data' => $qrData,
            'metadata' => [
                'size' => "{$size}x{$size}",
                'format' => $format,
                'error_correction' => $this->config['error_correction'],
                'generated_at' => now()->toISOString(),
                'expires_at' => $access->expires_at->toISOString(),
            ],
        ];
    }

    public function generateBatchQRCodes(array $accessKeys, array $options = []): array
    {
        $results = [];
        $formats = $options['formats'] ?? ['png'];
        
        foreach ($accessKeys as $accessId) {
            $access = TeamEmergencyAccess::find($accessId);
            if (!$access) {
                $results[$accessId] = ['success' => false, 'error' => 'Access key not found'];
                continue;
            }

            $accessResults = [];
            foreach ($formats as $format) {
                try {
                    $qrResult = $this->generateEmergencyQR($access, array_merge($options, ['format' => $format]));
                    $accessResults[$format] = $qrResult;
                } catch (\Exception $e) {
                    $accessResults[$format] = ['success' => false, 'error' => $e->getMessage()];
                }
            }

            $results[$accessId] = [
                'success' => true,
                'team_name' => $access->team->name,
                'formats' => $accessResults,
            ];
        }

        return $results;
    }

    public function generatePrintableQR(TeamEmergencyAccess $access, array $options = []): array
    {
        $includeContactList = $options['include_contact_list'] ?? true;
        $includeInstructions = $options['include_instructions'] ?? true;
        $paperSize = $options['paper_size'] ?? 'A4';

        // Generate high-quality QR code
        $qrResult = $this->generateEmergencyQR($access, [
            'size' => $this->config['print_size'],
            'format' => 'png',
            'include_logo' => true,
        ]);

        // Create printable PDF
        $pdfPath = $this->createPrintablePDF($access, $qrResult, [
            'include_contact_list' => $includeContactList,
            'include_instructions' => $includeInstructions,
            'paper_size' => $paperSize,
        ]);

        return [
            'qr_code' => $qrResult,
            'printable_pdf' => $pdfPath,
            'team_name' => $access->team->name,
            'generated_at' => now()->toISOString(),
        ];
    }

    public function generateContactQR(EmergencyContact $contact, array $options = []): array
    {
        $size = $options['size'] ?? $this->config['default_size'];
        $format = $options['format'] ?? 'png';

        $contactData = [
            'type' => 'emergency_contact',
            'contact_id' => $contact->id,
            'name' => $contact->contact_name ?? $contact->name,
            'phone' => $contact->display_phone_number,
            'relationship' => $contact->relationship,
            'player_name' => $contact->player?->full_name ?? $contact->user?->name,
            'generated_at' => now()->toISOString(),
        ];

        $qrCode = $this->generateQRCode(json_encode($contactData), $size, $format);

        $filename = "contact_qr_{$contact->id}_" . time() . ".{$format}";
        $filePath = $this->saveQRCode($qrCode, $filename, $format);

        return [
            'file_path' => $filePath,
            'public_url' => Storage::url($filePath),
            'contact_data' => $contactData,
            'metadata' => [
                'size' => "{$size}x{$size}",
                'format' => $format,
                'generated_at' => now()->toISOString(),
            ],
        ];
    }

    public function generateVenueQR(Team $team, array $venueInfo, array $options = []): array
    {
        $size = $options['size'] ?? $this->config['default_size'];
        $format = $options['format'] ?? 'png';

        $venueData = [
            'type' => 'venue_emergency',
            'team_id' => $team->id,
            'team_name' => $team->name,
            'venue_info' => $venueInfo,
            'emergency_numbers' => [
                'ambulance' => '112',
                'fire' => '112',
                'police' => '110',
            ],
            'generated_at' => now()->toISOString(),
        ];

        $qrCode = $this->generateQRCode(json_encode($venueData), $size, $format);

        $filename = "venue_qr_{$team->id}_" . time() . ".{$format}";
        $filePath = $this->saveQRCode($qrCode, $filename, $format);

        return [
            'file_path' => $filePath,
            'public_url' => Storage::url($filePath),
            'venue_data' => $venueData,
            'metadata' => [
                'size' => "{$size}x{$size}",
                'format' => $format,
                'generated_at' => now()->toISOString(),
            ],
        ];
    }

    public function customizeQRAppearance(string $qrCodePath, array $customization = []): string
    {
        $image = Image::make(storage_path("app/{$qrCodePath}"));
        
        // Apply color customization
        if (isset($customization['color'])) {
            $this->applyColorFilter($image, $customization['color']);
        }

        // Add border
        if (isset($customization['border'])) {
            $this->addBorder($image, $customization['border']);
        }

        // Add text label
        if (isset($customization['label'])) {
            $this->addLabel($image, $customization['label']);
        }

        // Save customized version
        $customizedPath = str_replace('.png', '_customized.png', $qrCodePath);
        $image->save(storage_path("app/{$customizedPath}"));

        return $customizedPath;
    }

    public function validateQRCode(string $qrData): bool
    {
        try {
            $decodedData = json_decode($qrData, true);
            
            if (!$decodedData) {
                return false;
            }

            // Check for required fields based on type
            switch ($decodedData['type'] ?? '') {
                case 'emergency_access':
                    return isset($decodedData['access_key']) && isset($decodedData['team_id']);
                case 'emergency_contact':
                    return isset($decodedData['contact_id']) && isset($decodedData['phone']);
                case 'venue_emergency':
                    return isset($decodedData['team_id']) && isset($decodedData['venue_info']);
                default:
                    return false;
            }
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getQRCodeAnalytics(TeamEmergencyAccess $access): array
    {
        return [
            'total_scans' => $access->current_uses,
            'last_scan' => $access->last_used_at,
            'scan_locations' => $this->getUniqueScanLocations($access),
            'scan_frequency' => $this->calculateScanFrequency($access),
            'expiry_status' => [
                'expires_at' => $access->expires_at,
                'days_remaining' => $access->days_until_expiry,
                'is_expired' => $access->is_expired,
            ],
        ];
    }

    public function cleanupOldQRCodes(int $daysOld = 30): int
    {
        $cutoffDate = now()->subDays($daysOld);
        $deletedCount = 0;

        // Get expired access keys
        $expiredAccess = TeamEmergencyAccess::where('expires_at', '<', $cutoffDate)
            ->whereNotNull('qr_code_filename')
            ->get();

        foreach ($expiredAccess as $access) {
            $qrPath = "public/emergency_qr/{$access->qr_code_filename}";
            
            if (Storage::exists($qrPath)) {
                Storage::delete($qrPath);
                $deletedCount++;
            }

            // Clear filename from database
            $access->update(['qr_code_filename' => null, 'qr_code_metadata' => null]);
        }

        return $deletedCount;
    }

    /**
     * Generate QR code using BaconQrCode 3.x
     *
     * @param string $data
     * @param int $size
     * @param string $format
     * @return string
     */
    private function generateQRCode(string $data, int $size, string $format): string
    {
        if ($format === 'svg') {
            $renderer = new ImageRenderer(
                new RendererStyle($size, $this->config['margin']),
                new SvgImageBackEnd()
            );
        } else {
            // Default to PNG using Imagick
            $renderer = new ImageRenderer(
                new RendererStyle($size, $this->config['margin']),
                new ImagickImageBackEnd()
            );
        }

        $writer = new Writer($renderer);
        return $writer->writeString($data);
    }

    private function buildEmergencyQRData(TeamEmergencyAccess $access, bool $includeVenue = true): array
    {
        $data = [
            'type' => 'emergency_access',
            'access_key' => $access->access_key,
            'team_id' => $access->team_id,
            'team_name' => $access->team->name,
            'club_name' => $access->team->club?->name,
            'url' => route('emergency.access.form', ['accessKey' => $access->access_key]),
            'expires_at' => $access->expires_at->toISOString(),
            'instructions' => $access->usage_instructions,
        ];

        if ($includeVenue && $access->venue_information) {
            $data['venue'] = $access->venue_information;
        }

        return $data;
    }

    private function generateFilename(TeamEmergencyAccess $access, string $format): string
    {
        return "emergency_qr_{$access->team_id}_{$access->id}_" . time() . ".{$format}";
    }

    private function saveQRCode(string $qrCode, string $filename, string $format): string
    {
        $directory = 'public/emergency_qr';
        $filePath = "{$directory}/{$filename}";

        Storage::put($filePath, $qrCode);

        return $filePath;
    }

    private function addLogoToQR(string $qrPath, int $size): string
    {
        $qrImage = Image::make(storage_path("app/{$qrPath}"));
        
        // Try to find club or team logo
        $logoPath = $this->findTeamLogo();
        if (!$logoPath) {
            return $qrPath; // Return original if no logo found
        }

        $logo = Image::make($logoPath);
        $logoSize = intval($size * $this->config['logo_size_ratio']);
        
        $logo->resize($logoSize, $logoSize);
        
        // Position logo in center
        $x = intval(($size - $logoSize) / 2);
        $y = intval(($size - $logoSize) / 2);
        
        $qrImage->insert($logo, 'top-left', $x, $y);
        $qrImage->save(storage_path("app/{$qrPath}"));

        return $qrPath;
    }

    private function createPrintablePDF(TeamEmergencyAccess $access, array $qrResult, array $options): string
    {
        // This would integrate with Laravel's PDF generation
        // For now, return placeholder path
        $pdfFilename = "printable_emergency_{$access->team_id}_" . time() . ".pdf";
        $pdfPath = "public/emergency_pdf/{$pdfFilename}";

        // Create PDF content (simplified for this example)
        $pdfContent = $this->generatePDFContent($access, $qrResult, $options);
        Storage::put($pdfPath, $pdfContent);

        return $pdfPath;
    }

    private function generatePDFContent(TeamEmergencyAccess $access, array $qrResult, array $options): string
    {
        // Simplified PDF content generation
        // In a real implementation, you'd use a PDF library
        return "Emergency QR Code for {$access->team->name} - Generated at " . now()->toDateTimeString();
    }

    private function findTeamLogo(): ?string
    {
        // Try to find team or club logo in storage
        $logoPath = storage_path('app/public/logos/emergency_logo.png');
        return file_exists($logoPath) ? $logoPath : null;
    }

    private function applyColorFilter($image, array $color): void
    {
        // Apply color filter to QR code
        if (isset($color['foreground'])) {
            $image->colorize($color['foreground']['r'] ?? 0, $color['foreground']['g'] ?? 0, $color['foreground']['b'] ?? 0);
        }
    }

    private function addBorder($image, array $border): void
    {
        $width = $border['width'] ?? 10;
        $color = $border['color'] ?? '#000000';
        
        $image->resizeCanvas($image->width() + ($width * 2), $image->height() + ($width * 2), 'center', false, $color);
    }

    private function addLabel($image, array $label): void
    {
        $text = $label['text'] ?? 'Emergency Access';
        $size = $label['size'] ?? 16;
        $color = $label['color'] ?? '#000000';
        
        $image->text($text, $image->width() / 2, $image->height() - 20, function($font) use ($size, $color) {
            $font->size($size);
            $font->color($color);
            $font->align('center');
        });
    }

    private function getUniqueScanLocations(TeamEmergencyAccess $access): array
    {
        $usageLog = $access->usage_log ?? [];
        $locations = [];

        foreach ($usageLog as $entry) {
            if (isset($entry['ip_address']) && !in_array($entry['ip_address'], $locations)) {
                $locations[] = $entry['ip_address'];
            }
        }

        return $locations;
    }

    private function calculateScanFrequency(TeamEmergencyAccess $access): array
    {
        $usageLog = $access->usage_log ?? [];
        $frequency = [];

        foreach ($usageLog as $entry) {
            if (isset($entry['timestamp'])) {
                $date = date('Y-m-d', strtotime($entry['timestamp']));
                $frequency[$date] = ($frequency[$date] ?? 0) + 1;
            }
        }

        return $frequency;
    }

    /**
     * Generate QR code for player registration invitation.
     *
     * @param \App\Models\PlayerRegistrationInvitation $invitation
     * @param array $options
     * @return array
     */
    public function generatePlayerRegistrationQR($invitation, array $options = []): array
    {
        $size = $options['size'] ?? $this->config['default_size'];
        $format = $options['format'] ?? 'png';
        $includeLogo = $options['include_logo'] ?? false;

        // Build registration URL
        $registrationUrl = route('public.player.register', ['token' => $invitation->invitation_token]);

        // Generate QR code
        $qrCode = $this->generateQRCode($registrationUrl, $size, $format);

        // Save QR code
        $filename = "player_registration_qr_{$invitation->club_id}_{$invitation->id}_" . time() . ".{$format}";
        $directory = config('player_registration.qr_storage_path', 'public/qr-codes/player-registrations');
        $filePath = "{$directory}/{$filename}";

        Storage::put($filePath, $qrCode);

        // Optionally add club logo
        if ($includeLogo && $format === 'png') {
            $filePath = $this->addClubLogoToQR($filePath, $size, $invitation->club_id);
        }

        return [
            'file_path' => $filePath,
            'public_url' => Storage::url($filePath),
            'registration_url' => $registrationUrl,
            'metadata' => [
                'size' => "{$size}x{$size}",
                'format' => $format,
                'error_correction' => $this->config['error_correction'],
                'generated_at' => now()->toISOString(),
                'invitation_id' => $invitation->id,
                'club_id' => $invitation->club_id,
            ],
        ];
    }

    /**
     * Add club logo to player registration QR code.
     *
     * @param string $qrPath
     * @param int $size
     * @param int $clubId
     * @return string
     */
    private function addClubLogoToQR(string $qrPath, int $size, int $clubId): string
    {
        try {
            $qrImage = Image::make(storage_path("app/{$qrPath}"));

            // Try to find club logo
            $logoPath = $this->findClubLogo($clubId);
            if (!$logoPath) {
                return $qrPath; // Return original if no logo found
            }

            $logo = Image::make($logoPath);
            $logoSize = intval($size * $this->config['logo_size_ratio']);

            $logo->resize($logoSize, $logoSize);

            // Position logo in center
            $x = intval(($size - $logoSize) / 2);
            $y = intval(($size - $logoSize) / 2);

            $qrImage->insert($logo, 'top-left', $x, $y);
            $qrImage->save(storage_path("app/{$qrPath}"));

            return $qrPath;
        } catch (\Exception $e) {
            // Return original path if logo addition fails
            return $qrPath;
        }
    }

    /**
     * Find club logo file.
     *
     * @param int $clubId
     * @return string|null
     */
    private function findClubLogo(int $clubId): ?string
    {
        // Try to find club logo in storage
        $logoPath = storage_path("app/public/logos/club_{$clubId}.png");
        if (file_exists($logoPath)) {
            return $logoPath;
        }

        // Fallback to default logo
        $defaultLogoPath = storage_path('app/public/logos/default_club_logo.png');
        return file_exists($defaultLogoPath) ? $defaultLogoPath : null;
    }
}