<template>
  <div class="timeline-annotations">
    <!-- Timeline Header -->
    <div class="timeline-header">
      <div class="timeline-controls">
        <div class="view-mode-selector">
          <button 
            v-for="mode in viewModes"
            :key="mode.value"
            class="mode-btn"
            :class="{ active: viewMode === mode.value }"
            @click="viewMode = mode.value"
          >
            <i :class="mode.icon"></i>
            {{ mode.label }}
          </button>
        </div>
        
        <div class="filter-controls">
          <select v-model="selectedFilter" class="filter-select">
            <option value="">Alle Typen</option>
            <option v-for="type in annotationTypes" :key="type.value" :value="type.value">
              {{ type.label }}
            </option>
          </select>
          
          <div class="search-box">
            <i class="fas fa-search"></i>
            <input 
              type="text" 
              v-model="searchQuery" 
              placeholder="Annotationen suchen..."
              class="search-input"
            />
          </div>
        </div>
        
        <div class="timeline-settings">
          <button 
            class="settings-btn"
            @click="showSettings = !showSettings"
            :class="{ active: showSettings }"
          >
            <i class="fas fa-cog"></i>
          </button>
        </div>
      </div>

      <!-- Settings Panel -->
      <div v-show="showSettings" class="settings-panel">
        <div class="setting-group">
          <label>
            <input type="checkbox" v-model="showThumbnails" />
            Thumbnails anzeigen
          </label>
          <label>
            <input type="checkbox" v-model="showStatistics" />
            Statistiken anzeigen
          </label>
          <label>
            <input type="checkbox" v-model="groupByType" />
            Nach Typ gruppieren
          </label>
        </div>
        
        <div class="setting-group">
          <label>Zoom-Level:</label>
          <input 
            type="range" 
            v-model="zoomLevel" 
            min="1" 
            max="10" 
            step="0.5"
            class="zoom-slider"
          />
          <span>{{ zoomLevel }}x</span>
        </div>
      </div>
    </div>

    <!-- Timeline Visualization -->
    <div class="timeline-visualization" v-if="viewMode === 'timeline'">
      <div class="timeline-ruler" ref="timelineRuler">
        <!-- Time Markers -->
        <div 
          v-for="marker in timeMarkers"
          :key="marker.time"
          class="time-marker"
          :style="{ left: getTimePosition(marker.time) + '%' }"
        >
          <div class="marker-line"></div>
          <div class="marker-label">{{ formatTime(marker.time) }}</div>
        </div>
        
        <!-- Current Time Indicator -->
        <div 
          class="current-time-indicator"
          :style="{ left: currentTimePosition + '%' }"
        >
          <div class="current-time-line"></div>
          <div class="current-time-handle" @mousedown="startTimelineDrag"></div>
        </div>
        
        <!-- Annotation Bars -->
        <div class="annotation-tracks">
          <div 
            v-for="(track, trackIndex) in annotationTracks"
            :key="trackIndex"
            class="annotation-track"
            :style="{ height: trackHeight + 'px' }"
          >
            <div
              v-for="annotation in track"
              :key="annotation.id"
              class="annotation-bar"
              :class="{ 
                selected: selectedAnnotation?.id === annotation.id,
                'ai-generated': annotation.is_ai_generated
              }"
              :style="getAnnotationBarStyle(annotation)"
              @click="selectAnnotation(annotation)"
              @contextmenu.prevent="showContextMenu(annotation, $event)"
              @mouseenter="showPreview(annotation, $event)"
              @mouseleave="hidePreview"
            >
              <div class="annotation-bar-content">
                <div class="annotation-title">{{ annotation.title }}</div>
                <div class="annotation-meta">
                  <span v-if="annotation.play_type" class="play-type">
                    {{ getPlayTypeLabel(annotation.play_type) }}
                  </span>
                  <span v-if="annotation.points_scored" class="points">
                    {{ annotation.points_scored }}pt
                  </span>
                </div>
              </div>
              
              <!-- Resize Handles -->
              <div class="resize-handle left" @mousedown="startResize(annotation, 'left', $event)"></div>
              <div class="resize-handle right" @mousedown="startResize(annotation, 'right', $event)"></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- List View -->
    <div class="timeline-list" v-else-if="viewMode === 'list'">
      <div class="list-header">
        <div class="sort-controls">
          <select v-model="sortBy" class="sort-select">
            <option value="start_time">Zeit</option>
            <option value="title">Titel</option>
            <option value="play_type">Typ</option>
            <option value="created_at">Erstellt</option>
            <option value="ai_confidence">Vertrauen</option>
          </select>
          <button 
            class="sort-direction-btn"
            @click="sortDirection = sortDirection === 'asc' ? 'desc' : 'asc'"
          >
            <i :class="sortDirection === 'asc' ? 'fas fa-sort-up' : 'fas fa-sort-down'"></i>
          </button>
        </div>
        
        <div class="bulk-actions" v-if="selectedAnnotations.length > 0">
          <span class="selection-count">{{ selectedAnnotations.length }} ausgewählt</span>
          <button @click="bulkEdit" class="bulk-btn">
            <i class="fas fa-edit"></i> Bearbeiten
          </button>
          <button @click="bulkDelete" class="bulk-btn delete">
            <i class="fas fa-trash"></i> Löschen
          </button>
        </div>
      </div>
      
      <div class="annotation-list-items">
        <div
          v-for="annotation in sortedFilteredAnnotations"
          :key="annotation.id"
          class="annotation-list-item"
          :class="{ 
            selected: selectedAnnotation?.id === annotation.id,
            'multi-selected': selectedAnnotations.includes(annotation.id)
          }"
          @click="handleListItemClick(annotation, $event)"
        >
          <!-- Thumbnail -->
          <div class="list-item-thumbnail" v-if="showThumbnails">
            <img 
              v-if="annotation.thumbnail_url"
              :src="annotation.thumbnail_url"
              :alt="annotation.title"
              class="thumbnail-img"
              @error="onThumbnailError"
            />
            <div v-else class="thumbnail-placeholder">
              <i :class="getAnnotationIcon(annotation)"></i>
            </div>
          </div>

          <!-- Content -->
          <div class="list-item-content">
            <div class="item-header">
              <h4 class="item-title">{{ annotation.title }}</h4>
              <div class="item-time">
                <span class="start-time">{{ formatTime(annotation.start_time) }}</span>
                <span class="duration">({{ formatDuration(annotation.end_time - annotation.start_time) }})</span>
              </div>
            </div>
            
            <div class="item-meta">
              <div class="meta-tags">
                <span 
                  v-if="annotation.play_type" 
                  class="meta-tag play-type"
                  :style="{ backgroundColor: annotation.color_code || '#007bff' }"
                >
                  {{ getPlayTypeLabel(annotation.play_type) }}
                </span>
                <span v-if="annotation.outcome" class="meta-tag outcome" :class="`outcome-${annotation.outcome}`">
                  {{ getOutcomeLabel(annotation.outcome) }}
                </span>
                <span v-if="annotation.is_ai_generated" class="meta-tag ai">
                  <i class="fas fa-robot"></i>
                  AI ({{ Math.round(annotation.ai_confidence * 100) }}%)
                </span>
                <span v-if="annotation.points_scored" class="meta-tag points">
                  <i class="fas fa-bullseye"></i>
                  {{ annotation.points_scored }} Punkt{{ annotation.points_scored !== 1 ? 'e' : '' }}
                </span>
              </div>
              
              <div class="item-description" v-if="annotation.description">
                {{ annotation.description }}
              </div>
              
              <div class="item-stats" v-if="showStatistics && annotation.statistical_data">
                <div v-if="annotation.statistical_data.shot_distance" class="stat-item">
                  <i class="fas fa-ruler"></i>
                  {{ annotation.statistical_data.shot_distance }}m
                </div>
                <div v-if="annotation.statistical_data.shot_angle" class="stat-item">
                  <i class="fas fa-angle-right"></i>
                  {{ annotation.statistical_data.shot_angle }}°
                </div>
                <div v-if="annotation.statistical_data.speed" class="stat-item">
                  <i class="fas fa-tachometer-alt"></i>
                  {{ annotation.statistical_data.speed }} km/h
                </div>
              </div>
            </div>
          </div>

          <!-- Actions -->
          <div class="list-item-actions">
            <button 
              class="action-btn play-btn"
              @click.stop="seekToAnnotation(annotation)"
              title="Zu Annotation springen"
            >
              <i class="fas fa-play"></i>
            </button>
            <button 
              class="action-btn edit-btn"
              @click.stop="editAnnotation(annotation)"
              title="Bearbeiten"
            >
              <i class="fas fa-edit"></i>
            </button>
            <button 
              class="action-btn delete-btn"
              @click.stop="deleteAnnotation(annotation)"
              title="Löschen"
            >
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Statistics View -->
    <div class="timeline-statistics" v-else-if="viewMode === 'stats'">
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-header">
            <i class="fas fa-chart-bar"></i>
            <h3>Annotation Typen</h3>
          </div>
          <div class="stat-content">
            <div class="type-distribution">
              <div 
                v-for="(count, type) in typeDistribution"
                :key="type"
                class="type-item"
              >
                <div class="type-bar">
                  <div 
                    class="type-fill"
                    :style="{ 
                      width: (count / totalAnnotations * 100) + '%',
                      backgroundColor: getTypeColor(type)
                    }"
                  ></div>
                </div>
                <span class="type-label">{{ getPlayTypeLabel(type) }}</span>
                <span class="type-count">{{ count }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <i class="fas fa-clock"></i>
            <h3>Zeitverteilung</h3>
          </div>
          <div class="stat-content">
            <canvas ref="timeDistributionChart" width="300" height="200"></canvas>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <i class="fas fa-bullseye"></i>
            <h3>Performance</h3>
          </div>
          <div class="stat-content">
            <div class="performance-metrics">
              <div class="metric-item">
                <span class="metric-label">Gesamtwürfe:</span>
                <span class="metric-value">{{ performanceStats.totalShots }}</span>
              </div>
              <div class="metric-item">
                <span class="metric-label">Trefferquote:</span>
                <span class="metric-value" :class="getPerformanceClass(performanceStats.shootingPercentage)">
                  {{ performanceStats.shootingPercentage }}%
                </span>
              </div>
              <div class="metric-item">
                <span class="metric-label">Gesamtpunkte:</span>
                <span class="metric-value">{{ performanceStats.totalPoints }}</span>
              </div>
              <div class="metric-item">
                <span class="metric-label">Pässe:</span>
                <span class="metric-value">{{ performanceStats.totalPasses }}</span>
              </div>
            </div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <i class="fas fa-robot"></i>
            <h3>AI Analyse</h3>
          </div>
          <div class="stat-content">
            <div class="ai-metrics">
              <div class="metric-item">
                <span class="metric-label">AI-Annotationen:</span>
                <span class="metric-value">{{ aiStats.aiGenerated }}</span>
              </div>
              <div class="metric-item">
                <span class="metric-label">Manuelle:</span>
                <span class="metric-value">{{ aiStats.manual }}</span>
              </div>
              <div class="metric-item">
                <span class="metric-label">Ø Vertrauen:</span>
                <span class="metric-value">{{ Math.round(aiStats.averageConfidence * 100) }}%</span>
              </div>
              <div class="ai-confidence-distribution">
                <div 
                  v-for="level in confidenceLevels"
                  :key="level.name"
                  class="confidence-bar"
                >
                  <div class="confidence-label">{{ level.label }}</div>
                  <div class="confidence-bar-bg">
                    <div 
                      class="confidence-fill"
                      :style="{ 
                        width: (level.count / aiStats.aiGenerated * 100) + '%',
                        backgroundColor: level.color
                      }"
                    ></div>
                  </div>
                  <span class="confidence-count">{{ level.count }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Context Menu -->
    <div 
      v-if="contextMenu.show"
      class="context-menu"
      :style="{ left: contextMenu.x + 'px', top: contextMenu.y + 'px' }"
      @click.stop
    >
      <div class="context-menu-item" @click="seekToAnnotation(contextMenu.annotation)">
        <i class="fas fa-play"></i>
        Zu Annotation springen
      </div>
      <div class="context-menu-item" @click="editAnnotation(contextMenu.annotation)">
        <i class="fas fa-edit"></i>
        Bearbeiten
      </div>
      <div class="context-menu-item" @click="duplicateAnnotation(contextMenu.annotation)">
        <i class="fas fa-copy"></i>
        Duplizieren
      </div>
      <hr>
      <div class="context-menu-item danger" @click="deleteAnnotation(contextMenu.annotation)">
        <i class="fas fa-trash"></i>
        Löschen
      </div>
    </div>

    <!-- Annotation Preview -->
    <div 
      v-if="preview.show"
      class="annotation-preview"
      :style="{ left: preview.x + 'px', top: preview.y + 'px' }"
    >
      <div class="preview-header">
        <h4>{{ preview.annotation.title }}</h4>
        <div class="preview-time">
          {{ formatTimeRange(preview.annotation) }}
        </div>
      </div>
      <div class="preview-content" v-if="preview.annotation.description">
        {{ preview.annotation.description }}
      </div>
      <div class="preview-thumbnail" v-if="preview.annotation.thumbnail_url">
        <img :src="preview.annotation.thumbnail_url" alt="Preview" />
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'TimelineAnnotations',
  props: {
    annotations: {
      type: Array,
      default: () => []
    },
    currentTime: {
      type: Number,
      default: 0
    },
    duration: {
      type: Number,
      default: 0
    },
    selectedAnnotation: {
      type: Object,
      default: null
    }
  },
  data() {
    return {
      // View settings
      viewMode: 'timeline', // 'timeline', 'list', 'stats'
      showSettings: false,
      showThumbnails: true,
      showStatistics: true,
      groupByType: false,
      zoomLevel: 1,
      
      // Filters and search
      selectedFilter: '',
      searchQuery: '',
      sortBy: 'start_time',
      sortDirection: 'asc',
      
      // Selection
      selectedAnnotations: [],
      
      // Timeline specific
      trackHeight: 40,
      isDragging: false,
      isResizing: false,
      
      // Context menu
      contextMenu: {
        show: false,
        x: 0,
        y: 0,
        annotation: null
      },
      
      // Preview
      preview: {
        show: false,
        x: 0,
        y: 0,
        annotation: null
      },
      
      // Constants
      viewModes: [
        { value: 'timeline', label: 'Timeline', icon: 'fas fa-chart-gantt' },
        { value: 'list', label: 'Liste', icon: 'fas fa-list' },
        { value: 'stats', label: 'Statistik', icon: 'fas fa-chart-bar' }
      ],
      
      annotationTypes: [
        { value: 'shot', label: 'Würfe' },
        { value: 'pass', label: 'Pässe' },
        { value: 'rebound', label: 'Rebounds' },
        { value: 'steal', label: 'Ballgewinne' },
        { value: 'block', label: 'Blocks' },
        { value: 'foul', label: 'Fouls' },
        { value: 'turnover', label: 'Ballverluste' }
      ]
    }
  },
  computed: {
    filteredAnnotations() {
      let filtered = this.annotations
      
      // Filter by type
      if (this.selectedFilter) {
        filtered = filtered.filter(a => a.play_type === this.selectedFilter)
      }
      
      // Search filter
      if (this.searchQuery) {
        const query = this.searchQuery.toLowerCase()
        filtered = filtered.filter(a => 
          a.title.toLowerCase().includes(query) ||
          (a.description && a.description.toLowerCase().includes(query)) ||
          (a.player_involved && a.player_involved.toLowerCase().includes(query))
        )
      }
      
      return filtered
    },

    sortedFilteredAnnotations() {
      const sorted = [...this.filteredAnnotations].sort((a, b) => {
        let aValue = a[this.sortBy]
        let bValue = b[this.sortBy]
        
        if (typeof aValue === 'string') {
          aValue = aValue.toLowerCase()
          bValue = bValue.toLowerCase()
        }
        
        if (this.sortDirection === 'asc') {
          return aValue > bValue ? 1 : -1
        } else {
          return aValue < bValue ? 1 : -1
        }
      })
      
      return sorted
    },

    annotationTracks() {
      const tracks = []
      const sortedAnnotations = [...this.filteredAnnotations].sort((a, b) => a.start_time - b.start_time)
      
      sortedAnnotations.forEach(annotation => {
        let placed = false
        
        // Try to place in existing track
        for (let track of tracks) {
          const lastInTrack = track[track.length - 1]
          if (!lastInTrack || lastInTrack.end_time <= annotation.start_time) {
            track.push(annotation)
            placed = true
            break
          }
        }
        
        // Create new track if needed
        if (!placed) {
          tracks.push([annotation])
        }
      })
      
      return tracks
    },

    timeMarkers() {
      if (!this.duration) return []
      
      const markers = []
      const interval = this.duration / 10 // 10 markers
      
      for (let i = 0; i <= 10; i++) {
        markers.push({
          time: Math.round(i * interval),
          label: this.formatTime(i * interval)
        })
      }
      
      return markers
    },

    currentTimePosition() {
      if (!this.duration) return 0
      return (this.currentTime / this.duration) * 100
    },

    totalAnnotations() {
      return this.annotations.length
    },

    typeDistribution() {
      const distribution = {}
      this.annotations.forEach(annotation => {
        const type = annotation.play_type || 'other'
        distribution[type] = (distribution[type] || 0) + 1
      })
      return distribution
    },

    performanceStats() {
      const shots = this.annotations.filter(a => a.play_type === 'shot')
      const successfulShots = shots.filter(a => a.outcome === 'successful')
      const passes = this.annotations.filter(a => a.play_type === 'pass')
      
      return {
        totalShots: shots.length,
        successfulShots: successfulShots.length,
        shootingPercentage: shots.length > 0 ? Math.round((successfulShots.length / shots.length) * 100) : 0,
        totalPoints: this.annotations.reduce((sum, a) => sum + (a.points_scored || 0), 0),
        totalPasses: passes.length
      }
    },

    aiStats() {
      const aiGenerated = this.annotations.filter(a => a.is_ai_generated)
      const manual = this.annotations.filter(a => !a.is_ai_generated)
      
      const averageConfidence = aiGenerated.length > 0 
        ? aiGenerated.reduce((sum, a) => sum + (a.ai_confidence || 0), 0) / aiGenerated.length
        : 0
      
      return {
        aiGenerated: aiGenerated.length,
        manual: manual.length,
        averageConfidence
      }
    },

    confidenceLevels() {
      const levels = [
        { name: 'high', label: 'Hoch (>80%)', color: '#28a745', count: 0 },
        { name: 'medium', label: 'Mittel (60-80%)', color: '#ffc107', count: 0 },
        { name: 'low', label: 'Niedrig (<60%)', color: '#dc3545', count: 0 }
      ]
      
      this.annotations
        .filter(a => a.is_ai_generated)
        .forEach(annotation => {
          const confidence = annotation.ai_confidence * 100
          if (confidence > 80) levels[0].count++
          else if (confidence >= 60) levels[1].count++
          else levels[2].count++
        })
      
      return levels
    }
  },
  mounted() {
    this.setupEventListeners()
    this.drawTimeDistributionChart()
  },
  beforeUnmount() {
    this.removeEventListeners()
  },
  watch: {
    annotations: {
      handler() {
        this.$nextTick(() => {
          this.drawTimeDistributionChart()
        })
      },
      deep: true
    },
    
    viewMode() {
      this.$nextTick(() => {
        if (this.viewMode === 'stats') {
          this.drawTimeDistributionChart()
        }
      })
    }
  },
  methods: {
    // Timeline positioning
    getTimePosition(time) {
      if (!this.duration) return 0
      return (time / this.duration) * 100
    },

    getAnnotationBarStyle(annotation) {
      const left = this.getTimePosition(annotation.start_time)
      const width = this.getTimePosition(annotation.end_time) - left
      
      return {
        left: left + '%',
        width: Math.max(width, 0.5) + '%',
        backgroundColor: annotation.color_code || '#007bff',
        height: (this.trackHeight - 4) + 'px'
      }
    },

    // Event handlers
    selectAnnotation(annotation) {
      this.$emit('annotation-select', annotation)
    },

    handleListItemClick(annotation, event) {
      if (event.ctrlKey || event.metaKey) {
        // Multi-select
        const index = this.selectedAnnotations.indexOf(annotation.id)
        if (index > -1) {
          this.selectedAnnotations.splice(index, 1)
        } else {
          this.selectedAnnotations.push(annotation.id)
        }
      } else {
        // Single select
        this.selectedAnnotations = []
        this.selectAnnotation(annotation)
      }
    },

    seekToAnnotation(annotation) {
      this.$emit('seek-to-time', annotation.start_time)
      this.selectAnnotation(annotation)
    },

    editAnnotation(annotation) {
      this.$emit('annotation-edit', annotation)
      this.hideContextMenu()
    },

    deleteAnnotation(annotation) {
      if (confirm('Annotation wirklich löschen?')) {
        this.$emit('annotation-delete', annotation)
      }
      this.hideContextMenu()
    },

    duplicateAnnotation(annotation) {
      const duplicate = {
        ...annotation,
        title: annotation.title + ' (Kopie)',
        start_time: annotation.start_time + 5,
        end_time: annotation.end_time + 5
      }
      this.$emit('annotation-duplicate', duplicate)
      this.hideContextMenu()
    },

    // Context menu
    showContextMenu(annotation, event) {
      event.preventDefault()
      this.contextMenu = {
        show: true,
        x: event.clientX,
        y: event.clientY,
        annotation
      }
    },

    hideContextMenu() {
      this.contextMenu.show = false
    },

    // Preview
    showPreview(annotation, event) {
      this.preview = {
        show: true,
        x: event.clientX + 10,
        y: event.clientY - 10,
        annotation
      }
    },

    hidePreview() {
      this.preview.show = false
    },

    // Timeline dragging
    startTimelineDrag(event) {
      this.isDragging = true
      document.addEventListener('mousemove', this.handleTimelineDrag)
      document.addEventListener('mouseup', this.endTimelineDrag)
    },

    handleTimelineDrag(event) {
      if (!this.isDragging) return
      
      const ruler = this.$refs.timelineRuler
      if (!ruler) return
      
      const rect = ruler.getBoundingClientRect()
      const percent = Math.max(0, Math.min(100, ((event.clientX - rect.left) / rect.width) * 100))
      const time = (percent / 100) * this.duration
      
      this.$emit('seek-to-time', time)
    },

    endTimelineDrag() {
      this.isDragging = false
      document.removeEventListener('mousemove', this.handleTimelineDrag)
      document.removeEventListener('mouseup', this.endTimelineDrag)
    },

    // Annotation resizing
    startResize(annotation, handle, event) {
      event.stopPropagation()
      this.isResizing = true
      this.resizeData = { annotation, handle, startX: event.clientX }
      
      document.addEventListener('mousemove', this.handleResize)
      document.addEventListener('mouseup', this.endResize)
    },

    handleResize(event) {
      if (!this.isResizing) return
      
      const { annotation, handle, startX } = this.resizeData
      const deltaX = event.clientX - startX
      const ruler = this.$refs.timelineRuler
      const deltaTime = (deltaX / ruler.clientWidth) * this.duration
      
      let newStartTime = annotation.start_time
      let newEndTime = annotation.end_time
      
      if (handle === 'left') {
        newStartTime = Math.max(0, annotation.start_time + deltaTime)
        newStartTime = Math.min(newStartTime, annotation.end_time - 1)
      } else {
        newEndTime = Math.min(this.duration, annotation.end_time + deltaTime)
        newEndTime = Math.max(newEndTime, annotation.start_time + 1)
      }
      
      this.$emit('annotation-resize', {
        annotation,
        startTime: newStartTime,
        endTime: newEndTime
      })
    },

    endResize() {
      this.isResizing = false
      this.resizeData = null
      document.removeEventListener('mousemove', this.handleResize)
      document.removeEventListener('mouseup', this.endResize)
    },

    // Bulk actions
    bulkEdit() {
      this.$emit('bulk-edit', this.selectedAnnotations)
    },

    bulkDelete() {
      if (confirm(`${this.selectedAnnotations.length} Annotationen wirklich löschen?`)) {
        this.$emit('bulk-delete', this.selectedAnnotations)
        this.selectedAnnotations = []
      }
    },

    // Charts
    drawTimeDistributionChart() {
      const canvas = this.$refs.timeDistributionChart
      if (!canvas) return
      
      const ctx = canvas.getContext('2d')
      const width = canvas.width
      const height = canvas.height
      
      // Clear canvas
      ctx.clearRect(0, 0, width, height)
      
      // Create time bins (10 minute intervals)
      const binSize = Math.max(600, this.duration / 10) // 10 minutes or duration/10
      const bins = Math.ceil(this.duration / binSize)
      const data = new Array(bins).fill(0)
      
      // Count annotations per bin
      this.annotations.forEach(annotation => {
        const bin = Math.floor(annotation.start_time / binSize)
        if (bin < bins) data[bin]++
      })
      
      // Find max for scaling
      const maxCount = Math.max(...data)
      if (maxCount === 0) return
      
      // Draw bars
      const barWidth = width / bins
      data.forEach((count, index) => {
        const barHeight = (count / maxCount) * (height - 40)
        const x = index * barWidth
        const y = height - barHeight - 20
        
        ctx.fillStyle = '#007bff'
        ctx.fillRect(x + 2, y, barWidth - 4, barHeight)
        
        // Label
        ctx.fillStyle = '#333'
        ctx.font = '10px Arial'
        ctx.textAlign = 'center'
        ctx.fillText(
          this.formatTime(index * binSize),
          x + barWidth / 2,
          height - 5
        )
      })
    },

    // Event listeners
    setupEventListeners() {
      document.addEventListener('click', this.hideContextMenu)
    },

    removeEventListeners() {
      document.removeEventListener('click', this.hideContextMenu)
    },

    // Utility methods
    getAnnotationIcon(annotation) {
      const icons = {
        'shot': 'fas fa-bullseye',
        'pass': 'fas fa-arrow-right',
        'dribble': 'fas fa-basketball-ball',
        'rebound': 'fas fa-hand-paper',
        'steal': 'fas fa-hand-grabbing',
        'block': 'fas fa-shield-alt',
        'foul': 'fas fa-exclamation-triangle'
      }
      return icons[annotation.play_type] || 'fas fa-comment'
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
        'free_throw': 'Freiwurf'
      }
      return labels[playType] || playType
    },

    getOutcomeLabel(outcome) {
      const labels = {
        'successful': 'Erfolgreich',
        'unsuccessful': 'Verfehlt',
        'neutral': 'Neutral'
      }
      return labels[outcome] || outcome
    },

    getTypeColor(type) {
      const colors = {
        'shot': '#dc3545',
        'pass': '#28a745',
        'rebound': '#ffc107',
        'steal': '#17a2b8',
        'block': '#6f42c1',
        'foul': '#fd7e14'
      }
      return colors[type] || '#6c757d'
    },

    getPerformanceClass(percentage) {
      if (percentage >= 50) return 'good'
      if (percentage >= 35) return 'average'
      return 'poor'
    },

    formatTime(seconds) {
      const minutes = Math.floor(seconds / 60)
      const secs = Math.floor(seconds % 60)
      return `${minutes}:${secs.toString().padStart(2, '0')}`
    },

    formatDuration(seconds) {
      if (seconds < 60) return `${Math.floor(seconds)}s`
      const minutes = Math.floor(seconds / 60)
      const secs = Math.floor(seconds % 60)
      return `${minutes}m ${secs}s`
    },

    formatTimeRange(annotation) {
      return `${this.formatTime(annotation.start_time)} - ${this.formatTime(annotation.end_time)}`
    },

    onThumbnailError(event) {
      event.target.style.display = 'none'
      event.target.nextElementSibling.style.display = 'flex'
    }
  }
}
</script>

<style scoped>
.timeline-annotations {
  background: white;
  border-radius: 8px;
  overflow: hidden;
}

.timeline-header {
  background: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
  padding: 15px 20px;
}

.timeline-controls {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  margin-bottom: 15px;
}

.view-mode-selector {
  display: flex;
  gap: 5px;
}

.mode-btn {
  background: white;
  border: 1px solid #dee2e6;
  padding: 8px 12px;
  border-radius: 6px;
  cursor: pointer;
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.9rem;
}

.mode-btn:hover {
  background: #e9ecef;
}

.mode-btn.active {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.filter-controls {
  display: flex;
  gap: 15px;
  align-items: center;
}

.filter-select {
  padding: 6px 12px;
  border: 1px solid #ced4da;
  border-radius: 4px;
  background: white;
}

.search-box {
  position: relative;
  display: flex;
  align-items: center;
}

.search-box i {
  position: absolute;
  left: 10px;
  color: #6c757d;
  z-index: 1;
}

.search-input {
  padding: 6px 12px 6px 35px;
  border: 1px solid #ced4da;
  border-radius: 4px;
  width: 200px;
}

.settings-btn {
  background: transparent;
  border: 1px solid #dee2e6;
  padding: 8px;
  border-radius: 6px;
  cursor: pointer;
  color: #6c757d;
  transition: all 0.2s ease;
}

.settings-btn:hover,
.settings-btn.active {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.settings-panel {
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  padding: 15px;
  display: flex;
  gap: 20px;
  align-items: center;
}

.setting-group {
  display: flex;
  gap: 15px;
  align-items: center;
}

.setting-group label {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 0.9rem;
}

.zoom-slider {
  width: 100px;
}

/* Timeline Visualization */
.timeline-visualization {
  padding: 20px;
  background: #fafafa;
}

.timeline-ruler {
  position: relative;
  height: 200px;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  overflow: hidden;
}

.time-marker {
  position: absolute;
  top: 0;
  height: 100%;
}

.marker-line {
  width: 1px;
  height: 20px;
  background: #dee2e6;
}

.marker-label {
  position: absolute;
  top: 25px;
  transform: translateX(-50%);
  font-size: 0.75rem;
  color: #6c757d;
  white-space: nowrap;
}

.current-time-indicator {
  position: absolute;
  top: 0;
  height: 100%;
  z-index: 10;
}

.current-time-line {
  width: 2px;
  height: 100%;
  background: #dc3545;
}

.current-time-handle {
  position: absolute;
  top: 0;
  left: -6px;
  width: 14px;
  height: 14px;
  background: #dc3545;
  border: 2px solid white;
  border-radius: 50%;
  cursor: ew-resize;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.annotation-tracks {
  position: absolute;
  top: 50px;
  left: 0;
  right: 0;
  bottom: 20px;
}

.annotation-track {
  position: relative;
  margin-bottom: 2px;
}

.annotation-bar {
  position: absolute;
  border-radius: 4px;
  cursor: pointer;
  border: 1px solid rgba(0, 0, 0, 0.1);
  transition: all 0.2s ease;
  display: flex;
  align-items: center;
  padding: 4px 8px;
  overflow: hidden;
}

.annotation-bar:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
  z-index: 5;
}

.annotation-bar.selected {
  border: 2px solid #ffc107;
  box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.3);
  z-index: 6;
}

.annotation-bar.ai-generated {
  border-style: dashed;
}

.annotation-bar-content {
  flex: 1;
  overflow: hidden;
}

.annotation-title {
  color: white;
  font-weight: bold;
  font-size: 0.8rem;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}

.annotation-meta {
  display: flex;
  gap: 6px;
  margin-top: 2px;
}

.play-type,
.points {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  padding: 1px 4px;
  border-radius: 8px;
  font-size: 0.7rem;
  white-space: nowrap;
}

.resize-handle {
  position: absolute;
  top: 0;
  bottom: 0;
  width: 6px;
  cursor: ew-resize;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.resize-handle.left {
  left: -3px;
}

.resize-handle.right {
  right: -3px;
}

.annotation-bar:hover .resize-handle {
  opacity: 1;
  background: rgba(255, 255, 255, 0.5);
}

/* List View */
.timeline-list {
  background: white;
}

.list-header {
  padding: 15px 20px;
  background: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.sort-controls {
  display: flex;
  align-items: center;
  gap: 10px;
}

.sort-select {
  padding: 6px 12px;
  border: 1px solid #ced4da;
  border-radius: 4px;
  background: white;
}

.sort-direction-btn {
  background: transparent;
  border: 1px solid #dee2e6;
  padding: 6px 8px;
  border-radius: 4px;
  cursor: pointer;
  color: #6c757d;
}

.sort-direction-btn:hover {
  background: #e9ecef;
}

.bulk-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.selection-count {
  font-weight: bold;
  color: #007bff;
}

.bulk-btn {
  background: #007bff;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.9rem;
  display: flex;
  align-items: center;
  gap: 6px;
}

.bulk-btn.delete {
  background: #dc3545;
}

.bulk-btn:hover {
  opacity: 0.9;
}

.annotation-list-items {
  max-height: 400px;
  overflow-y: auto;
}

.annotation-list-item {
  display: flex;
  align-items: center;
  padding: 15px 20px;
  border-bottom: 1px solid #f1f3f4;
  cursor: pointer;
  transition: background-color 0.2s ease;
  gap: 15px;
}

.annotation-list-item:hover {
  background: #f8f9fa;
}

.annotation-list-item.selected {
  background: #e3f2fd;
  border-left: 4px solid #007bff;
}

.annotation-list-item.multi-selected {
  background: #fff3cd;
  border-left: 4px solid #ffc107;
}

.list-item-thumbnail {
  width: 60px;
  height: 40px;
  border-radius: 4px;
  overflow: hidden;
  background: #f8f9fa;
  flex-shrink: 0;
}

.thumbnail-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.thumbnail-placeholder {
  width: 100%;
  height: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6c757d;
  font-size: 1.2rem;
}

.list-item-content {
  flex: 1;
  min-width: 0;
}

.item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 6px;
}

.item-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: #333;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.item-time {
  font-family: 'Roboto Mono', monospace;
  font-size: 0.85rem;
  color: #6c757d;
  white-space: nowrap;
}

.duration {
  margin-left: 6px;
  opacity: 0.7;
}

.item-meta {
  margin-bottom: 8px;
}

.meta-tags {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 6px;
}

.meta-tag {
  padding: 2px 8px;
  border-radius: 12px;
  font-size: 0.75rem;
  color: white;
  white-space: nowrap;
}

.meta-tag.play-type {
  /* Background color set via :style */
}

.meta-tag.outcome {
  background: #6c757d;
}

.meta-tag.outcome.outcome-successful {
  background: #28a745;
}

.meta-tag.outcome.outcome-unsuccessful {
  background: #dc3545;
}

.meta-tag.ai {
  background: #17a2b8;
}

.meta-tag.points {
  background: #ffc107;
  color: #333;
}

.item-description {
  color: #6c757d;
  font-size: 0.9rem;
  line-height: 1.4;
  margin-bottom: 6px;
}

.item-stats {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.stat-item {
  display: flex;
  align-items: center;
  gap: 4px;
  color: #6c757d;
  font-size: 0.8rem;
}

.stat-item i {
  font-size: 0.75rem;
  opacity: 0.8;
}

.list-item-actions {
  display: flex;
  gap: 6px;
}

.action-btn {
  background: transparent;
  border: 1px solid #dee2e6;
  color: #6c757d;
  padding: 6px 8px;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.9rem;
}

.action-btn:hover {
  background: #e9ecef;
}

.action-btn.play-btn:hover {
  background: #007bff;
  color: white;
  border-color: #007bff;
}

.action-btn.edit-btn:hover {
  background: #ffc107;
  color: white;
  border-color: #ffc107;
}

.action-btn.delete-btn:hover {
  background: #dc3545;
  color: white;
  border-color: #dc3545;
}

/* Statistics View */
.timeline-statistics {
  padding: 20px;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
}

.stat-card {
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 8px;
  overflow: hidden;
}

.stat-header {
  background: #f8f9fa;
  padding: 15px 20px;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  align-items: center;
  gap: 10px;
}

.stat-header i {
  color: #007bff;
  font-size: 1.2rem;
}

.stat-header h3 {
  margin: 0;
  font-size: 1.1rem;
  color: #333;
}

.stat-content {
  padding: 20px;
}

.type-distribution {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.type-item {
  display: flex;
  align-items: center;
  gap: 10px;
}

.type-bar {
  flex: 1;
  height: 20px;
  background: #f8f9fa;
  border-radius: 10px;
  overflow: hidden;
}

.type-fill {
  height: 100%;
  transition: width 0.3s ease;
}

.type-label {
  min-width: 80px;
  font-size: 0.9rem;
  color: #333;
}

.type-count {
  min-width: 30px;
  text-align: right;
  font-weight: bold;
  color: #007bff;
}

.performance-metrics,
.ai-metrics {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.metric-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 8px 0;
  border-bottom: 1px solid #f1f3f4;
}

.metric-label {
  color: #6c757d;
  font-size: 0.9rem;
}

.metric-value {
  font-weight: bold;
  font-size: 1rem;
}

.metric-value.good {
  color: #28a745;
}

.metric-value.average {
  color: #ffc107;
}

.metric-value.poor {
  color: #dc3545;
}

.ai-confidence-distribution {
  margin-top: 15px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.confidence-bar {
  display: flex;
  align-items: center;
  gap: 10px;
}

.confidence-label {
  min-width: 100px;
  font-size: 0.8rem;
  color: #6c757d;
}

.confidence-bar-bg {
  flex: 1;
  height: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  overflow: hidden;
}

.confidence-fill {
  height: 100%;
  transition: width 0.3s ease;
}

.confidence-count {
  min-width: 25px;
  text-align: right;
  font-size: 0.8rem;
  color: #333;
}

/* Context Menu */
.context-menu {
  position: fixed;
  background: white;
  border: 1px solid #dee2e6;
  border-radius: 6px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
  z-index: 1000;
  min-width: 150px;
  overflow: hidden;
}

.context-menu-item {
  padding: 10px 15px;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 0.9rem;
  color: #333;
  transition: background-color 0.2s ease;
}

.context-menu-item:hover {
  background: #f8f9fa;
}

.context-menu-item.danger {
  color: #dc3545;
}

.context-menu-item.danger:hover {
  background: #f8d7da;
}

.context-menu hr {
  margin: 0;
  border: none;
  border-top: 1px solid #dee2e6;
}

/* Annotation Preview */
.annotation-preview {
  position: fixed;
  background: rgba(0, 0, 0, 0.9);
  color: white;
  padding: 15px;
  border-radius: 8px;
  max-width: 300px;
  z-index: 1000;
  pointer-events: none;
  backdrop-filter: blur(10px);
}

.preview-header {
  margin-bottom: 10px;
}

.preview-header h4 {
  margin: 0 0 5px 0;
  font-size: 1rem;
}

.preview-time {
  font-family: 'Roboto Mono', monospace;
  font-size: 0.8rem;
  color: #ccc;
}

.preview-content {
  margin-bottom: 10px;
  font-size: 0.9rem;
  line-height: 1.4;
  color: #ddd;
}

.preview-thumbnail {
  border-radius: 4px;
  overflow: hidden;
}

.preview-thumbnail img {
  width: 100%;
  height: auto;
  display: block;
}

/* Responsive Design */
@media (max-width: 768px) {
  .timeline-controls {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .filter-controls {
    flex-direction: column;
    gap: 10px;
  }

  .search-input {
    width: 100%;
  }

  .settings-panel {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .annotation-list-item {
    flex-direction: column;
    align-items: stretch;
    gap: 10px;
  }

  .item-header {
    flex-direction: column;
    align-items: stretch;
    gap: 5px;
  }

  .list-item-actions {
    align-self: center;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .type-item {
    flex-direction: column;
    align-items: stretch;
    gap: 5px;
  }

  .metric-item {
    flex-direction: column;
    align-items: stretch;
    text-align: center;
    gap: 5px;
  }
}
</style>