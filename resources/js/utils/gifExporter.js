/**
 * GIF Exporter for TacticBoard animations
 * Phase 11.3 - Exports animation keyframes as animated GIF
 */
import GIF from 'gif.js';

/**
 * GIF Exporter class
 */
export class GifExporter {
    constructor(options = {}) {
        this.fps = options.fps || 15;
        this.quality = options.quality || 10; // 1-30, lower = better quality
        this.width = options.width || 600;
        this.height = options.height || 450;
        this.workerScript = options.workerScript || '/js/gif.worker.js';
        this.repeat = options.repeat ?? 0; // 0 = loop forever, -1 = no loop
    }

    /**
     * Export animation to GIF blob
     * @param {Object} stageRef - Konva stage reference
     * @param {Object} animation - Animation composable instance
     * @param {Function} onProgress - Progress callback (0-1)
     * @returns {Promise<Blob>} GIF blob
     */
    async exportAnimation(stageRef, animation, onProgress = () => {}) {
        return new Promise((resolve, reject) => {
            // Get the Konva stage
            const stage = stageRef?.getStage?.() || stageRef;
            if (!stage) {
                reject(new Error('Konva Stage-Referenz ist erforderlich'));
                return;
            }

            const duration = animation.totalDuration.value;
            if (duration === 0) {
                reject(new Error('Animation hat keine Dauer'));
                return;
            }

            // Initialize GIF encoder
            const gif = new GIF({
                workers: 2,
                quality: this.quality,
                width: this.width,
                height: this.height,
                workerScript: this.workerScript,
                repeat: this.repeat,
            });

            const frameInterval = 1000 / this.fps;
            const totalFrames = Math.ceil(duration / frameInterval);
            let currentFrame = 0;
            let framesCaptured = 0;

            // Store original animation state
            const wasPlaying = animation.isPlaying.value;
            const originalTime = animation.currentTime.value;

            // Pause animation if playing
            if (wasPlaying) {
                animation.pause();
            }

            /**
             * Capture a single frame
             */
            const captureFrame = () => {
                const time = currentFrame * frameInterval;

                // Seek animation to this time
                animation.seekTo(Math.min(time, duration));

                // Small delay to let Vue update the DOM
                setTimeout(() => {
                    try {
                        // Capture frame from stage
                        const canvas = stage.toCanvas({
                            pixelRatio: 1,
                            width: this.width,
                            height: this.height,
                        });

                        gif.addFrame(canvas, {
                            delay: frameInterval,
                            copy: true,
                        });

                        framesCaptured++;
                        currentFrame++;

                        // Report progress (80% for frame capture)
                        onProgress(framesCaptured / totalFrames * 0.8);

                        if (currentFrame < totalFrames) {
                            // Continue capturing next frame
                            requestAnimationFrame(captureFrame);
                        } else {
                            // All frames captured, start rendering
                            gif.render();
                        }
                    } catch (error) {
                        reject(error);
                    }
                }, 20); // 20ms delay for DOM updates
            };

            // GIF rendering progress
            gif.on('progress', (progress) => {
                // Last 20% for encoding
                onProgress(0.8 + progress * 0.2);
            });

            // GIF finished
            gif.on('finished', (blob) => {
                // Restore original animation state
                animation.seekTo(originalTime);
                if (wasPlaying) {
                    animation.play();
                }

                resolve(blob);
            });

            // GIF error
            gif.on('error', (error) => {
                // Restore original animation state
                animation.seekTo(originalTime);
                reject(error);
            });

            // Start capturing frames
            captureFrame();
        });
    }

    /**
     * Download GIF with given filename
     * @param {Blob} blob - GIF blob
     * @param {string} filename - Filename without extension
     */
    downloadGif(blob, filename = 'animation') {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.download = `${filename}-${Date.now()}.gif`;
        link.href = url;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    }

    /**
     * Convert blob to base64 string
     * @param {Blob} blob - GIF blob
     * @returns {Promise<string>} Base64 string
     */
    blobToBase64(blob) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    }
}

/**
 * Create a new GIF exporter instance
 * @param {Object} options - Exporter options
 * @returns {GifExporter} GIF exporter instance
 */
export const createGifExporter = (options) => new GifExporter(options);

/**
 * Default export options
 */
export const defaultGifOptions = {
    fps: 15,
    quality: 10,
    width: 600,
    height: 450,
};
