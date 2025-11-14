<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;

class FileUploadController extends Controller
{
    public function __construct()
    {
        // Authorization handled by route middleware: role:admin|super_admin
        $this->middleware('auth');
    }

    /**
     * Upload an image for landing page content.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpg,jpeg,png,webp,gif|max:2048'
        ]);

        try {
            $user = auth()->user();
            $tenantId = $user->tenant_id ?? 'global';
            $file = $request->file('image');

            // Generate unique filename
            $filename = time() . '_' . uniqid() . '.' . $file->extension();
            $directory = "landing/{$tenantId}";
            $path = "{$directory}/{$filename}";

            // Create directory if it doesn't exist
            if (!Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->makeDirectory($directory);
            }

            // Load and resize image
            $image = Image::make($file);

            // Resize to max 1200px width while maintaining aspect ratio
            if ($image->width() > 1200) {
                $image->resize(1200, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
            }

            // Save original format
            Storage::disk('public')->put($path, $image->encode());

            // Also save as WebP for better compression
            $webpFilename = pathinfo($filename, PATHINFO_FILENAME) . '.webp';
            $webpPath = "{$directory}/{$webpFilename}";

            try {
                $webpImage = Image::make($file);
                if ($webpImage->width() > 1200) {
                    $webpImage->resize(1200, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }
                Storage::disk('public')->put($webpPath, $webpImage->encode('webp', 85));

                // Prefer WebP format
                $preferredUrl = Storage::url($webpPath);
                $preferredFilename = $webpFilename;
            } catch (\Exception $e) {
                // WebP conversion failed, use original
                Log::warning('WebP conversion failed: ' . $e->getMessage());
                $preferredUrl = Storage::url($path);
                $preferredFilename = $filename;
            }

            Log::info('Landing page image uploaded', [
                'tenant_id' => $tenantId,
                'filename' => $preferredFilename,
                'size' => Storage::disk('public')->size($webpPath ?? $path)
            ]);

            return response()->json([
                'success' => true,
                'url' => $preferredUrl,
                'fallback_url' => Storage::url($path),
                'filename' => $preferredFilename,
                'size' => Storage::disk('public')->size($webpPath ?? $path),
            ]);
        } catch (\Exception $e) {
            Log::error('Image upload failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Upload fehlgeschlagen. Bitte versuchen Sie es erneut.'
            ], 500);
        }
    }

    /**
     * Delete an uploaded image.
     *
     * @param Request $request
     * @param string $filename
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteImage(Request $request, string $filename)
    {
        try {
            $user = auth()->user();
            $tenantId = $user->tenant_id ?? 'global';

            // Security: Ensure user can only delete their tenant's images
            $path = "landing/{$tenantId}/{$filename}";

            if (!Storage::disk('public')->exists($path)) {
                return response()->json([
                    'success' => false,
                    'error' => 'File not found'
                ], 404);
            }

            // Delete the file
            Storage::disk('public')->delete($path);

            // Also delete original file if this was a WebP
            if (pathinfo($filename, PATHINFO_EXTENSION) === 'webp') {
                $originalFilename = pathinfo($filename, PATHINFO_FILENAME);
                $possibleOriginals = [
                    "landing/{$tenantId}/{$originalFilename}.jpg",
                    "landing/{$tenantId}/{$originalFilename}.jpeg",
                    "landing/{$tenantId}/{$originalFilename}.png",
                    "landing/{$tenantId}/{$originalFilename}.gif",
                ];

                foreach ($possibleOriginals as $originalPath) {
                    if (Storage::disk('public')->exists($originalPath)) {
                        Storage::disk('public')->delete($originalPath);
                        break;
                    }
                }
            }

            Log::info('Landing page image deleted', [
                'tenant_id' => $tenantId,
                'filename' => $filename
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Image deletion failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Löschen fehlgeschlagen'
            ], 500);
        }
    }
}
