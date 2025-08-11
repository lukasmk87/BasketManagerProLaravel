<template>
  <div class="annotation-overlay" :style="overlayStyles">
    <!-- Basketball Court Visualization -->
    <div v-if="showCourtVisualization" class="court-container">
      <svg 
        class="basketball-court" 
        :viewBox="courtViewBox"
        :width="courtDimensions.width"
        :height="courtDimensions.height"
        @click="onCourtClick"
      >
        <!-- Court Background -->
        <rect 
          class="court-background"
          :width="courtDimensions.width"
          :height="courtDimensions.height"
          fill="#d2691e"
          stroke="#fff"
          stroke-width="2"
        />
        
        <!-- Court Lines -->
        <g class="court-lines">
          <!-- Center Line -->
          <line 
            :x1="courtDimensions.width / 2" 
            :y1="0" 
            :x2="courtDimensions.width / 2" 
            :y2="courtDimensions.height"
            stroke="#fff" 
            stroke-width="2"
          />
          
          <!-- Center Circle -->
          <circle 
            :cx="courtDimensions.width / 2" 
            :cy="courtDimensions.height / 2"
            :r="courtDimensions.centerCircleRadius"
            fill="none" 
            stroke="#fff" 
            stroke-width="2"
          />
          
          <!-- Three Point Lines -->
          <path 
            :d="threePointArcTop" 
            fill="none" 
            stroke="#fff" 
            stroke-width="2"
          />
          <path 
            :d="threePointArcBottom" 
            fill="none" 
            stroke="#fff" 
            stroke-width="2"
          />
          
          <!-- Free Throw Circles -->
          <circle 
            :cx="courtDimensions.width / 2" 
            :cy="courtDimensions.freeThrowTop"
            :r="courtDimensions.freeThrowRadius"
            fill="none" 
            stroke="#fff" 
            stroke-width="2"
          />
          <circle 
            :cx="courtDimensions.width / 2" 
            :cy="courtDimensions.freeThrowBottom"
            :r="courtDimensions.freeThrowRadius"
            fill="none" 
            stroke="#fff" 
            stroke-width="2"
          />
          
          <!-- Key Areas -->
          <rect 
            :x="courtDimensions.width / 2 - courtDimensions.keyWidth / 2"
            :y="0"
            :width="courtDimensions.keyWidth"
            :height="courtDimensions.keyHeight"
            fill="none" 
            stroke="#fff" 
            stroke-width="2"
          />
          <rect 
            :x="courtDimensions.width / 2 - courtDimensions.keyWidth / 2"
            :y="courtDimensions.height - courtDimensions.keyHeight"
            :width="courtDimensions.keyWidth"
            :height="courtDimensions.keyHeight"
            fill="none" 
            stroke="#fff" 
            stroke-width="2"
          />
          
          <!-- Baskets -->
          <circle 
            :cx="courtDimensions.width / 2" 
            :cy="courtDimensions.basketTop"
            r="9" 
            fill="none" 
            stroke="#ff4500" 
            stroke-width="3"
          />
          <circle 
            :cx="courtDimensions.width / 2" 
            :cy="courtDimensions.basketBottom"
            r="9" 
            fill="none" 
            stroke="#ff4500" 
            stroke-width="3"
          />
        </g>
      </svg>
      
      <!-- Annotation Markers on Court -->
      <div
        v-for="annotation in currentAnnotations"
        :key="`court-${annotation.id}`"
        class="court-annotation-marker"
        :style="getCourtMarkerStyle(annotation)"
        :title="annotation.title"
        @click="$emit('annotation-click', annotation)"
      >
        <div class="marker-dot" :style="{ backgroundColor: annotation.color_code || '#007bff' }"></div>
        <div class="marker-label">{{ getAnnotationLabel(annotation) }}</div>
      </div>
    </div>

    <!-- Video Annotation Markers -->
    <div
      v-for="annotation in currentAnnotations"
      :key="`video-${annotation.id}`"
      class="video-annotation-marker"
      :class="{ 'selected': selectedAnnotation?.id === annotation.id }"
      :style="getVideoMarkerStyle(annotation)"
      @click="$emit('annotation-click', annotation)"
    >
      <div class="annotation-content">
        <!-- Annotation Icon -->
        <div class="annotation-icon" :style="{ backgroundColor: annotation.color_code || '#007bff' }">
          <i :class="getAnnotationIcon(annotation)"></i>
        </div>
        
        <!-- Annotation Info -->
        <div class="annotation-info" v-if="showAnnotationDetails">
          <div class="annotation-title">{{ annotation.title }}</div>
          <div class="annotation-meta">
            <span class="annotation-time">{{ formatTime(annotation.start_time) }}</span>
            <span v-if="annotation.play_type" class="annotation-type">{{ getPlayTypeLabel(annotation.play_type) }}</span>
            <span v-if="annotation.outcome" class="annotation-outcome" :class="`outcome-${annotation.outcome}`">
              {{ getOutcomeLabel(annotation.outcome) }}
            </span>
          </div>
          
          <!-- Basketball-specific data -->
          <div v-if="hasBasketballData(annotation)" class="annotation-stats">
            <span v-if="annotation.points_scored" class="stat-item">
              <i class="fas fa-bullseye"></i>
              {{ annotation.points_scored }} Punkt{{ annotation.points_scored !== 1 ? 'e' : '' }}
            </span>
            <span v-if="annotation.player_involved" class="stat-item">
              <i class="fas fa-user"></i>
              {{ annotation.player_involved }}
            </span>
            <span v-if="annotation.statistical_data?.shot_distance" class="stat-item">
              <i class="fas fa-ruler"></i>
              {{ annotation.statistical_data.shot_distance }}m
            </span>
          </div>

          <!-- AI Confidence (if AI-generated) -->
          <div v-if="annotation.is_ai_generated" class="ai-confidence">
            <i class="fas fa-robot"></i>
            <span class="confidence-score">{{ Math.round(annotation.ai_confidence * 100) }}%</span>
            <span class="confidence-label">AI</span>
          </div>
        </div>

        <!-- Quick Action Buttons -->
        <div class="annotation-actions" v-if="showQuickActions">
          <button 
            class="action-btn edit-btn" 
            @click.stop="$emit('annotation-edit', annotation)"
            title="Bearbeiten"
          >
            <i class="fas fa-edit"></i>
          </button>
          <button 
            class="action-btn delete-btn" 
            @click.stop="$emit('annotation-delete', annotation)"
            title="Löschen"
          >
            <i class="fas fa-trash"></i>
          </button>
          <button 
            class="action-btn share-btn" 
            @click.stop="shareAnnotation(annotation)"
            title="Teilen"
          >
            <i class="fas fa-share"></i>
          </button>
        </div>
      </div>

      <!-- Connection Line to Court Position -->
      <svg 
        v-if="annotation.court_position_x && annotation.court_position_y && showCourtVisualization"
        class="connection-line"
        :style="getConnectionLineStyle(annotation)"
      >
        <line 
          x1="0" 
          y1="0" 
          :x2="getConnectionEndX(annotation)"
          :y2="getConnectionEndY(annotation)"
          :stroke="annotation.color_code || '#007bff'"
          stroke-width="2" 
          stroke-dasharray="5,5"
          opacity="0.6"
        />
      </svg>
    </div>

    <!-- Annotation Tooltip -->
    <div 
      v-if="hoveredAnnotation && showTooltip"
      class="annotation-tooltip"
      :style="tooltipStyle"
    >
      <div class="tooltip-header">
        <span class="tooltip-title">{{ hoveredAnnotation.title }}</span>
        <span class="tooltip-time">{{ formatTimeRange(hoveredAnnotation) }}</span>
      </div>
      <div class="tooltip-content" v-if="hoveredAnnotation.description">
        {{ hoveredAnnotation.description }}
      </div>
      <div class="tooltip-tags" v-if="hoveredAnnotation.tags?.length">
        <span 
          v-for="tag in hoveredAnnotation.tags" 
          :key="tag"
          class="tooltip-tag"
        >
          {{ tag }}
        </span>
      </div>
    </div>

    <!-- Heat Map Overlay -->
    <div v-if="showHeatMap" class="heat-map-overlay">
      <canvas 
        ref="heatMapCanvas"
        class="heat-map-canvas"
        :width="videoDimensions.width"
        :height="videoDimensions.height"
      ></canvas>
    </div>

    <!-- Zone Labels -->
    <div v-if="showZoneLabels" class="zone-labels">
      <div
        v-for="zone in basketballZones"
        :key="zone.name"
        class="zone-label"
        :style="getZoneLabelStyle(zone)"
      >
        {{ zone.label }}
      </div>
    </div>

    <!-- Performance Indicators -->
    <div v-if="showPerformanceIndicators" class="performance-indicators">
      <div class="performance-stats">
        <div class="stat-group">
          <span class="stat-label">Würfe:</span>
          <span class="stat-value">{{ shotStats.total }}</span>
          <span class="stat-percentage" :class="shotStats.percentage >= 50 ? 'good' : 'poor'">
            ({{ shotStats.percentage }}%)
          </span>
        </div>
        <div class="stat-group">
          <span class="stat-label">Pässe:</span>
          <span class="stat-value">{{ passStats.successful }}/{{ passStats.total }}</span>
        </div>
        <div class="stat-group">
          <span class="stat-label">Rebounds:</span>
          <span class="stat-value">{{ reboundStats.total }}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AnnotationOverlay',
  props: {
    annotations: {
      type: Array,
      default: () => []
    },
    currentTime: {
      type: Number,
      default: 0
    },
    videoDimensions: {
      type: Object,
      default: () => ({ width: 800, height: 600 })
    },
    selectedAnnotation: {
      type: Object,
      default: null
    },
    courtTemplate: {
      type: String,
      default: 'standard'
    },
    showCourtVisualization: {
      type: Boolean,
      default: true
    },
    showAnnotationDetails: {
      type: Boolean,
      default: true
    },
    showQuickActions: {
      type: Boolean,
      default: true
    },
    showHeatMap: {
      type: Boolean,
      default: false
    },
    showZoneLabels: {
      type: Boolean,
      default: false
    },
    showPerformanceIndicators: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      hoveredAnnotation: null,
      showTooltip: false,
      tooltipPosition: { x: 0, y: 0 },
      heatMapData: [],
      basketballZones: [
        { name: 'paint', label: 'Zone', x: 50, y: 25, width: 0, height: 25 },
        { name: 'left_wing', label: 'Linker Flügel', x: 20, y: 30, width: 30, height: 20 },
        { name: 'right_wing', label: 'Rechter Flügel', x: 70, y: 30, width: 30, height: 20 },
        { name: 'top_key', label: 'Obere Zone', x: 35, y: 15, width: 30, height: 15 },
        { name: 'perimeter', label: 'Dreipunkt-Linie', x: 10, y: 40, width: 80, height: 15 }
      ]
    }
  },
  computed: {
    currentAnnotations() {
      return this.annotations.filter(annotation => {
        return this.currentTime >= annotation.start_time && 
               this.currentTime <= annotation.end_time
      })
    },

    overlayStyles() {
      return {
        width: this.videoDimensions.width + 'px',
        height: this.videoDimensions.height + 'px',
        position: 'absolute',
        top: '0',
        left: '0',
        pointerEvents: 'none'
      }
    },

    courtDimensions() {
      const baseWidth = 500
      const baseHeight = 300
      const scale = Math.min(
        (this.videoDimensions.width * 0.3) / baseWidth,
        (this.videoDimensions.height * 0.3) / baseHeight
      )

      return {
        width: baseWidth * scale,
        height: baseHeight * scale,
        centerCircleRadius: 60 * scale,
        freeThrowRadius: 60 * scale,
        freeThrowTop: 95 * scale,
        freeThrowBottom: (baseHeight - 95) * scale,
        basketTop: 19 * scale,
        basketBottom: (baseHeight - 19) * scale,
        keyWidth: 120 * scale,
        keyHeight: 95 * scale,
        threePointRadius: 180 * scale
      }
    },

    courtViewBox() {
      return `0 0 ${this.courtDimensions.width} ${this.courtDimensions.height}`
    },

    threePointArcTop() {
      const centerX = this.courtDimensions.width / 2
      const topY = this.courtDimensions.basketTop
      const radius = this.courtDimensions.threePointRadius
      
      return `M ${centerX - radius} ${topY + radius} 
              A ${radius} ${radius} 0 0 1 ${centerX + radius} ${topY + radius}`
    },

    threePointArcBottom() {
      const centerX = this.courtDimensions.width / 2
      const bottomY = this.courtDimensions.basketBottom
      const radius = this.courtDimensions.threePointRadius
      
      return `M ${centerX - radius} ${bottomY - radius} 
              A ${radius} ${radius} 0 0 0 ${centerX + radius} ${bottomY - radius}`
    },

    tooltipStyle() {
      return {
        position: 'absolute',
        left: this.tooltipPosition.x + 'px',
        top: this.tooltipPosition.y + 'px',
        pointerEvents: 'none'
      }
    },

    shotStats() {
      const shots = this.currentAnnotations.filter(a => a.play_type === 'shot')
      const successful = shots.filter(a => a.outcome === 'successful')
      
      return {
        total: shots.length,
        successful: successful.length,
        percentage: shots.length > 0 ? Math.round((successful.length / shots.length) * 100) : 0
      }
    },

    passStats() {
      const passes = this.currentAnnotations.filter(a => a.play_type === 'pass')
      const successful = passes.filter(a => a.outcome === 'successful')
      
      return {
        total: passes.length,
        successful: successful.length
      }
    },

    reboundStats() {
      const rebounds = this.currentAnnotations.filter(a => a.play_type === 'rebound')
      
      return {
        total: rebounds.length
      }
    }
  },
  watch: {
    currentAnnotations: {
      handler(newAnnotations) {
        this.updateHeatMap(newAnnotations)
      },
      immediate: true
    }
  },
  mounted() {
    this.initializeHeatMap()
  },
  methods: {
    // Annotation Positioning
    getVideoMarkerStyle(annotation) {
      let x = 50 // Default center position
      let y = 50

      // Use court position if available
      if (annotation.court_position_x && annotation.court_position_y) {
        x = (annotation.court_position_x / 1000) * 100
        y = (annotation.court_position_y / 600) * 100
      }
      // Otherwise use frame-based positioning or defaults based on play type
      else {
        const positions = this.getDefaultPositionForPlayType(annotation.play_type)
        x = positions.x
        y = positions.y
      }

      return {
        position: 'absolute',
        left: `${x}%`,
        top: `${y}%`,
        transform: 'translate(-50%, -50%)',
        pointerEvents: 'auto',
        zIndex: annotation.id === this.selectedAnnotation?.id ? 1000 : 100
      }
    },

    getCourtMarkerStyle(annotation) {
      if (!annotation.court_position_x || !annotation.court_position_y) return { display: 'none' }

      const x = (annotation.court_position_x / 1000) * this.courtDimensions.width
      const y = (annotation.court_position_y / 600) * this.courtDimensions.height

      return {
        position: 'absolute',
        left: `${x}px`,
        top: `${y}px`,
        transform: 'translate(-50%, -50%)',
        pointerEvents: 'auto'
      }
    },

    getDefaultPositionForPlayType(playType) {
      const positions = {
        'shot': { x: 50, y: 30 },
        'free_throw': { x: 50, y: 20 },
        'rebound': { x: 50, y: 25 },
        'pass': { x: 40, y: 45 },
        'steal': { x: 60, y: 55 },
        'block': { x: 45, y: 25 },
        'dribble': { x: 55, y: 50 },
        'turnover': { x: 45, y: 60 }
      }
      
      return positions[playType] || { x: 50, y: 50 }
    },

    // Connection Lines
    getConnectionLineStyle(annotation) {
      const videoMarker = this.getVideoMarkerPosition(annotation)
      const courtMarker = this.getCourtMarkerPosition(annotation)
      
      if (!videoMarker || !courtMarker) return { display: 'none' }

      return {
        position: 'absolute',
        left: `${videoMarker.x}px`,
        top: `${videoMarker.y}px`,
        width: `${Math.abs(courtMarker.x - videoMarker.x) + 10}px`,
        height: `${Math.abs(courtMarker.y - videoMarker.y) + 10}px`,
        pointerEvents: 'none'
      }
    },

    getConnectionEndX(annotation) {
      const videoPos = this.getVideoMarkerPosition(annotation)
      const courtPos = this.getCourtMarkerPosition(annotation)
      return courtPos ? courtPos.x - videoPos.x : 0
    },

    getConnectionEndY(annotation) {
      const videoPos = this.getVideoMarkerPosition(annotation)
      const courtPos = this.getCourtMarkerPosition(annotation)
      return courtPos ? courtPos.y - videoPos.y : 0
    },

    getVideoMarkerPosition(annotation) {
      const style = this.getVideoMarkerStyle(annotation)
      const leftPercent = parseFloat(style.left)
      const topPercent = parseFloat(style.top)
      
      return {
        x: (leftPercent / 100) * this.videoDimensions.width,
        y: (topPercent / 100) * this.videoDimensions.height
      }
    },

    getCourtMarkerPosition(annotation) {
      if (!annotation.court_position_x || !annotation.court_position_y) return null
      
      const x = (annotation.court_position_x / 1000) * this.courtDimensions.width + 20 // Court offset
      const y = (annotation.court_position_y / 600) * this.courtDimensions.height + 20
      
      return { x, y }
    },

    // Annotation Labels and Icons
    getAnnotationIcon(annotation) {
      const icons = {
        'shot': 'fas fa-bullseye',
        'pass': 'fas fa-arrow-right',
        'dribble': 'fas fa-basketball-ball',
        'rebound': 'fas fa-hand-paper',
        'steal': 'fas fa-hand-grabbing',
        'block': 'fas fa-shield-alt',
        'foul': 'fas fa-exclamation-triangle',
        'turnover': 'fas fa-times-circle',
        'timeout': 'fas fa-clock',
        'substitution': 'fas fa-exchange-alt',
        'free_throw': 'fas fa-dot-circle',
        'jump_ball': 'fas fa-circle',
        'violation': 'fas fa-ban'
      }
      
      const typeIcon = icons[annotation.play_type] || 'fas fa-info-circle'
      
      // Add modifier for outcome
      if (annotation.outcome === 'successful') {
        return typeIcon + ' success'
      } else if (annotation.outcome === 'unsuccessful') {
        return typeIcon + ' fail'
      }
      
      return typeIcon
    },

    getAnnotationLabel(annotation) {
      if (annotation.play_type) {
        return this.getPlayTypeLabel(annotation.play_type)
      }
      return annotation.title.substring(0, 15) + (annotation.title.length > 15 ? '...' : '')
    },

    getPlayTypeLabel(playType) {
      const labels = {
        'shot': 'Wurf',
        'pass': 'Pass',
        'dribble': 'Dribbling',
        'rebound': 'Rebound',
        'steal': 'Ballgewinn',
        'block': 'Block',
        'foul': 'Foul',
        'turnover': 'Ballverlust',
        'timeout': 'Auszeit',
        'substitution': 'Wechsel',
        'free_throw': 'Freiwurf',
        'jump_ball': 'Sprungball',
        'violation': 'Regelverstoß'
      }
      
      return labels[playType] || playType
    },

    getOutcomeLabel(outcome) {
      const labels = {
        'successful': 'Erfolgreich',
        'unsuccessful': 'Verfehlt',
        'neutral': 'Neutral',
        'positive': 'Positiv',
        'negative': 'Negativ'
      }
      
      return labels[outcome] || outcome
    },

    hasBasketballData(annotation) {
      return annotation.points_scored > 0 || 
             annotation.player_involved || 
             annotation.statistical_data?.shot_distance
    },

    // Zone Labels
    getZoneLabelStyle(zone) {
      return {
        position: 'absolute',
        left: `${zone.x}%`,
        top: `${zone.y}%`,
        transform: 'translate(-50%, -50%)'
      }
    },

    // Heat Map
    initializeHeatMap() {
      if (this.$refs.heatMapCanvas) {
        const canvas = this.$refs.heatMapCanvas
        const ctx = canvas.getContext('2d')
        ctx.clearRect(0, 0, canvas.width, canvas.height)
      }
    },

    updateHeatMap(annotations) {
      if (!this.showHeatMap || !this.$refs.heatMapCanvas) return

      const canvas = this.$refs.heatMapCanvas
      const ctx = canvas.getContext('2d')
      
      // Clear canvas
      ctx.clearRect(0, 0, canvas.width, canvas.height)
      
      // Group annotations by position
      const positionGroups = new Map()
      
      annotations.forEach(annotation => {
        if (annotation.court_position_x && annotation.court_position_y) {
          const key = `${Math.floor(annotation.court_position_x / 50)}_${Math.floor(annotation.court_position_y / 50)}`
          if (!positionGroups.has(key)) {
            positionGroups.set(key, [])
          }
          positionGroups.get(key).push(annotation)
        }
      })

      // Draw heat map points
      positionGroups.forEach((groupAnnotations, key) => {
        const [x, y] = key.split('_').map(Number)
        const intensity = Math.min(1, groupAnnotations.length / 10) // Normalize intensity
        
        const canvasX = (x * 50 / 1000) * canvas.width
        const canvasY = (y * 50 / 600) * canvas.height
        
        // Create gradient
        const gradient = ctx.createRadialGradient(canvasX, canvasY, 0, canvasX, canvasY, 30)
        gradient.addColorStop(0, `rgba(255, 0, 0, ${intensity * 0.6})`)
        gradient.addColorStop(1, 'rgba(255, 0, 0, 0)')
        
        ctx.fillStyle = gradient
        ctx.beginPath()
        ctx.arc(canvasX, canvasY, 30, 0, 2 * Math.PI)
        ctx.fill()
      })
    },

    // Event Handlers
    onCourtClick(event) {
      const rect = event.target.getBoundingClientRect()
      const x = ((event.clientX - rect.left) / rect.width) * 1000
      const y = ((event.clientY - rect.top) / rect.height) * 600
      
      this.$emit('court-click', { x: Math.round(x), y: Math.round(y) })
    },

    onAnnotationHover(annotation, event) {
      this.hoveredAnnotation = annotation
      this.tooltipPosition = {
        x: event.clientX + 10,
        y: event.clientY - 10
      }
      this.showTooltip = true
    },

    onAnnotationLeave() {
      this.hoveredAnnotation = null
      this.showTooltip = false
    },

    shareAnnotation(annotation) {
      // Create share URL
      const baseUrl = window.location.origin
      const shareUrl = `${baseUrl}/videos/${annotation.video_file_id}?t=${annotation.start_time}&annotation=${annotation.id}`
      
      // Copy to clipboard
      navigator.clipboard.writeText(shareUrl).then(() => {
        this.$emit('annotation-shared', annotation, shareUrl)
      })
    },

    // Utility Methods
    formatTime(seconds) {
      const minutes = Math.floor(seconds / 60)
      const secs = Math.floor(seconds % 60)
      return `${minutes}:${secs.toString().padStart(2, '0')}`
    },

    formatTimeRange(annotation) {
      return `${this.formatTime(annotation.start_time)} - ${this.formatTime(annotation.end_time)}`
    }
  }
}
</script>

<style scoped>
.annotation-overlay {
  pointer-events: none;
  user-select: none;
  overflow: hidden;
}

.court-container {
  position: absolute;
  top: 20px;
  right: 20px;
  background: rgba(0, 0, 0, 0.8);
  border-radius: 8px;
  padding: 10px;
  pointer-events: auto;
}

.basketball-court {
  border-radius: 4px;
  background: #d2691e;
}

.court-lines {
  pointer-events: none;
}

.court-annotation-marker {
  z-index: 10;
  cursor: pointer;
}

.marker-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  border: 2px solid white;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.marker-label {
  position: absolute;
  top: 12px;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(0, 0, 0, 0.8);
  color: white;
  padding: 2px 6px;
  border-radius: 10px;
  font-size: 0.7rem;
  white-space: nowrap;
  pointer-events: none;
}

.video-annotation-marker {
  cursor: pointer;
  transition: all 0.2s ease;
}

.video-annotation-marker:hover {
  transform: translate(-50%, -50%) scale(1.1);
  z-index: 200 !important;
}

.video-annotation-marker.selected {
  transform: translate(-50%, -50%) scale(1.2);
}

.annotation-content {
  position: relative;
  background: rgba(0, 0, 0, 0.9);
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(10px);
}

.annotation-icon {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.2rem;
  margin-bottom: 5px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.annotation-info {
  padding: 8px;
  min-width: 200px;
}

.annotation-title {
  color: white;
  font-weight: bold;
  font-size: 0.9rem;
  margin-bottom: 4px;
  line-height: 1.2;
}

.annotation-meta {
  display: flex;
  gap: 8px;
  margin-bottom: 6px;
  flex-wrap: wrap;
}

.annotation-time {
  background: rgba(255, 255, 255, 0.1);
  color: #ccc;
  padding: 2px 6px;
  border-radius: 10px;
  font-size: 0.7rem;
  font-family: 'Roboto Mono', monospace;
}

.annotation-type {
  background: rgba(0, 123, 255, 0.8);
  color: white;
  padding: 2px 6px;
  border-radius: 10px;
  font-size: 0.7rem;
}

.annotation-outcome {
  padding: 2px 6px;
  border-radius: 10px;
  font-size: 0.7rem;
  color: white;
}

.outcome-successful {
  background: rgba(40, 167, 69, 0.8);
}

.outcome-unsuccessful {
  background: rgba(220, 53, 69, 0.8);
}

.outcome-neutral {
  background: rgba(108, 117, 125, 0.8);
}

.annotation-stats {
  display: flex;
  gap: 10px;
  margin-bottom: 6px;
  flex-wrap: wrap;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 4px;
  color: #ccc;
  font-size: 0.75rem;
}

.stat-item i {
  font-size: 0.7rem;
  opacity: 0.8;
}

.ai-confidence {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 4px;
  padding-top: 4px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.confidence-score {
  font-weight: bold;
  color: #17a2b8;
  font-size: 0.8rem;
}

.confidence-label {
  background: rgba(23, 162, 184, 0.2);
  color: #17a2b8;
  padding: 1px 4px;
  border-radius: 6px;
  font-size: 0.6rem;
}

.annotation-actions {
  display: flex;
  justify-content: center;
  gap: 4px;
  padding: 6px;
  background: rgba(255, 255, 255, 0.05);
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.action-btn {
  background: transparent;
  border: 1px solid rgba(255, 255, 255, 0.2);
  color: #ccc;
  padding: 4px 8px;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.8rem;
}

.action-btn:hover {
  background: rgba(255, 255, 255, 0.1);
  color: white;
}

.edit-btn:hover {
  border-color: #ffc107;
  color: #ffc107;
}

.delete-btn:hover {
  border-color: #dc3545;
  color: #dc3545;
}

.share-btn:hover {
  border-color: #17a2b8;
  color: #17a2b8;
}

.connection-line {
  pointer-events: none;
  z-index: 1;
}

.annotation-tooltip {
  background: rgba(0, 0, 0, 0.95);
  color: white;
  padding: 12px;
  border-radius: 8px;
  max-width: 300px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(10px);
  z-index: 1000;
}

.tooltip-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
  gap: 10px;
}

.tooltip-title {
  font-weight: bold;
  font-size: 0.9rem;
}

.tooltip-time {
  color: #ccc;
  font-size: 0.8rem;
  font-family: 'Roboto Mono', monospace;
}

.tooltip-content {
  margin-bottom: 8px;
  font-size: 0.85rem;
  line-height: 1.4;
  color: #ddd;
}

.tooltip-tags {
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
}

.tooltip-tag {
  background: rgba(0, 123, 255, 0.3);
  color: #87ceeb;
  padding: 2px 6px;
  border-radius: 10px;
  font-size: 0.7rem;
}

.heat-map-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  z-index: 1;
}

.heat-map-canvas {
  width: 100%;
  height: 100%;
  opacity: 0.6;
}

.zone-labels {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
}

.zone-label {
  background: rgba(0, 0, 0, 0.6);
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: bold;
  white-space: nowrap;
}

.performance-indicators {
  position: absolute;
  top: 20px;
  left: 20px;
  background: rgba(0, 0, 0, 0.85);
  color: white;
  padding: 15px;
  border-radius: 8px;
  backdrop-filter: blur(10px);
  pointer-events: auto;
}

.performance-stats {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.stat-group {
  display: flex;
  align-items: center;
  gap: 8px;
}

.stat-label {
  font-weight: bold;
  min-width: 60px;
  font-size: 0.85rem;
}

.stat-value {
  font-family: 'Roboto Mono', monospace;
  font-weight: bold;
  font-size: 0.9rem;
}

.stat-percentage {
  font-size: 0.8rem;
}

.stat-percentage.good {
  color: #28a745;
}

.stat-percentage.poor {
  color: #dc3545;
}

/* Responsive Design */
@media (max-width: 768px) {
  .court-container {
    top: 10px;
    right: 10px;
    padding: 6px;
  }

  .annotation-info {
    min-width: 150px;
    padding: 6px;
  }

  .annotation-title {
    font-size: 0.8rem;
  }

  .annotation-meta {
    gap: 4px;
  }

  .annotation-stats {
    gap: 6px;
  }

  .performance-indicators {
    top: 10px;
    left: 10px;
    padding: 10px;
  }

  .stat-group {
    gap: 6px;
  }

  .stat-label {
    min-width: 50px;
    font-size: 0.8rem;
  }

  .annotation-tooltip {
    max-width: 250px;
    padding: 10px;
  }
}
</style>