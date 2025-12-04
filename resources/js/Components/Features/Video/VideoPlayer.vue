<template>
  <div class="video-player-container" :class="{ 'fullscreen': isFullscreen }">
    <!-- Main Video Player -->
    <div class="video-player-wrapper" ref="videoWrapper">
      <div class="video-container" @mousemove="handleMouseMove" @mouseleave="hideControls">
        <!-- Video Element -->
        <video
          ref="videoElement"
          class="video-element"
          :src="currentVideoUrl"
          :poster="videoPoster"
          @loadedmetadata="onVideoLoaded"
          @timeupdate="onTimeUpdate"
          @play="onPlay"
          @pause="onPause"
          @ended="onEnded"
          @seeking="onSeeking"
          @seeked="onSeeked"
          @error="onError"
          @click="togglePlayPause"
          playsinline
        />

        <!-- Video Overlay for Annotations -->
        <div class="video-overlay" v-if="showAnnotationOverlay">
          <AnnotationOverlay
            :annotations="currentAnnotations"
            :video-dimensions="videoDimensions"
            :current-time="currentTime"
            :court-template="courtTemplate"
            @annotation-click="selectAnnotation"
          />
        </div>

        <!-- Loading Spinner -->
        <div v-if="isLoading" class="loading-overlay">
          <div class="spinner">
            <i class="fas fa-basketball-ball fa-spin"></i>
          </div>
          <p>Video wird geladen...</p>
        </div>

        <!-- Error Display -->
        <div v-if="error" class="error-overlay">
          <div class="error-content">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Fehler beim Laden</h3>
            <p>{{ error }}</p>
            <button @click="retryLoad" class="btn btn-primary">
              <i class="fas fa-redo"></i> Erneut versuchen
            </button>
          </div>
        </div>

        <!-- Video Controls -->
        <div 
          class="video-controls" 
          :class="{ 'visible': showControls || !isPlaying, 'hidden': !showControls && isPlaying }"
        >
          <!-- Progress Bar -->
          <div class="progress-container">
            <div class="progress-bar-container" @click="seek">
              <div class="progress-bar" ref="progressBar">
                <div class="progress-filled" :style="{ width: progressPercent + '%' }"></div>
                <div class="progress-buffered" :style="{ width: bufferedPercent + '%' }"></div>
                
                <!-- Annotation Markers -->
                <div
                  v-for="annotation in annotations"
                  :key="annotation.id"
                  class="annotation-marker"
                  :style="{ 
                    left: (annotation.start_time / duration * 100) + '%',
                    width: Math.max(1, (annotation.end_time - annotation.start_time) / duration * 100) + '%',
                    backgroundColor: annotation.color_code || '#007bff'
                  }"
                  :title="annotation.title"
                  @click.stop="seekToAnnotation(annotation)"
                ></div>
                
                <!-- Playhead -->
                <div class="playhead" :style="{ left: progressPercent + '%' }"></div>
              </div>
            </div>
          </div>

          <!-- Control Buttons -->
          <div class="control-buttons">
            <div class="left-controls">
              <!-- Play/Pause -->
              <button @click="togglePlayPause" class="control-btn play-pause-btn">
                <i :class="isPlaying ? 'fas fa-pause' : 'fas fa-play'"></i>
              </button>

              <!-- Skip Backward -->
              <button @click="skipBackward" class="control-btn">
                <i class="fas fa-backward"></i>
                <span class="skip-time">{{ skipBackwardTime }}s</span>
              </button>

              <!-- Skip Forward -->
              <button @click="skipForward" class="control-btn">
                <i class="fas fa-forward"></i>
                <span class="skip-time">{{ skipForwardTime }}s</span>
              </button>

              <!-- Time Display -->
              <div class="time-display">
                <span class="current-time">{{ formatTime(currentTime) }}</span>
                <span class="time-separator">/</span>
                <span class="total-time">{{ formatTime(duration) }}</span>
              </div>
            </div>

            <div class="right-controls">
              <!-- Speed Control -->
              <div class="speed-control">
                <select v-model="playbackRate" @change="setPlaybackRate" class="speed-select">
                  <option value="0.25">0.25x</option>
                  <option value="0.5">0.5x</option>
                  <option value="0.75">0.75x</option>
                  <option value="1">1x</option>
                  <option value="1.25">1.25x</option>
                  <option value="1.5">1.5x</option>
                  <option value="2">2x</option>
                </select>
              </div>

              <!-- Volume Control -->
              <div class="volume-control">
                <button @click="toggleMute" class="control-btn volume-btn">
                  <i :class="volumeIcon"></i>
                </button>
                <input
                  type="range"
                  min="0"
                  max="100"
                  v-model="volume"
                  @input="setVolume"
                  class="volume-slider"
                />
              </div>

              <!-- Quality Selector -->
              <div class="quality-control" v-if="qualityOptions.length > 1">
                <select v-model="selectedQuality" @change="changeQuality" class="quality-select">
                  <option v-for="quality in qualityOptions" :key="quality.quality" :value="quality.quality">
                    {{ quality.label }}
                  </option>
                </select>
              </div>

              <!-- Annotation Toggle -->
              <button @click="toggleAnnotationOverlay" class="control-btn annotation-toggle-btn">
                <i class="fas fa-comment-dots"></i>
                <span class="annotation-count">{{ annotations.length }}</span>
              </button>

              <!-- Fullscreen -->
              <button @click="toggleFullscreen" class="control-btn fullscreen-btn">
                <i :class="isFullscreen ? 'fas fa-compress' : 'fas fa-expand'"></i>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Timeline Annotations Panel -->
    <div class="timeline-panel" v-if="showTimelinePanel" :class="{ 'collapsed': timelinePanelCollapsed }">
      <div class="timeline-header">
        <h4>
          <i class="fas fa-clock"></i>
          Timeline Annotationen
        </h4>
        <div class="timeline-controls">
          <button @click="addAnnotation" class="btn btn-sm btn-primary">
            <i class="fas fa-plus"></i> Annotation hinzufügen
          </button>
          <button @click="timelinePanelCollapsed = !timelinePanelCollapsed" class="btn btn-sm btn-outline">
            <i :class="timelinePanelCollapsed ? 'fas fa-chevron-up' : 'fas fa-chevron-down'"></i>
          </button>
        </div>
      </div>

      <div class="timeline-content" v-show="!timelinePanelCollapsed">
        <TimelineAnnotations
          :annotations="annotations"
          :current-time="currentTime"
          :duration="duration"
          :selected-annotation="selectedAnnotation"
          @annotation-select="selectAnnotation"
          @annotation-edit="editAnnotation"
          @annotation-delete="deleteAnnotation"
          @seek-to-time="seekTo"
        />
      </div>
    </div>

    <!-- Annotation Creation Modal -->
    <AnnotationModal
      v-if="showAnnotationModal"
      :video-id="videoId"
      :start-time="annotationStartTime"
      :end-time="annotationEndTime"
      :court-position="annotationCourtPosition"
      @close="closeAnnotationModal"
      @created="onAnnotationCreated"
    />

    <!-- Keyboard Shortcuts Help -->
    <div v-if="showShortcutsHelp" class="shortcuts-help-overlay" @click="showShortcutsHelp = false">
      <div class="shortcuts-help-content" @click.stop>
        <h3>Tastenkürzel</h3>
        <div class="shortcuts-list">
          <div class="shortcut-item">
            <span class="key">Leertaste</span>
            <span class="description">Wiedergabe/Pause</span>
          </div>
          <div class="shortcut-item">
            <span class="key">←</span>
            <span class="description">{{ skipBackwardTime }}s zurück</span>
          </div>
          <div class="shortcut-item">
            <span class="key">→</span>
            <span class="description">{{ skipForwardTime }}s vorwärts</span>
          </div>
          <div class="shortcut-item">
            <span class="key">↑</span>
            <span class="description">Lautstärke erhöhen</span>
          </div>
          <div class="shortcut-item">
            <span class="key">↓</span>
            <span class="description">Lautstärke verringern</span>
          </div>
          <div class="shortcut-item">
            <span class="key">M</span>
            <span class="description">Stumm schalten</span>
          </div>
          <div class="shortcut-item">
            <span class="key">F</span>
            <span class="description">Vollbild</span>
          </div>
          <div class="shortcut-item">
            <span class="key">A</span>
            <span class="description">Annotation hinzufügen</span>
          </div>
          <div class="shortcut-item">
            <span class="key">T</span>
            <span class="description">Timeline ein/aus</span>
          </div>
          <div class="shortcut-item">
            <span class="key">?</span>
            <span class="description">Diese Hilfe anzeigen</span>
          </div>
        </div>
        <button @click="showShortcutsHelp = false" class="btn btn-primary">Schließen</button>
      </div>
    </div>
  </div>
</template>

<script>
import AnnotationOverlay from './AnnotationOverlay.vue'
import TimelineAnnotations from './TimelineAnnotations.vue'
import AnnotationModal from './AnnotationModal.vue'

export default {
  name: 'VideoPlayer',
  components: {
    AnnotationOverlay,
    TimelineAnnotations,
    AnnotationModal
  },
  props: {
    videoId: {
      type: Number,
      required: true
    },
    videoData: {
      type: Object,
      required: true
    },
    annotations: {
      type: Array,
      default: () => []
    },
    autoplay: {
      type: Boolean,
      default: false
    },
    showTimeline: {
      type: Boolean,
      default: true
    },
    courtTemplate: {
      type: String,
      default: 'standard'
    }
  },
  data() {
    return {
      // Video state
      currentTime: 0,
      duration: 0,
      isPlaying: false,
      isLoading: true,
      error: null,
      bufferedPercent: 0,
      
      // UI state
      showControls: true,
      showAnnotationOverlay: true,
      showTimelinePanel: true,
      timelinePanelCollapsed: false,
      showAnnotationModal: false,
      showShortcutsHelp: false,
      isFullscreen: false,
      
      // Video settings
      volume: 100,
      isMuted: false,
      playbackRate: 1,
      selectedQuality: 'auto',
      
      // Timing
      skipBackwardTime: 10,
      skipForwardTime: 10,
      controlsTimeout: null,
      
      // Annotation creation
      annotationStartTime: 0,
      annotationEndTime: 0,
      annotationCourtPosition: null,
      selectedAnnotation: null,
      
      // Video dimensions
      videoDimensions: {
        width: 0,
        height: 0
      },
      
      // Quality options
      qualityOptions: []
    }
  },
  computed: {
    currentVideoUrl() {
      if (!this.videoData?.streaming_urls) return null
      
      const urls = this.videoData.streaming_urls
      
      // Return appropriate quality
      switch (this.selectedQuality) {
        case '720p':
          return urls.mp4_720p || urls.mp4_480p || urls.mp4_360p
        case '480p':
          return urls.mp4_480p || urls.mp4_360p
        case '360p':
          return urls.mp4_360p
        case 'hls':
          return urls.hls
        default:
          return urls.mp4_720p || urls.mp4_480p || urls.mp4_360p || urls.hls
      }
    },
    
    videoPoster() {
      return this.videoData?.thumbnail_url || null
    },
    
    progressPercent() {
      if (!this.duration) return 0
      return (this.currentTime / this.duration) * 100
    },
    
    volumeIcon() {
      if (this.isMuted || this.volume === 0) return 'fas fa-volume-mute'
      if (this.volume < 50) return 'fas fa-volume-down'
      return 'fas fa-volume-up'
    },
    
    currentAnnotations() {
      return this.annotations.filter(annotation => {
        return this.currentTime >= annotation.start_time && 
               this.currentTime <= annotation.end_time
      })
    }
  },
  mounted() {
    this.initializePlayer()
    this.setupEventListeners()
    this.loadQualityOptions()
  },
  beforeUnmount() {
    this.cleanup()
  },
  methods: {
    // Player Initialization
    initializePlayer() {
      if (this.$refs.videoElement) {
        this.$refs.videoElement.volume = this.volume / 100
        this.$refs.videoElement.playbackRate = this.playbackRate
        
        if (this.autoplay) {
          this.$nextTick(() => {
            this.play()
          })
        }
      }
    },
    
    // Event Listeners
    setupEventListeners() {
      // Keyboard shortcuts
      document.addEventListener('keydown', this.handleKeydown)
      
      // Fullscreen change
      document.addEventListener('fullscreenchange', this.onFullscreenChange)
      document.addEventListener('webkitfullscreenchange', this.onFullscreenChange)
      document.addEventListener('mozfullscreenchange', this.onFullscreenChange)
      document.addEventListener('MSFullscreenChange', this.onFullscreenChange)
      
      // Window resize
      window.addEventListener('resize', this.updateVideoDimensions)
    },
    
    cleanup() {
      document.removeEventListener('keydown', this.handleKeydown)
      document.removeEventListener('fullscreenchange', this.onFullscreenChange)
      document.removeEventListener('webkitfullscreenchange', this.onFullscreenChange)
      document.removeEventListener('mozfullscreenchange', this.onFullscreenChange)
      document.removeEventListener('MSFullscreenChange', this.onFullscreenChange)
      window.removeEventListener('resize', this.updateVideoDimensions)
      
      if (this.controlsTimeout) {
        clearTimeout(this.controlsTimeout)
      }
    },
    
    // Video Events
    onVideoLoaded() {
      const video = this.$refs.videoElement
      if (video) {
        this.duration = video.duration
        this.isLoading = false
        this.updateVideoDimensions()
        this.updateBuffered()
      }
      this.$emit('loaded', { duration: this.duration })
    },
    
    onTimeUpdate() {
      const video = this.$refs.videoElement
      if (video) {
        this.currentTime = video.currentTime
        this.updateBuffered()
      }
      this.$emit('timeupdate', this.currentTime)
    },
    
    onPlay() {
      this.isPlaying = true
      this.$emit('play')
    },
    
    onPause() {
      this.isPlaying = false
      this.$emit('pause')
    },
    
    onEnded() {
      this.isPlaying = false
      this.$emit('ended')
    },
    
    onSeeking() {
      this.$emit('seeking', this.currentTime)
    },
    
    onSeeked() {
      this.$emit('seeked', this.currentTime)
    },
    
    onError(event) {
      this.error = 'Fehler beim Laden des Videos. Bitte versuchen Sie es später erneut.'
      this.isLoading = false
      this.$emit('error', event)
    },
    
    // Playback Controls
    togglePlayPause() {
      const video = this.$refs.videoElement
      if (!video) return
      
      if (this.isPlaying) {
        this.pause()
      } else {
        this.play()
      }
    },
    
    play() {
      const video = this.$refs.videoElement
      if (video) {
        video.play().catch(error => {
          console.error('Error playing video:', error)
          this.error = 'Fehler beim Abspielen des Videos.'
        })
      }
    },
    
    pause() {
      const video = this.$refs.videoElement
      if (video) {
        video.pause()
      }
    },
    
    seek(event) {
      const progressBar = this.$refs.progressBar
      if (!progressBar || !this.duration) return
      
      const rect = progressBar.getBoundingClientRect()
      const percent = (event.clientX - rect.left) / rect.width
      const newTime = percent * this.duration
      
      this.seekTo(newTime)
    },
    
    seekTo(time) {
      const video = this.$refs.videoElement
      if (video && time >= 0 && time <= this.duration) {
        video.currentTime = time
        this.currentTime = time
      }
    },
    
    skipBackward() {
      this.seekTo(Math.max(0, this.currentTime - this.skipBackwardTime))
    },
    
    skipForward() {
      this.seekTo(Math.min(this.duration, this.currentTime + this.skipForwardTime))
    },
    
    // Volume Controls
    setVolume() {
      const video = this.$refs.videoElement
      if (video) {
        video.volume = this.volume / 100
        this.isMuted = this.volume === 0
      }
    },
    
    toggleMute() {
      this.isMuted = !this.isMuted
      const video = this.$refs.videoElement
      if (video) {
        video.muted = this.isMuted
      }
    },
    
    // Playback Rate
    setPlaybackRate() {
      const video = this.$refs.videoElement
      if (video) {
        video.playbackRate = parseFloat(this.playbackRate)
      }
    },
    
    // Quality Control
    loadQualityOptions() {
      const urls = this.videoData?.streaming_urls || {}
      const options = []
      
      if (urls.mp4_720p) options.push({ quality: '720p', label: '720p HD' })
      if (urls.mp4_480p) options.push({ quality: '480p', label: '480p' })
      if (urls.mp4_360p) options.push({ quality: '360p', label: '360p' })
      if (urls.hls) options.push({ quality: 'hls', label: 'Auto (HLS)' })
      
      this.qualityOptions = options
      
      if (options.length > 0) {
        this.selectedQuality = options[0].quality
      }
    },
    
    changeQuality() {
      const currentTime = this.currentTime
      const wasPlaying = this.isPlaying
      
      this.$nextTick(() => {
        const video = this.$refs.videoElement
        if (video) {
          video.currentTime = currentTime
          if (wasPlaying) {
            video.play()
          }
        }
      })
    },
    
    // Fullscreen
    toggleFullscreen() {
      const wrapper = this.$refs.videoWrapper
      if (!wrapper) return
      
      if (!this.isFullscreen) {
        this.enterFullscreen(wrapper)
      } else {
        this.exitFullscreen()
      }
    },
    
    enterFullscreen(element) {
      if (element.requestFullscreen) {
        element.requestFullscreen()
      } else if (element.webkitRequestFullscreen) {
        element.webkitRequestFullscreen()
      } else if (element.mozRequestFullScreen) {
        element.mozRequestFullScreen()
      } else if (element.msRequestFullscreen) {
        element.msRequestFullscreen()
      }
    },
    
    exitFullscreen() {
      if (document.exitFullscreen) {
        document.exitFullscreen()
      } else if (document.webkitExitFullscreen) {
        document.webkitExitFullscreen()
      } else if (document.mozCancelFullScreen) {
        document.mozCancelFullScreen()
      } else if (document.msExitFullscreen) {
        document.msExitFullscreen()
      }
    },
    
    onFullscreenChange() {
      this.isFullscreen = !!(
        document.fullscreenElement ||
        document.webkitFullscreenElement ||
        document.mozFullScreenElement ||
        document.msFullscreenElement
      )
    },
    
    // UI Controls
    handleMouseMove() {
      this.showControls = true
      this.resetControlsTimeout()
    },
    
    hideControls() {
      if (this.isPlaying) {
        this.resetControlsTimeout()
      }
    },
    
    resetControlsTimeout() {
      if (this.controlsTimeout) {
        clearTimeout(this.controlsTimeout)
      }
      
      this.controlsTimeout = setTimeout(() => {
        if (this.isPlaying) {
          this.showControls = false
        }
      }, 3000)
    },
    
    toggleAnnotationOverlay() {
      this.showAnnotationOverlay = !this.showAnnotationOverlay
    },
    
    // Keyboard Shortcuts
    handleKeydown(event) {
      // Don't handle shortcuts if user is typing in an input
      if (['INPUT', 'TEXTAREA', 'SELECT'].includes(event.target.tagName)) {
        return
      }
      
      switch (event.key) {
        case ' ':
        case 'k':
          event.preventDefault()
          this.togglePlayPause()
          break
        case 'ArrowLeft':
          event.preventDefault()
          this.skipBackward()
          break
        case 'ArrowRight':
          event.preventDefault()
          this.skipForward()
          break
        case 'ArrowUp':
          event.preventDefault()
          this.volume = Math.min(100, this.volume + 5)
          this.setVolume()
          break
        case 'ArrowDown':
          event.preventDefault()
          this.volume = Math.max(0, this.volume - 5)
          this.setVolume()
          break
        case 'm':
          event.preventDefault()
          this.toggleMute()
          break
        case 'f':
          event.preventDefault()
          this.toggleFullscreen()
          break
        case 'a':
          event.preventDefault()
          this.addAnnotation()
          break
        case 't':
          event.preventDefault()
          this.showTimelinePanel = !this.showTimelinePanel
          break
        case '?':
          event.preventDefault()
          this.showShortcutsHelp = !this.showShortcutsHelp
          break
      }
    },
    
    // Annotations
    selectAnnotation(annotation) {
      this.selectedAnnotation = annotation
      if (annotation) {
        this.seekTo(annotation.start_time)
      }
      this.$emit('annotation-select', annotation)
    },
    
    seekToAnnotation(annotation) {
      this.seekTo(annotation.start_time)
      this.selectAnnotation(annotation)
    },
    
    addAnnotation() {
      this.annotationStartTime = Math.floor(this.currentTime)
      this.annotationEndTime = Math.min(this.duration, this.annotationStartTime + 5)
      this.annotationCourtPosition = null
      this.showAnnotationModal = true
    },
    
    editAnnotation(annotation) {
      this.$emit('annotation-edit', annotation)
    },
    
    deleteAnnotation(annotation) {
      this.$emit('annotation-delete', annotation)
    },
    
    closeAnnotationModal() {
      this.showAnnotationModal = false
    },
    
    onAnnotationCreated(annotation) {
      this.closeAnnotationModal()
      this.$emit('annotation-created', annotation)
    },
    
    // Utility Methods
    updateVideoDimensions() {
      const video = this.$refs.videoElement
      if (video) {
        this.videoDimensions = {
          width: video.videoWidth || video.clientWidth,
          height: video.videoHeight || video.clientHeight
        }
      }
    },
    
    updateBuffered() {
      const video = this.$refs.videoElement
      if (video && video.buffered.length > 0) {
        const buffered = video.buffered.end(video.buffered.length - 1)
        this.bufferedPercent = (buffered / this.duration) * 100
      }
    },
    
    formatTime(seconds) {
      if (!seconds || isNaN(seconds)) return '0:00'
      
      const hours = Math.floor(seconds / 3600)
      const minutes = Math.floor((seconds % 3600) / 60)
      const secs = Math.floor(seconds % 60)
      
      if (hours > 0) {
        return `${hours}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`
      }
      
      return `${minutes}:${secs.toString().padStart(2, '0')}`
    },
    
    retryLoad() {
      this.error = null
      this.isLoading = true
      const video = this.$refs.videoElement
      if (video) {
        video.load()
      }
    }
  }
}
</script>

<style scoped>
.video-player-container {
  position: relative;
  background: #000;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
}

.video-player-container.fullscreen {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  z-index: 9999;
  border-radius: 0;
}

.video-player-wrapper {
  position: relative;
  width: 100%;
  background: #000;
}

.video-container {
  position: relative;
  width: 100%;
  cursor: none;
}

.video-element {
  width: 100%;
  height: 100%;
  display: block;
  background: #000;
}

.video-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 2;
}

.loading-overlay,
.error-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  background: rgba(0, 0, 0, 0.8);
  color: white;
  z-index: 3;
}

.spinner {
  font-size: 2rem;
  margin-bottom: 1rem;
  color: #007bff;
}

.error-content {
  text-align: center;
}

.error-content i {
  font-size: 3rem;
  color: #dc3545;
  margin-bottom: 1rem;
}

.video-controls {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
  color: white;
  padding: 20px 15px 15px;
  transition: opacity 0.3s ease;
  z-index: 4;
}

.video-controls.visible {
  opacity: 1;
}

.video-controls.hidden {
  opacity: 0;
}

.progress-container {
  margin-bottom: 10px;
}

.progress-bar-container {
  position: relative;
  height: 6px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 3px;
  cursor: pointer;
}

.progress-bar {
  position: relative;
  width: 100%;
  height: 100%;
}

.progress-filled,
.progress-buffered {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  border-radius: 3px;
}

.progress-filled {
  background: #007bff;
  z-index: 2;
}

.progress-buffered {
  background: rgba(255, 255, 255, 0.4);
  z-index: 1;
}

.annotation-marker {
  position: absolute;
  top: -2px;
  height: 10px;
  border-radius: 2px;
  opacity: 0.7;
  cursor: pointer;
  z-index: 3;
}

.annotation-marker:hover {
  opacity: 1;
  height: 12px;
  top: -3px;
}

.playhead {
  position: absolute;
  top: -4px;
  width: 14px;
  height: 14px;
  background: #007bff;
  border: 2px solid white;
  border-radius: 50%;
  transform: translateX(-50%);
  z-index: 4;
}

.control-buttons {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.left-controls,
.right-controls {
  display: flex;
  align-items: center;
  gap: 10px;
}

.control-btn {
  background: transparent;
  border: none;
  color: white;
  padding: 8px;
  border-radius: 4px;
  cursor: pointer;
  transition: background-color 0.2s ease;
  position: relative;
}

.control-btn:hover {
  background: rgba(255, 255, 255, 0.1);
}

.play-pause-btn {
  font-size: 1.2rem;
  padding: 12px;
}

.skip-time {
  position: absolute;
  top: -20px;
  left: 50%;
  transform: translateX(-50%);
  font-size: 0.7rem;
  opacity: 0.8;
}

.time-display {
  font-family: 'Roboto Mono', monospace;
  font-size: 0.9rem;
  margin: 0 10px;
}

.time-separator {
  margin: 0 5px;
  opacity: 0.7;
}

.volume-control {
  display: flex;
  align-items: center;
  gap: 5px;
}

.volume-slider {
  width: 80px;
  accent-color: #007bff;
}

.speed-select,
.quality-select {
  background: rgba(0, 0, 0, 0.5);
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: white;
  padding: 4px 8px;
  border-radius: 4px;
  cursor: pointer;
}

.annotation-toggle-btn {
  position: relative;
}

.annotation-count {
  position: absolute;
  top: 0;
  right: 0;
  background: #dc3545;
  color: white;
  font-size: 0.7rem;
  padding: 2px 4px;
  border-radius: 8px;
  min-width: 16px;
  text-align: center;
}

.timeline-panel {
  background: white;
  border-top: 1px solid #e9ecef;
  transition: all 0.3s ease;
}

.timeline-panel.collapsed .timeline-content {
  max-height: 0;
  overflow: hidden;
}

.timeline-header {
  padding: 15px 20px;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8f9fa;
}

.timeline-header h4 {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 8px;
  color: #495057;
}

.timeline-controls {
  display: flex;
  gap: 10px;
}

.timeline-content {
  max-height: 300px;
  overflow-y: auto;
}

.shortcuts-help-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 10000;
}

.shortcuts-help-content {
  background: white;
  padding: 30px;
  border-radius: 12px;
  max-width: 500px;
  width: 90%;
  max-height: 80vh;
  overflow-y: auto;
}

.shortcuts-help-content h3 {
  margin-top: 0;
  margin-bottom: 20px;
  text-align: center;
  color: #333;
}

.shortcuts-list {
  display: grid;
  grid-template-columns: 1fr;
  gap: 10px;
  margin-bottom: 20px;
}

.shortcut-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #eee;
}

.key {
  background: #f8f9fa;
  padding: 4px 8px;
  border-radius: 4px;
  font-family: 'Roboto Mono', monospace;
  font-weight: bold;
  color: #495057;
  min-width: 40px;
  text-align: center;
}

.description {
  flex: 1;
  margin-left: 15px;
  color: #666;
}

/* Responsive Design */
@media (max-width: 768px) {
  .video-controls {
    padding: 15px 10px 10px;
  }
  
  .control-buttons {
    flex-wrap: wrap;
    gap: 5px;
  }
  
  .left-controls,
  .right-controls {
    gap: 5px;
  }
  
  .volume-slider {
    width: 60px;
  }
  
  .time-display {
    font-size: 0.8rem;
  }
  
  .timeline-header {
    padding: 10px 15px;
    flex-direction: column;
    gap: 10px;
    align-items: stretch;
  }
  
  .timeline-controls {
    justify-content: center;
  }
}
</style>