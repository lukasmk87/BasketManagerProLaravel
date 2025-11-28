<?php

namespace App\Services\ML\VideoAnalysis;

use App\Models\VideoFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

/**
 * Handles Python script execution for AI video analysis.
 *
 * Extracted from AIVideoAnalysisService during REFACTOR-001.
 * Manages script paths, execution, and placeholder generation.
 */
class PythonScriptExecutorService
{
    private string $pythonExecutable;
    private array $pythonScripts;
    private int $timeout;

    public function __construct()
    {
        $this->pythonExecutable = config('video_analysis.python_executable', 'python3');
        $this->pythonScripts = config('video_analysis.scripts', []);
        $this->timeout = config('video_analysis.execution.timeout', 1800);
    }

    /**
     * Execute a Python script with input data.
     *
     * @throws Exception
     */
    public function executePythonScript(string $scriptPath, array $inputData): array
    {
        // Create temporary input file
        $inputFile = storage_path('app/temp/ai_input_' . Str::uuid() . '.json');
        file_put_contents($inputFile, json_encode($inputData, JSON_PRETTY_PRINT));

        // Create output file path
        $outputFile = storage_path('app/temp/ai_output_' . Str::uuid() . '.json');

        try {
            // Execute Python script
            $command = [$this->pythonExecutable, $scriptPath, $inputFile, $outputFile];

            $result = Process::timeout($this->timeout)->run($command);

            if (!$result->successful()) {
                throw new Exception('Python script execution failed: ' . $result->errorOutput());
            }

            // Read results
            if (!file_exists($outputFile)) {
                throw new Exception('Python script did not produce output file');
            }

            $output = json_decode(file_get_contents($outputFile), true);

            if (!$output) {
                throw new Exception('Invalid JSON output from Python script');
            }

            return $output;
        } finally {
            // Clean up temporary files
            if (file_exists($inputFile)) {
                unlink($inputFile);
            }
            if (file_exists($outputFile)) {
                unlink($outputFile);
            }
        }
    }

    /**
     * Get script path for a given script type.
     *
     * @throws Exception
     */
    public function getScriptPath(string $scriptType): string
    {
        $relativePath = $this->pythonScripts[$scriptType] ?? null;

        if (!$relativePath) {
            throw new Exception("Unknown script type: {$scriptType}");
        }

        $fullPath = base_path($relativePath);

        if (!file_exists($fullPath)) {
            // Create placeholder script for development
            $this->createPlaceholderScript($fullPath, $scriptType);
        }

        return $fullPath;
    }

    /**
     * Prepare input data for Python script execution.
     */
    public function prepareInputData(VideoFile $videoFile, array $frameData, string $analysisType, array $capabilities): array
    {
        return [
            'video_id' => $videoFile->id,
            'video_path' => Storage::path($videoFile->file_path),
            'analysis_type' => $analysisType,
            'frame_data' => $frameData,
            'capabilities' => $capabilities[$analysisType] ?? [],
            'video_metadata' => [
                'duration' => $videoFile->duration,
                'width' => $videoFile->width,
                'height' => $videoFile->height,
                'video_type' => $videoFile->video_type,
            ],
        ];
    }

    /**
     * Prepare comprehensive analysis input data.
     */
    public function prepareComprehensiveInputData(VideoFile $videoFile, array $extractedFrames, string $analysisType, array $capabilities): array
    {
        return [
            'video_id' => $videoFile->id,
            'video_path' => Storage::path($videoFile->file_path),
            'analysis_type' => $analysisType,
            'frames' => $extractedFrames,
            'video_metadata' => [
                'duration' => $videoFile->duration,
                'width' => $videoFile->width,
                'height' => $videoFile->height,
                'fps' => $videoFile->frame_rate,
            ],
            'capabilities' => $capabilities,
            'video_type' => $videoFile->video_type,
        ];
    }

    /**
     * Create a placeholder Python script for development.
     */
    public function createPlaceholderScript(string $scriptPath, string $scriptType): void
    {
        $dir = dirname($scriptPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Create a basic Python script that returns mock data for development
        $placeholderScript = $this->generatePlaceholderScript($scriptType);
        file_put_contents($scriptPath, $placeholderScript);
        chmod($scriptPath, 0755);

        Log::info("Created placeholder Python script: {$scriptPath}");
    }

    /**
     * Generate placeholder Python script content.
     */
    private function generatePlaceholderScript(string $scriptType): string
    {
        return <<<PYTHON
#!/usr/bin/env python3
"""
Placeholder AI analysis script for {$scriptType}
This is a development placeholder that returns mock data.
Replace with actual AI/ML implementation.
"""

import json
import sys
import random
from datetime import datetime

def main():
    if len(sys.argv) != 3:
        print("Usage: python script.py input.json output.json")
        sys.exit(1)

    input_file = sys.argv[1]
    output_file = sys.argv[2]

    # Read input
    with open(input_file, 'r') as f:
        input_data = json.load(f)

    # Generate mock results based on script type
    results = generate_mock_results(input_data)

    # Write output
    with open(output_file, 'w') as f:
        json.dump(results, f, indent=2)

def generate_mock_results(input_data):
    """Generate mock AI analysis results for development."""

    script_type = "{$scriptType}"

    base_result = {
        "analysis_type": script_type,
        "video_id": input_data.get("video_id"),
        "timestamp": datetime.now().isoformat(),
        "confidence_score": round(random.uniform(0.6, 0.95), 3),
        "processing_time": round(random.uniform(1.0, 5.0), 2)
    }

    if script_type == "comprehensive_analysis":
        return {
            **base_result,
            "players_detected": generate_mock_players(),
            "court_detected": generate_mock_court(),
            "actions_recognized": generate_mock_actions(),
            "shots_analyzed": generate_mock_shots(),
            "overall_confidence": round(random.uniform(0.7, 0.9), 3)
        }

    elif script_type == "player_detection":
        return {
            **base_result,
            "players": generate_mock_players()
        }

    elif script_type == "court_detection":
        return {
            **base_result,
            "court": generate_mock_court()
        }

    elif script_type == "ball_tracking":
        return {
            **base_result,
            "ball_trajectory": generate_mock_ball_trajectory()
        }

    else:
        return base_result

def generate_mock_players():
    return [
        {
            "player_id": i,
            "confidence": round(random.uniform(0.7, 0.95), 3),
            "bounding_box": [
                random.randint(50, 300),
                random.randint(50, 200),
                random.randint(80, 120),
                random.randint(150, 200)
            ],
            "jersey_number": str(random.randint(1, 99)) if random.random() > 0.3 else None,
            "team": "team_" + random.choice(["A", "B"])
        }
        for i in range(random.randint(4, 10))
    ]

def generate_mock_court():
    return {
        "detected": True,
        "confidence": round(random.uniform(0.8, 0.95), 3),
        "boundaries": {
            "left_sideline": 100,
            "right_sideline": 900,
            "baseline_1": 50,
            "baseline_2": 650
        },
        "key_points": {
            "center_circle": [500, 350],
            "free_throw_line_1": [500, 200],
            "free_throw_line_2": [500, 500],
            "three_point_arc_1": [[200, 150], [800, 150]],
            "three_point_arc_2": [[200, 550], [800, 550]]
        }
    }

def generate_mock_actions():
    actions = ["shot", "pass", "dribble", "rebound", "steal", "block"]
    return [
        {
            "action": random.choice(actions),
            "timestamp": round(random.uniform(10, 300), 1),
            "confidence": round(random.uniform(0.6, 0.9), 3),
            "player_id": random.randint(1, 10),
            "location": [random.randint(100, 900), random.randint(50, 650)]
        }
        for _ in range(random.randint(10, 25))
    ]

def generate_mock_shots():
    return [
        {
            "timestamp": round(random.uniform(10, 300), 1),
            "player_id": random.randint(1, 10),
            "shot_location": [random.randint(200, 800), random.randint(100, 600)],
            "shot_type": random.choice(["two_point", "three_point", "free_throw"]),
            "outcome": random.choice(["made", "missed"]),
            "confidence": round(random.uniform(0.7, 0.95), 3),
            "release_angle": round(random.uniform(35, 55), 1),
            "arc_height": round(random.uniform(3, 5), 1)
        }
        for _ in range(random.randint(5, 15))
    ]

def generate_mock_ball_trajectory():
    return [
        {
            "timestamp": t,
            "position": [
                random.randint(200, 800),
                random.randint(100, 600)
            ],
            "confidence": round(random.uniform(0.6, 0.9), 3)
        }
        for t in range(0, random.randint(30, 120), 2)
    ]

if __name__ == "__main__":
    main()
PYTHON;
    }
}
