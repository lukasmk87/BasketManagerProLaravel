import { ref } from 'vue';
import { createGifExporter } from '@/utils/gifExporter';

/**
 * Composable for exporting Tactic Board as images
 * Version 1.1 - Includes GIF export (Phase 11.3)
 */
export function useTacticExport() {
    const isExporting = ref(false);
    const exportError = ref(null);
    const exportProgress = ref(0); // Progress for GIF export (0-1)

    /**
     * Export a Konva stage as PNG data URL
     */
    const exportAsPng = async (stageRef, options = {}) => {
        isExporting.value = true;
        exportError.value = null;

        try {
            const stage = stageRef?.getStage?.() || stageRef;
            if (!stage) {
                throw new Error('Stage reference is required');
            }

            const dataUrl = stage.toDataURL({
                mimeType: options.mimeType || 'image/png',
                quality: options.quality || 1,
                pixelRatio: options.pixelRatio || 2, // Higher resolution
            });

            return dataUrl;
        } catch (error) {
            exportError.value = error.message;
            throw error;
        } finally {
            isExporting.value = false;
        }
    };

    /**
     * Download PNG image
     */
    const downloadPng = async (stageRef, filename = 'play', options = {}) => {
        try {
            const dataUrl = await exportAsPng(stageRef, options);

            const link = document.createElement('a');
            link.download = `${filename}-${Date.now()}.png`;
            link.href = dataUrl;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            return true;
        } catch (error) {
            console.error('Failed to download PNG:', error);
            return false;
        }
    };

    /**
     * Export as base64 for API upload
     */
    const exportAsBase64 = async (stageRef, options = {}) => {
        const dataUrl = await exportAsPng(stageRef, options);
        // Remove data URL prefix to get raw base64
        return dataUrl.replace(/^data:image\/\w+;base64,/, '');
    };

    /**
     * Generate thumbnail (smaller version)
     */
    const generateThumbnail = async (stageRef, maxWidth = 300) => {
        const stage = stageRef?.getStage?.() || stageRef;
        if (!stage) return null;

        const originalWidth = stage.width();
        const originalHeight = stage.height();
        const ratio = originalHeight / originalWidth;

        return exportAsPng(stageRef, {
            pixelRatio: maxWidth / originalWidth,
        });
    };

    /**
     * Save thumbnail to server
     */
    const saveThumbnail = async (playId, stageRef, csrfToken) => {
        isExporting.value = true;
        exportError.value = null;

        try {
            const imageData = await exportAsPng(stageRef, { pixelRatio: 1 });

            const response = await fetch(`/api/plays/${playId}/thumbnail`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    image_data: imageData,
                }),
            });

            if (!response.ok) {
                throw new Error('Failed to save thumbnail');
            }

            const data = await response.json();
            return data.thumbnail_path;
        } catch (error) {
            exportError.value = error.message;
            throw error;
        } finally {
            isExporting.value = false;
        }
    };

    /**
     * Export play to PNG via API
     */
    const exportPlayPng = async (playId, stageRef, csrfToken, width = 800) => {
        isExporting.value = true;
        exportError.value = null;

        try {
            const imageData = await exportAsPng(stageRef, { pixelRatio: 2 });

            const response = await fetch(`/api/plays/${playId}/export/png`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    image_data: imageData,
                    width,
                }),
            });

            if (!response.ok) {
                throw new Error('Failed to export PNG');
            }

            const data = await response.json();
            return data.url;
        } catch (error) {
            exportError.value = error.message;
            throw error;
        } finally {
            isExporting.value = false;
        }
    };

    /**
     * Download PDF (redirects to PDF export endpoint)
     */
    const downloadPdf = (playId, thumbnailDataUrl = null) => {
        let url = `/api/plays/${playId}/export/pdf`;

        if (thumbnailDataUrl) {
            // For POST with thumbnail, we need to use a form
            const form = document.createElement('form');
            form.method = 'GET';
            form.action = url;

            // Add thumbnail as hidden input if small enough
            // For larger thumbnails, we'd need a different approach
            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        } else {
            // Simple redirect for GET request
            window.open(url, '_blank');
        }
    };

    /**
     * Export playbook as PDF
     */
    const downloadPlaybookPdf = (playbookId, thumbnails = []) => {
        let url = `/api/playbooks/${playbookId}/export/pdf`;

        if (thumbnails.length > 0) {
            // Would need to handle thumbnails differently for playbook export
            // For now, just open the URL
            window.open(url, '_blank');
        } else {
            window.open(url, '_blank');
        }
    };

    /**
     * Copy image to clipboard
     */
    const copyToClipboard = async (stageRef) => {
        try {
            const dataUrl = await exportAsPng(stageRef);
            const blob = await (await fetch(dataUrl)).blob();

            await navigator.clipboard.write([
                new ClipboardItem({
                    [blob.type]: blob,
                }),
            ]);

            return true;
        } catch (error) {
            console.error('Failed to copy to clipboard:', error);
            exportError.value = 'Failed to copy to clipboard';
            return false;
        }
    };

    // ==========================================
    // GIF EXPORT METHODS (Phase 11.3)
    // ==========================================

    /**
     * Export animation as GIF blob
     * @param {Object} stageRef - Konva stage reference
     * @param {Object} animation - Animation composable instance
     * @param {Object} options - GIF export options
     * @returns {Promise<Blob>} GIF blob
     */
    const exportAsGif = async (stageRef, animation, options = {}) => {
        isExporting.value = true;
        exportError.value = null;
        exportProgress.value = 0;

        try {
            const exporter = createGifExporter({
                fps: options.fps || 15,
                quality: options.quality || 10,
                width: options.width || 600,
                height: options.height || 450,
            });

            const blob = await exporter.exportAnimation(
                stageRef,
                animation,
                (progress) => {
                    exportProgress.value = progress;
                }
            );

            return blob;
        } catch (error) {
            exportError.value = error.message;
            throw error;
        } finally {
            isExporting.value = false;
            exportProgress.value = 0;
        }
    };

    /**
     * Download animation as GIF
     * @param {Object} stageRef - Konva stage reference
     * @param {Object} animation - Animation composable instance
     * @param {string} filename - Filename without extension
     * @param {Object} options - GIF export options
     * @returns {Promise<boolean>} Success status
     */
    const downloadGif = async (stageRef, animation, filename = 'spielzug', options = {}) => {
        try {
            const blob = await exportAsGif(stageRef, animation, options);

            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.download = `${filename}-${Date.now()}.gif`;
            link.href = url;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);

            return true;
        } catch (error) {
            console.error('Failed to download GIF:', error);
            return false;
        }
    };

    /**
     * Convert blob to base64 string
     * @param {Blob} blob - Blob to convert
     * @returns {Promise<string>} Base64 string with data URL prefix
     */
    const blobToBase64 = (blob) => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    };

    /**
     * Save GIF to server
     * @param {number|string} playId - Play ID
     * @param {Object} stageRef - Konva stage reference
     * @param {Object} animation - Animation composable instance
     * @param {string} csrfToken - CSRF token
     * @param {Object} options - GIF export options
     * @returns {Promise<string>} Server URL of saved GIF
     */
    const saveGifToServer = async (playId, stageRef, animation, csrfToken, options = {}) => {
        isExporting.value = true;
        exportError.value = null;
        exportProgress.value = 0;

        try {
            // First export as GIF blob
            const blob = await exportAsGif(stageRef, animation, options);

            // Convert to base64
            const base64Data = await blobToBase64(blob);
            // Remove data URL prefix
            const gifData = base64Data.split(',')[1];

            // Upload to server
            const response = await fetch(`/api/plays/${playId}/export/gif`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    gif_data: gifData,
                }),
            });

            if (!response.ok) {
                throw new Error('Fehler beim Speichern des GIFs auf dem Server');
            }

            const data = await response.json();
            return data.url;
        } catch (error) {
            exportError.value = error.message;
            throw error;
        } finally {
            isExporting.value = false;
            exportProgress.value = 0;
        }
    };

    return {
        // State
        isExporting,
        exportError,
        exportProgress, // For GIF export progress (Phase 11.3)

        // Methods
        exportAsPng,
        exportAsBase64,
        downloadPng,
        generateThumbnail,
        saveThumbnail,
        exportPlayPng,
        downloadPdf,
        downloadPlaybookPdf,
        copyToClipboard,

        // GIF Export Methods (Phase 11.3)
        exportAsGif,
        downloadGif,
        saveGifToServer,
        blobToBase64,
    };
}
