<template>
  <div class="shot-chart-container">
    <!-- Header mit Controls -->
    <div class="shot-chart-header">
      <h3 class="chart-title">
        <i class="fas fa-bullseye"></i>
        Shot Chart {{ playerName ? `- ${playerName}` : '' }}
      </h3>
      
      <div class="chart-controls">
        <!-- View Mode Toggle -->
        <div class="btn-group" role="group">
          <button 
            class="btn btn-sm"
            :class="{ 'btn-primary': viewMode === 'shots', 'btn-outline-primary': viewMode !== 'shots' }"
            @click="viewMode = 'shots'"
          >
            <i class="fas fa-circle"></i> Shot Chart
          </button>
          <button 
            class="btn btn-sm"
            :class="{ 'btn-primary': viewMode === 'heatmap', 'btn-outline-primary': viewMode !== 'heatmap' }"
            @click="viewMode = 'heatmap'"
          >
            <i class="fas fa-fire"></i> Heat Map
          </button>
        </div>

        <!-- Shot Type Filter -->
        <select v-model="selectedShotType" class="form-select form-select-sm">
          <option value="all">Alle Würfe</option>
          <option value="field_goal">2-Punkte</option>
          <option value="three_point">3-Punkte</option>
          <option value="free_throw">Freiwürfe</option>
        </select>

        <!-- Time Period Filter -->
        <select v-model="selectedPeriod" class="form-select form-select-sm">
          <option value="all">Alle Perioden</option>
          <option value="1">Q1</option>
          <option value="2">Q2</option>
          <option value="3">Q3</option>
          <option value="4">Q4</option>
          <option value="overtime">Overtime</option>
        </select>

        <!-- Export Button -->
        <button class="btn btn-sm btn-outline-success" @click="exportChart">
          <i class="fas fa-download"></i> Export
        </button>
      </div>
    </div>

    <!-- Shot Chart Canvas -->
    <div class="chart-canvas-container" ref="canvasContainer">
      <canvas 
        ref="chartCanvas"
        class="shot-chart-canvas"
        :width="canvasWidth"
        :height="canvasHeight"
        @mousemove="handleMouseMove"
        @click="handleCanvasClick"
        @mouseleave="hideTooltip"
      ></canvas>

      <!-- Tooltip -->
      <div 
        class="shot-tooltip" 
        v-if="tooltip.show"
        :style="{ left: tooltip.x + 'px', top: tooltip.y + 'px' }"
      >
        <div class="tooltip-content">
          <strong>{{ tooltip.data?.description || 'Unbekannt' }}</strong>
          <br>
          <span class="text-muted">{{ tooltip.data?.displayTime }}</span>
          <br>
          <span :class="tooltip.data?.is_successful ? 'text-success' : 'text-danger'">
            {{ tooltip.data?.is_successful ? 'Getroffen' : 'Verfehlt' }}
          </span>
          <br>
          <small class="text-muted">
            Distanz: {{ tooltip.data?.shot_distance?.toFixed(1) }}m
          </small>
        </div>
      </div>
    </div>

    <!-- Statistics Panel -->
    <div class="shot-statistics" v-if="showStatistics">
      <div class="row">
        <div class="col-md-3">
          <div class="stat-card text-center">
            <div class="stat-value">{{ statistics.totalShots }}</div>
            <div class="stat-label">Gesamt Würfe</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="stat-card text-center">
            <div class="stat-value text-success">{{ statistics.madeShots }}</div>
            <div class="stat-label">Getroffen</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="stat-card text-center">
            <div class="stat-value">{{ statistics.fieldGoalPercentage }}%</div>
            <div class="stat-label">FG%</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="stat-card text-center">
            <div class="stat-value">{{ statistics.averageDistance }}m</div>
            <div class="stat-label">Ø Distanz</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Zone Breakdown -->
    <div class="zone-breakdown" v-if="showZoneBreakdown">
      <h4>Zonen-Statistiken</h4>
      <div class="row">
        <div 
          class="col-md-4 mb-3" 
          v-for="zone in zoneStatistics" 
          :key="zone.name"
        >
          <div class="zone-card">
            <div class="zone-name">{{ zone.name }}</div>
            <div class="zone-stats">
              <span class="zone-made">{{ zone.made }}</span> / 
              <span class="zone-attempted">{{ zone.attempted }}</span>
              <span class="zone-percentage">({{ zone.percentage }}%)</span>
            </div>
            <div class="progress" style="height: 8px;">
              <div 
                class="progress-bar" 
                :class="getZonePercentageColor(zone.percentage)"
                :style="{ width: zone.percentage + '%' }"
              ></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ShotChart',
  props: {
    gameActions: {
      type: Array,
      default: () => []
    },
    playerId: {
      type: Number,
      default: null
    },
    playerName: {
      type: String,
      default: null
    },
    teamId: {
      type: Number,
      default: null
    },
    showStatistics: {
      type: Boolean,
      default: true
    },
    showZoneBreakdown: {
      type: Boolean,
      default: true
    },
    realtime: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      viewMode: 'shots', // 'shots' oder 'heatmap'
      selectedShotType: 'all',
      selectedPeriod: 'all',
      canvasWidth: 800,
      canvasHeight: 600,
      tooltip: {
        show: false,
        x: 0,
        y: 0,
        data: null
      },
      // Basketball Court Dimensions (in meters)
      court: {
        width: 28, // Offizielle FIBA Breite
        length: 15, // Halbe Länge (nur eine Hälfte)
        freeThrowLine: 5.8,
        threePointLine: 6.75,
        basketPosition: { x: 14, y: 7.5 }, // Mitte der Grundlinie
        paintWidth: 4.9
      }
    }
  },
  computed: {
    filteredShots() {
      let shots = this.gameActions.filter(action => action.is_shot)
      
      if (this.playerId) {
        shots = shots.filter(shot => shot.player_id === this.playerId)
      }
      
      if (this.teamId) {
        shots = shots.filter(shot => shot.team_id === this.teamId)
      }
      
      if (this.selectedShotType !== 'all') {
        shots = shots.filter(shot => shot.action_type.startsWith(this.selectedShotType))
      }
      
      if (this.selectedPeriod !== 'all') {
        if (this.selectedPeriod === 'overtime') {
          shots = shots.filter(shot => shot.period > 4)
        } else {
          shots = shots.filter(shot => shot.period === parseInt(this.selectedPeriod))
        }
      }
      
      return shots
    },
    
    statistics() {
      const shots = this.filteredShots
      const madeShots = shots.filter(shot => shot.is_successful).length
      const totalShots = shots.length
      const totalDistance = shots.reduce((sum, shot) => sum + (shot.shot_distance || 0), 0)
      
      return {
        totalShots,
        madeShots,
        missedShots: totalShots - madeShots,
        fieldGoalPercentage: totalShots > 0 ? Math.round((madeShots / totalShots) * 100) : 0,
        averageDistance: totalShots > 0 ? (totalDistance / totalShots).toFixed(1) : 0
      }
    },
    
    zoneStatistics() {
      const zones = [
        { name: 'Paint', filter: shot => this.isInPaint(shot) },
        { name: '3-Punkte-Zone', filter: shot => shot.is_three_pointer },
        { name: 'Mitteldistanz', filter: shot => this.isMidRange(shot) },
        { name: 'Freiwürfe', filter: shot => shot.is_free_throw }
      ]
      
      return zones.map(zone => {
        const zoneShots = this.filteredShots.filter(zone.filter)
        const made = zoneShots.filter(shot => shot.is_successful).length
        const attempted = zoneShots.length
        const percentage = attempted > 0 ? Math.round((made / attempted) * 100) : 0
        
        return {
          name: zone.name,
          made,
          attempted,
          percentage
        }
      })
    }
  },
  mounted() {
    this.initializeCanvas()
    this.drawChart()
    
    // Resize handler
    window.addEventListener('resize', this.handleResize)
    
    // Realtime updates
    if (this.realtime) {
      this.setupRealtimeUpdates()
    }
  },
  beforeUnmount() {
    window.removeEventListener('resize', this.handleResize)
  },
  watch: {
    gameActions: {
      handler() {
        this.drawChart()
      },
      deep: true
    },
    viewMode() {
      this.drawChart()
    },
    selectedShotType() {
      this.drawChart()
    },
    selectedPeriod() {
      this.drawChart()
    }
  },
  methods: {
    initializeCanvas() {
      const container = this.$refs.canvasContainer
      if (container) {
        const rect = container.getBoundingClientRect()
        this.canvasWidth = Math.min(rect.width, 800)
        this.canvasHeight = Math.round((this.canvasWidth / 28) * 15) // Aspect ratio
      }
    },
    
    drawChart() {
      const canvas = this.$refs.chartCanvas
      if (!canvas) return
      
      const ctx = canvas.getContext('2d')
      ctx.clearRect(0, 0, this.canvasWidth, this.canvasHeight)
      
      // Zeichne Basketballfeld
      this.drawCourt(ctx)
      
      if (this.viewMode === 'shots') {
        this.drawShots(ctx)
      } else {
        this.drawHeatmap(ctx)
      }
    },
    
    drawCourt(ctx) {
      const scaleX = this.canvasWidth / this.court.width
      const scaleY = this.canvasHeight / this.court.length
      
      ctx.strokeStyle = '#ffffff'
      ctx.lineWidth = 2
      
      // Grundlinie
      ctx.beginPath()
      ctx.moveTo(0, 0)
      ctx.lineTo(this.canvasWidth, 0)
      ctx.stroke()
      
      // Seitenlinien
      ctx.beginPath()
      ctx.moveTo(0, 0)
      ctx.lineTo(0, this.canvasHeight)
      ctx.moveTo(this.canvasWidth, 0)
      ctx.lineTo(this.canvasWidth, this.canvasHeight)
      ctx.stroke()
      
      // Korb
      const basketX = this.court.basketPosition.x * scaleX
      const basketY = this.court.basketPosition.y * scaleY
      ctx.beginPath()
      ctx.arc(basketX, basketY, 8, 0, 2 * Math.PI)
      ctx.fillStyle = '#ff6b35'
      ctx.fill()
      ctx.strokeStyle = '#ffffff'
      ctx.stroke()
      
      // Freiwurflinie
      const ftY = this.court.freeThrowLine * scaleY
      ctx.beginPath()
      ctx.moveTo(basketX - (this.court.paintWidth / 2) * scaleX, ftY)
      ctx.lineTo(basketX + (this.court.paintWidth / 2) * scaleX, ftY)
      ctx.strokeStyle = '#ffffff'
      ctx.stroke()
      
      // 3-Punkte-Linie (vereinfacht als Bogen)
      ctx.beginPath()
      ctx.arc(basketX, basketY, this.court.threePointLine * scaleX, -Math.PI/3, Math.PI/3)
      ctx.stroke()
      
      // Paint (Zone)
      ctx.beginPath()
      ctx.rect(
        basketX - (this.court.paintWidth / 2) * scaleX,
        0,
        this.court.paintWidth * scaleX,
        ftY
      )
      ctx.strokeStyle = '#ffffff'
      ctx.stroke()
    },
    
    drawShots(ctx) {
      this.filteredShots.forEach(shot => {
        if (!shot.shot_x || !shot.shot_y) return
        
        const x = this.courtToCanvasX(shot.shot_x)
        const y = this.courtToCanvasY(shot.shot_y)
        
        // Shot-Kreis
        ctx.beginPath()
        ctx.arc(x, y, 6, 0, 2 * Math.PI)
        
        // Farbe basierend auf Erfolg
        if (shot.is_successful) {
          ctx.fillStyle = '#28a745' // Grün für getroffen
          ctx.strokeStyle = '#155724'
        } else {
          ctx.fillStyle = '#dc3545' // Rot für verfehlt
          ctx.strokeStyle = '#721c24'
        }
        
        ctx.fill()
        ctx.lineWidth = 2
        ctx.stroke()
        
        // Shot-Typ Indikator
        if (shot.is_three_pointer) {
          ctx.fillStyle = '#ffffff'
          ctx.font = '10px Arial'
          ctx.textAlign = 'center'
          ctx.fillText('3', x, y + 3)
        }
      })
    },
    
    drawHeatmap(ctx) {
      // Einfache Heatmap-Implementation
      const gridSize = 20
      const heatmapData = this.calculateHeatmapData(gridSize)
      
      Object.keys(heatmapData).forEach(key => {
        const [x, y] = key.split(',').map(Number)
        const intensity = heatmapData[key].intensity
        
        if (intensity > 0) {
          const alpha = Math.min(intensity / 10, 0.8) // Max 80% Opacity
          ctx.fillStyle = `rgba(255, 107, 53, ${alpha})` // Orange Heat
          ctx.fillRect(x * gridSize, y * gridSize, gridSize, gridSize)
        }
      })
      
      // Overlay shots on heatmap
      this.drawShots(ctx)
    },
    
    calculateHeatmapData(gridSize) {
      const data = {}
      
      this.filteredShots.forEach(shot => {
        if (!shot.shot_x || !shot.shot_y) return
        
        const canvasX = this.courtToCanvasX(shot.shot_x)
        const canvasY = this.courtToCanvasY(shot.shot_y)
        
        const gridX = Math.floor(canvasX / gridSize)
        const gridY = Math.floor(canvasY / gridSize)
        const key = `${gridX},${gridY}`
        
        if (!data[key]) {
          data[key] = { intensity: 0, made: 0, attempted: 0 }
        }
        
        data[key].intensity += shot.is_successful ? 2 : 1
        data[key].attempted += 1
        if (shot.is_successful) data[key].made += 1
      })
      
      return data
    },
    
    courtToCanvasX(courtX) {
      return (courtX / this.court.width) * this.canvasWidth
    },
    
    courtToCanvasY(courtY) {
      return (courtY / this.court.length) * this.canvasHeight
    },
    
    canvasToCourtX(canvasX) {
      return (canvasX / this.canvasWidth) * this.court.width
    },
    
    canvasToCourtY(canvasY) {
      return (canvasY / this.canvasHeight) * this.court.length
    },
    
    handleMouseMove(event) {
      const canvas = this.$refs.chartCanvas
      const rect = canvas.getBoundingClientRect()
      const x = event.clientX - rect.left
      const y = event.clientY - rect.top
      
      // Finde nächsten Shot
      const nearestShot = this.findNearestShot(x, y)
      
      if (nearestShot) {
        this.tooltip = {
          show: true,
          x: event.clientX,
          y: event.clientY,
          data: nearestShot
        }
      } else {
        this.tooltip.show = false
      }
    },
    
    findNearestShot(canvasX, canvasY) {
      let nearestShot = null
      let minDistance = Infinity
      
      this.filteredShots.forEach(shot => {
        if (!shot.shot_x || !shot.shot_y) return
        
        const shotX = this.courtToCanvasX(shot.shot_x)
        const shotY = this.courtToCanvasY(shot.shot_y)
        
        const distance = Math.sqrt(
          Math.pow(canvasX - shotX, 2) + Math.pow(canvasY - shotY, 2)
        )
        
        if (distance < 15 && distance < minDistance) { // 15px Radius
          minDistance = distance
          nearestShot = shot
        }
      })
      
      return nearestShot
    },
    
    handleCanvasClick(event) {
      const canvas = this.$refs.chartCanvas
      const rect = canvas.getBoundingClientRect()
      const x = event.clientX - rect.left
      const y = event.clientY - rect.top
      
      const shot = this.findNearestShot(x, y)
      if (shot) {
        this.$emit('shot-selected', shot)
      }
    },
    
    hideTooltip() {
      this.tooltip.show = false
    },
    
    handleResize() {
      this.initializeCanvas()
      this.$nextTick(() => {
        this.drawChart()
      })
    },
    
    exportChart() {
      const canvas = this.$refs.chartCanvas
      const link = document.createElement('a')
      link.download = `shot-chart-${this.playerName || 'team'}-${Date.now()}.png`
      link.href = canvas.toDataURL()
      link.click()
    },
    
    setupRealtimeUpdates() {
      // Echo listener für Real-time Updates
      if (window.Echo) {
        window.Echo.channel('game-updates')
          .listen('GameActionAdded', (e) => {
            // Chart wird automatisch über gameActions prop aktualisiert
            this.drawChart()
          })
      }
    },
    
    // Zone-Hilfsfunktionen
    isInPaint(shot) {
      if (!shot.shot_x || !shot.shot_y) return false
      const basketX = this.court.basketPosition.x
      const basketY = this.court.basketPosition.y
      const paintHalfWidth = this.court.paintWidth / 2
      
      return Math.abs(shot.shot_x - basketX) <= paintHalfWidth &&
             shot.shot_y <= this.court.freeThrowLine
    },
    
    isMidRange(shot) {
      return !shot.is_three_pointer && 
             !shot.is_free_throw && 
             !this.isInPaint(shot)
    },
    
    getZonePercentageColor(percentage) {
      if (percentage >= 70) return 'bg-success'
      if (percentage >= 50) return 'bg-warning'
      if (percentage >= 30) return 'bg-info'
      return 'bg-danger'
    }
  }
}
</script>

<style scoped>
.shot-chart-container {
  background: #1a1a1a;
  border-radius: 12px;
  padding: 20px;
  color: white;
}

.shot-chart-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
  flex-wrap: wrap;
  gap: 15px;
}

.chart-title {
  display: flex;
  align-items: center;
  gap: 8px;
  color: white;
  margin: 0;
}

.chart-controls {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.chart-canvas-container {
  position: relative;
  background: #2d5016;
  border-radius: 8px;
  margin-bottom: 20px;
  overflow: hidden;
  border: 2px solid #ffffff;
}

.shot-chart-canvas {
  display: block;
  width: 100%;
  height: auto;
  cursor: crosshair;
}

.shot-tooltip {
  position: fixed;
  background: rgba(0, 0, 0, 0.9);
  color: white;
  padding: 8px 12px;
  border-radius: 6px;
  font-size: 12px;
  pointer-events: none;
  z-index: 1000;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
}

.shot-statistics {
  margin-bottom: 20px;
}

.stat-card {
  background: rgba(255, 255, 255, 0.1);
  padding: 15px;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.stat-value {
  font-size: 1.8rem;
  font-weight: bold;
  color: #ffffff;
}

.stat-label {
  font-size: 0.9rem;
  color: #cccccc;
  margin-top: 4px;
}

.zone-breakdown h4 {
  color: white;
  margin-bottom: 15px;
}

.zone-card {
  background: rgba(255, 255, 255, 0.1);
  padding: 15px;
  border-radius: 8px;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.zone-name {
  font-weight: bold;
  color: #ffffff;
  margin-bottom: 8px;
}

.zone-stats {
  color: #cccccc;
  margin-bottom: 8px;
}

.zone-made {
  color: #28a745;
  font-weight: bold;
}

.zone-attempted {
  color: #ffffff;
}

.zone-percentage {
  color: #ffc107;
  font-weight: bold;
}

/* Responsive Design */
@media (max-width: 768px) {
  .shot-chart-header {
    flex-direction: column;
    align-items: stretch;
  }
  
  .chart-controls {
    justify-content: center;
  }
  
  .shot-statistics .row > div {
    margin-bottom: 10px;
  }
}
</style>