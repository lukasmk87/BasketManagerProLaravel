<?php

namespace App\Console\Commands;

use App\Models\PlayerRegistrationInvitation;
use App\Services\QRCodeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RegeneratePlayerInvitationQRCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invitations:regenerate-qr
                            {--all : Regenerate QR codes for all invitations}
                            {--missing : Only regenerate missing QR codes}
                            {--format=svg : Format to use (svg or png)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate QR codes for player registration invitations';

    protected QRCodeService $qrCodeService;

    /**
     * Create a new command instance.
     */
    public function __construct(QRCodeService $qrCodeService)
    {
        parent::__construct();
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $format = $this->option('format');
        $regenerateAll = $this->option('all');
        $missingOnly = $this->option('missing');

        if (!$regenerateAll && !$missingOnly) {
            $this->error('Please specify either --all or --missing option');
            return 1;
        }

        if (!in_array($format, ['svg', 'png'])) {
            $this->error('Invalid format. Use "svg" or "png"');
            return 1;
        }

        if ($format === 'png' && !extension_loaded('imagick')) {
            $this->warn('Imagick extension not available. Using SVG format instead.');
            $format = 'svg';
        }

        $this->info('Starting QR code regeneration...');
        $this->newLine();

        // Get invitations to process
        $query = PlayerRegistrationInvitation::query();

        if ($missingOnly) {
            $this->info('Mode: Only regenerating missing QR codes');
        } else {
            $this->info('Mode: Regenerating ALL QR codes');
        }

        $invitations = $query->get();
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        $this->withProgressBar($invitations, function ($invitation) use ($format, $missingOnly, &$processed, &$skipped, &$failed) {
            // Check if QR code exists (use public disk)
            $qrExists = $invitation->qr_code_path && Storage::disk('public')->exists($invitation->qr_code_path);

            if ($missingOnly && $qrExists) {
                $skipped++;
                return;
            }

            try {
                // Delete old QR code if exists (from both public and private disk for cleanup)
                if ($invitation->qr_code_path) {
                    if (Storage::disk('public')->exists($invitation->qr_code_path)) {
                        Storage::disk('public')->delete($invitation->qr_code_path);
                    }
                    // Also clean up from private disk if it exists there (old bug)
                    if (Storage::exists('public/' . $invitation->qr_code_path)) {
                        Storage::delete('public/' . $invitation->qr_code_path);
                    }
                }

                // Generate new QR code
                $qrResult = $this->qrCodeService->generatePlayerRegistrationQR($invitation, [
                    'size' => 300,
                    'format' => $format,
                ]);

                // Update invitation
                $invitation->update([
                    'qr_code_path' => $qrResult['file_path'],
                    'qr_code_metadata' => $qrResult['metadata'] ?? [],
                ]);

                $processed++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Failed to regenerate QR code for invitation {$invitation->id}: {$e->getMessage()}");
                $failed++;
            }
        });

        $this->newLine(2);
        $this->info('QR code regeneration complete!');
        $this->newLine();

        // Summary table
        $this->table(
            ['Status', 'Count'],
            [
                ['Processed', $processed],
                ['Skipped', $skipped],
                ['Failed', $failed],
                ['Total', $invitations->count()],
            ]
        );

        if ($failed > 0) {
            $this->warn('Some QR codes failed to regenerate. Check the logs for details.');
            return 1;
        }

        return 0;
    }
}
