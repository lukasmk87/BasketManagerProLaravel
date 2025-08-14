<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * API Response Compression Middleware
 * 
 * Komprimiert API-Responses mit gzip/deflate für bessere Performance
 * Besonders wichtig für große JSON-Responses mit Basketball-Statistiken
 */
class ApiResponseCompressionMiddleware
{
    /**
     * Die minimale Größe (in Bytes) für Compression
     */
    private const MIN_COMPRESSION_SIZE = 1024; // 1KB

    /**
     * Unterstützte Compression-Algorithmen
     */
    private const SUPPORTED_ENCODINGS = [
        'br' => 'brotli',
        'gzip' => 'gzip', 
        'deflate' => 'deflate'
    ];

    /**
     * Content-Types die komprimiert werden sollen
     */
    private const COMPRESSIBLE_TYPES = [
        'application/json',
        'text/json',
        'application/xml',
        'text/xml',
        'text/plain',
        'text/html',
        'text/css',
        'application/javascript',
        'text/javascript'
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\JsonResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Nur für API-Routes komprimieren
        if (!$request->is('api/*')) {
            return $response;
        }

        // Prüfen ob Client Compression unterstützt
        $acceptEncoding = $request->header('Accept-Encoding', '');
        if (empty($acceptEncoding)) {
            return $response;
        }

        // Bestimme beste Encoding-Methode
        $encoding = $this->getBestEncoding($acceptEncoding);
        if (!$encoding) {
            return $response;
        }

        // Prüfen ob Response komprimierbar ist
        if (!$this->isCompressible($response)) {
            return $response;
        }

        // Response komprimieren
        return $this->compressResponse($response, $encoding);
    }

    /**
     * Bestimme die beste verfügbare Encoding-Methode
     *
     * @param string $acceptEncoding
     * @return string|null
     */
    private function getBestEncoding(string $acceptEncoding): ?string
    {
        $acceptedEncodings = $this->parseAcceptEncoding($acceptEncoding);
        
        // Sortiere nach Qualität (q-Werte)
        arsort($acceptedEncodings);

        foreach ($acceptedEncodings as $encoding => $quality) {
            if ($quality > 0 && isset(self::SUPPORTED_ENCODINGS[$encoding])) {
                // Prüfe ob Extension verfügbar ist
                if ($this->isEncodingAvailable($encoding)) {
                    return $encoding;
                }
            }
        }

        return null;
    }

    /**
     * Parse Accept-Encoding Header
     *
     * @param string $acceptEncoding
     * @return array
     */
    private function parseAcceptEncoding(string $acceptEncoding): array
    {
        $encodings = [];
        $parts = array_map('trim', explode(',', $acceptEncoding));

        foreach ($parts as $part) {
            $pieces = array_map('trim', explode(';', $part));
            $encoding = strtolower($pieces[0]);
            
            // Default quality ist 1.0
            $quality = 1.0;
            
            // Parse q-Wert wenn vorhanden
            foreach (array_slice($pieces, 1) as $param) {
                if (strpos($param, 'q=') === 0) {
                    $quality = (float) substr($param, 2);
                    break;
                }
            }
            
            $encodings[$encoding] = $quality;
        }

        return $encodings;
    }

    /**
     * Prüfe ob Encoding verfügbar ist
     *
     * @param string $encoding
     * @return bool
     */
    private function isEncodingAvailable(string $encoding): bool
    {
        switch ($encoding) {
            case 'br':
                // Brotli ist noch nicht weit verbreitet in PHP
                return false;
            case 'gzip':
                return function_exists('gzencode');
            case 'deflate':
                return function_exists('gzdeflate');
            default:
                return false;
        }
    }

    /**
     * Prüfe ob Response komprimiert werden kann
     *
     * @param mixed $response
     * @return bool
     */
    private function isCompressible($response): bool
    {
        // Nur HTTP 200 Responses komprimieren
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        // Content-Type prüfen
        $contentType = $response->headers->get('content-type', '');
        $isCompressibleType = false;

        foreach (self::COMPRESSIBLE_TYPES as $type) {
            if (strpos($contentType, $type) === 0) {
                $isCompressibleType = true;
                break;
            }
        }

        if (!$isCompressibleType) {
            return false;
        }

        // Bereits komprimierte Responses überspringen
        if ($response->headers->has('content-encoding')) {
            return false;
        }

        // Mindestgröße prüfen
        $content = $response->getContent();
        if (strlen($content) < self::MIN_COMPRESSION_SIZE) {
            return false;
        }

        return true;
    }

    /**
     * Komprimiere die Response
     *
     * @param mixed $response
     * @param string $encoding
     * @return mixed
     */
    private function compressResponse($response, string $encoding)
    {
        $originalContent = $response->getContent();
        $originalSize = strlen($originalContent);

        try {
            $compressedContent = $this->compress($originalContent, $encoding);
            $compressedSize = strlen($compressedContent);

            // Nur verwenden wenn Compression Sinn macht (mindestens 10% Einsparung)
            $compressionRatio = ($originalSize - $compressedSize) / $originalSize;
            if ($compressionRatio < 0.1) {
                return $response;
            }

            // Response Content und Headers aktualisieren
            $response->setContent($compressedContent);
            $response->headers->set('Content-Encoding', $encoding);
            $response->headers->set('Content-Length', $compressedSize);
            $response->headers->set('Vary', 'Accept-Encoding');

            // Debug-Info für Performance-Monitoring
            if (config('app.debug') || config('logging.channels.performance', false)) {
                $response->headers->set('X-Compression-Ratio', round($compressionRatio * 100, 2) . '%');
                $response->headers->set('X-Original-Size', $originalSize);
                $response->headers->set('X-Compressed-Size', $compressedSize);
            }

            // Performance-Logging
            Log::channel('performance')->info('API Response Compressed', [
                'endpoint' => request()->path(),
                'method' => request()->method(),
                'encoding' => $encoding,
                'original_size' => $originalSize,
                'compressed_size' => $compressedSize,
                'compression_ratio' => round($compressionRatio * 100, 2) . '%',
                'user_agent' => request()->userAgent()
            ]);

        } catch (\Exception $e) {
            // Bei Compression-Fehlern Original-Response zurückgeben
            Log::error('Response compression failed', [
                'endpoint' => request()->path(),
                'encoding' => $encoding,
                'error' => $e->getMessage()
            ]);
            
            return $response;
        }

        return $response;
    }

    /**
     * Komprimiere Content mit spezifischem Algorithmus
     *
     * @param string $content
     * @param string $encoding
     * @return string
     * @throws \Exception
     */
    private function compress(string $content, string $encoding): string
    {
        switch ($encoding) {
            case 'gzip':
                $compressed = gzencode($content, 6); // Compression Level 6 (Balance zwischen Geschwindigkeit und Größe)
                if ($compressed === false) {
                    throw new \Exception('GZIP compression failed');
                }
                return $compressed;

            case 'deflate':
                $compressed = gzdeflate($content, 6);
                if ($compressed === false) {
                    throw new \Exception('Deflate compression failed');
                }
                return $compressed;

            case 'br':
                // Brotli kompression - falls verfügbar
                if (function_exists('brotli_compress')) {
                    $compressed = brotli_compress($content, 6);
                    if ($compressed === false) {
                        throw new \Exception('Brotli compression failed');
                    }
                    return $compressed;
                }
                throw new \Exception('Brotli not available');

            default:
                throw new \Exception('Unsupported encoding: ' . $encoding);
        }
    }

    /**
     * Performance-Metriken für große Basketball-JSON-Responses
     * 
     * @param JsonResponse $response
     * @return array
     */
    public static function analyzeJsonResponse(JsonResponse $response): array
    {
        $content = $response->getContent();
        $data = json_decode($content, true);
        
        $metrics = [
            'total_size' => strlen($content),
            'json_depth' => self::calculateJsonDepth($data),
            'array_count' => self::countArrays($data),
            'object_count' => self::countObjects($data),
            'string_length_total' => self::calculateStringLength($data),
            'compression_potential' => self::estimateCompressionPotential($content)
        ];

        return $metrics;
    }

    /**
     * Berechne JSON-Tiefe (für Performance-Analysis)
     */
    private static function calculateJsonDepth($data, int $depth = 0): int
    {
        if (!is_array($data)) {
            return $depth;
        }

        $maxDepth = $depth;
        foreach ($data as $value) {
            if (is_array($value)) {
                $maxDepth = max($maxDepth, self::calculateJsonDepth($value, $depth + 1));
            }
        }

        return $maxDepth;
    }

    /**
     * Zähle Arrays in JSON-Struktur
     */
    private static function countArrays($data): int
    {
        $count = 0;
        
        if (is_array($data)) {
            $count = 1;
            foreach ($data as $value) {
                if (is_array($value)) {
                    $count += self::countArrays($value);
                }
            }
        }

        return $count;
    }

    /**
     * Zähle Objekte in JSON-Struktur
     */
    private static function countObjects($data): int
    {
        $count = 0;
        
        if (is_array($data)) {
            // Prüfe ob assoziatives Array (Objekt-artig)
            if (array_keys($data) !== range(0, count($data) - 1)) {
                $count = 1;
            }
            
            foreach ($data as $value) {
                if (is_array($value)) {
                    $count += self::countObjects($value);
                }
            }
        }

        return $count;
    }

    /**
     * Berechne Gesamtlänge aller Strings
     */
    private static function calculateStringLength($data): int
    {
        $length = 0;
        
        if (is_string($data)) {
            return strlen($data);
        }
        
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_string($key)) {
                    $length += strlen($key);
                }
                $length += self::calculateStringLength($value);
            }
        }

        return $length;
    }

    /**
     * Schätze Compression-Potenzial basierend auf Content-Analyse
     */
    private static function estimateCompressionPotential(string $content): array
    {
        $originalSize = strlen($content);
        
        // Wiederholende Patterns (typisch für JSON mit vielen ähnlichen Objekten)
        $repetitiveScore = 0;
        $patterns = [
            '/"[a-zA-Z_]+":/',  // JSON keys
            '/\d{4}-\d{2}-\d{2}/', // Dates
            '/null,?/',         // null values
            '/true,?/',         // boolean true
            '/false,?/'         // boolean false
        ];

        foreach ($patterns as $pattern) {
            preg_match_all($pattern, $content, $matches);
            $repetitiveScore += count($matches[0]);
        }

        // String-Redundanz
        $uniqueStrings = array_unique(preg_split('/[",\[\]{}:]/', $content));
        $stringRedundancy = (strlen($content) - array_sum(array_map('strlen', $uniqueStrings))) / $originalSize;

        // Geschätzte Compression-Rate
        $estimatedRatio = min(0.8, ($repetitiveScore / 100) + $stringRedundancy);

        return [
            'repetitive_patterns' => $repetitiveScore,
            'string_redundancy' => round($stringRedundancy * 100, 2) . '%',
            'estimated_compression' => round($estimatedRatio * 100, 2) . '%',
            'potential_savings' => round($originalSize * $estimatedRatio) . ' bytes'
        ];
    }
}