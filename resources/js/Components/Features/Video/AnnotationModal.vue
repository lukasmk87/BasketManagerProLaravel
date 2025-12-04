<template>
  <div class="annotation-modal-overlay" @click="closeModal">
    <div class="annotation-modal" @click.stop>
      <!-- Modal Header -->
      <div class="modal-header">
        <h2>
          <i class="fas fa-comment-dots"></i>
          {{ isEditing ? 'Annotation bearbeiten' : 'Neue Annotation erstellen' }}
        </h2>
        <button class="close-btn" @click="closeModal">
          <i class="fas fa-times"></i>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="modal-body">
        <form @submit.prevent="saveAnnotation">
          <!-- Basic Information -->
          <div class="form-section">
            <h3>
              <i class="fas fa-info-circle"></i>
              Grundinformationen
            </h3>
            
            <div class="form-row">
              <div class="form-group">
                <label for="title">Titel *</label>
                <input
                  id="title"
                  type="text"
                  v-model="form.title"
                  class="form-control"
                  :class="{ 'error': errors.title }"
                  placeholder="Annotationstitel eingeben..."
                  required
                />
                <div v-if="errors.title" class="error-message">{{ errors.title }}</div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="description">Beschreibung</label>
                <textarea
                  id="description"
                  v-model="form.description"
                  class="form-control textarea"
                  :class="{ 'error': errors.description }"
                  placeholder="Detaillierte Beschreibung der Annotation..."
                  rows="3"
                ></textarea>
                <div v-if="errors.description" class="error-message">{{ errors.description }}</div>
              </div>
            </div>
          </div>

          <!-- Time Settings -->
          <div class="form-section">
            <h3>
              <i class="fas fa-clock"></i>
              Zeitbereich
            </h3>
            
            <div class="form-row">
              <div class="form-group">
                <label for="start-time">Startzeit (Sekunden) *</label>
                <input
                  id="start-time"
                  type="number"
                  v-model.number="form.start_time"
                  class="form-control"
                  :class="{ 'error': errors.start_time }"
                  min="0"
                  :max="videoDuration"
                  step="0.1"
                  required
                />
                <div class="time-display">{{ formatTime(form.start_time) }}</div>
                <div v-if="errors.start_time" class="error-message">{{ errors.start_time }}</div>
              </div>
              
              <div class="form-group">
                <label for="end-time">Endzeit (Sekunden) *</label>
                <input
                  id="end-time"
                  type="number"
                  v-model.number="form.end_time"
                  class="form-control"
                  :class="{ 'error': errors.end_time }"
                  :min="form.start_time + 0.1"
                  :max="videoDuration"
                  step="0.1"
                  required
                />
                <div class="time-display">{{ formatTime(form.end_time) }}</div>
                <div v-if="errors.end_time" class="error-message">{{ errors.end_time }}</div>
              </div>
            </div>

            <div class="duration-info">
              <i class="fas fa-hourglass-half"></i>
              Dauer: {{ formatDuration(form.end_time - form.start_time) }}
            </div>

            <!-- Time Range Slider -->
            <div class="time-range-slider">
              <div class="slider-track">
                <div 
                  class="slider-range"
                  :style="rangeSliderStyle"
                ></div>
                <input
                  type="range"
                  class="slider-input start"
                  v-model.number="form.start_time"
                  :min="0"
                  :max="videoDuration"
                  :step="0.1"
                />
                <input
                  type="range"
                  class="slider-input end"
                  v-model.number="form.end_time"
                  :min="0"
                  :max="videoDuration"
                  :step="0.1"
                />
              </div>
            </div>
          </div>

          <!-- Basketball-specific Settings -->
          <div class="form-section">
            <h3>
              <i class="fas fa-basketball-ball"></i>
              Basketball-Eigenschaften
            </h3>
            
            <div class="form-row">
              <div class="form-group">
                <label for="annotation-type">Annotationstyp</label>
                <select
                  id="annotation-type"
                  v-model="form.annotation_type"
                  class="form-control"
                  :class="{ 'error': errors.annotation_type }"
                >
                  <option value="play_action">Spielaktion</option>
                  <option value="statistical_event">Statistisches Ereignis</option>
                  <option value="coaching_note">Trainer-Notiz</option>
                  <option value="tactical_analysis">Taktische Analyse</option>
                  <option value="player_performance">Spieler-Leistung</option>
                  <option value="referee_decision">Schiedsrichter-Entscheidung</option>
                  <option value="highlight_moment">Highlight-Moment</option>
                </select>
                <div v-if="errors.annotation_type" class="error-message">{{ errors.annotation_type }}</div>
              </div>
              
              <div class="form-group">
                <label for="play-type">Spielzugtyp</label>
                <select
                  id="play-type"
                  v-model="form.play_type"
                  class="form-control"
                  :class="{ 'error': errors.play_type }"
                >
                  <option value="">-- Auswählen --</option>
                  <option value="shot">Wurf</option>
                  <option value="free_throw">Freiwurf</option>
                  <option value="pass">Pass</option>
                  <option value="dribble">Dribbling</option>
                  <option value="rebound">Rebound</option>
                  <option value="steal">Ballgewinn</option>
                  <option value="block">Block</option>
                  <option value="foul">Foul</option>
                  <option value="turnover">Ballverlust</option>
                  <option value="timeout">Auszeit</option>
                  <option value="substitution">Wechsel</option>
                </select>
                <div v-if="errors.play_type" class="error-message">{{ errors.play_type }}</div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="outcome">Ergebnis</label>
                <select
                  id="outcome"
                  v-model="form.outcome"
                  class="form-control"
                  :class="{ 'error': errors.outcome }"
                >
                  <option value="">-- Auswählen --</option>
                  <option value="successful">Erfolgreich</option>
                  <option value="unsuccessful">Erfolglos</option>
                  <option value="neutral">Neutral</option>
                  <option value="positive">Positiv</option>
                  <option value="negative">Negativ</option>
                </select>
                <div v-if="errors.outcome" class="error-message">{{ errors.outcome }}</div>
              </div>
              
              <div class="form-group">
                <label for="points-scored">Erzielte Punkte</label>
                <input
                  id="points-scored"
                  type="number"
                  v-model.number="form.points_scored"
                  class="form-control"
                  :class="{ 'error': errors.points_scored }"
                  min="0"
                  max="10"
                />
                <div v-if="errors.points_scored" class="error-message">{{ errors.points_scored }}</div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="player-involved">Beteiligter Spieler</label>
                <input
                  id="player-involved"
                  type="text"
                  v-model="form.player_involved"
                  class="form-control"
                  :class="{ 'error': errors.player_involved }"
                  placeholder="Name oder Nummer des Spielers..."
                />
                <div v-if="errors.player_involved" class="error-message">{{ errors.player_involved }}</div>
              </div>
              
              <div class="form-group">
                <label for="team-involved">Beteiligtes Team</label>
                <select
                  id="team-involved"
                  v-model="form.team_involved"
                  class="form-control"
                  :class="{ 'error': errors.team_involved }"
                >
                  <option value="">-- Auswählen --</option>
                  <option value="home">Heimteam</option>
                  <option value="away">Auswärtsteam</option>
                  <option value="both">Beide Teams</option>
                  <option value="neutral">Neutral</option>
                </select>
                <div v-if="errors.team_involved" class="error-message">{{ errors.team_involved }}</div>
              </div>
            </div>
          </div>

          <!-- Court Position -->
          <div class="form-section">
            <h3>
              <i class="fas fa-map-marker-alt"></i>
              Spielfeldposition
            </h3>
            
            <div class="court-position-section">
              <!-- Basketball Court Visualization -->
              <div class="court-selector">
                <svg 
                  class="court-svg"
                  viewBox="0 0 500 300"
                  @click="selectCourtPosition"
                >
                  <!-- Court Background -->
                  <rect width="500" height="300" fill="#d2691e" stroke="#fff" stroke-width="2"/>
                  
                  <!-- Court Lines -->
                  <g class="court-lines" stroke="#fff" stroke-width="2" fill="none">
                    <!-- Center Line -->
                    <line x1="250" y1="0" x2="250" y2="300"/>
                    <!-- Center Circle -->
                    <circle cx="250" cy="150" r="60"/>
                    <!-- Free Throw Circles -->
                    <circle cx="250" cy="75" r="60"/>
                    <circle cx="250" cy="225" r="60"/>
                    <!-- Three Point Arcs -->
                    <path d="M 70 75 A 180 180 0 0 1 430 75"/>
                    <path d="M 70 225 A 180 180 0 0 0 430 225"/>
                    <!-- Key Areas -->
                    <rect x="190" y="0" width="120" height="95"/>
                    <rect x="190" y="205" width="120" height="95"/>
                    <!-- Baskets -->
                    <circle cx="250" cy="19" r="9" stroke="#ff4500" stroke-width="3"/>
                    <circle cx="250" cy="281" r="9" stroke="#ff4500" stroke-width="3"/>
                  </g>
                  
                  <!-- Position Marker -->
                  <circle
                    v-if="form.court_position_x && form.court_position_y"
                    :cx="(form.court_position_x / 1000) * 500"
                    :cy="(form.court_position_y / 600) * 300"
                    r="8"
                    fill="#ff4500"
                    stroke="#fff"
                    stroke-width="2"
                    class="position-marker"
                  />
                </svg>
                
                <div class="court-info" v-if="form.court_position_x && form.court_position_y">
                  <div class="position-coords">
                    Position: {{ Math.round(form.court_position_x) }}, {{ Math.round(form.court_position_y) }}
                  </div>
                  <div class="position-zone">
                    Zone: {{ getCourtZone(form.court_position_x, form.court_position_y) }}
                  </div>
                  <button type="button" class="clear-position-btn" @click="clearCourtPosition">
                    <i class="fas fa-times"></i> Position löschen
                  </button>
                </div>
              </div>
              
              <!-- Manual Position Input -->
              <div class="manual-position">
                <h4>Manuelle Eingabe</h4>
                <div class="form-row">
                  <div class="form-group">
                    <label for="court-x">X-Position (0-1000)</label>
                    <input
                      id="court-x"
                      type="number"
                      v-model.number="form.court_position_x"
                      class="form-control"
                      min="0"
                      max="1000"
                      step="1"
                    />
                  </div>
                  <div class="form-group">
                    <label for="court-y">Y-Position (0-600)</label>
                    <input
                      id="court-y"
                      type="number"
                      v-model.number="form.court_position_y"
                      class="form-control"
                      min="0"
                      max="600"
                      step="1"
                    />
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Statistical Data -->
          <div class="form-section" v-if="shouldShowStatistics">
            <h3>
              <i class="fas fa-chart-line"></i>
              Statistische Daten
            </h3>
            
            <div class="form-row" v-if="form.play_type === 'shot' || form.play_type === 'free_throw'">
              <div class="form-group">
                <label for="shot-distance">Schussdistanz (Meter)</label>
                <input
                  id="shot-distance"
                  type="number"
                  v-model.number="shotDistance"
                  class="form-control"
                  min="0"
                  max="50"
                  step="0.1"
                />
              </div>
              
              <div class="form-group">
                <label for="shot-angle">Schusswinkel (Grad)</label>
                <input
                  id="shot-angle"
                  type="number"
                  v-model.number="shotAngle"
                  class="form-control"
                  min="0"
                  max="360"
                  step="1"
                />
              </div>
            </div>

            <div class="form-row" v-if="form.play_type === 'pass'">
              <div class="form-group">
                <label for="pass-distance">Passdistanz (Meter)</label>
                <input
                  id="pass-distance"
                  type="number"
                  v-model.number="passDistance"
                  class="form-control"
                  min="0"
                  max="30"
                  step="0.1"
                />
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="speed">Geschwindigkeit (km/h)</label>
                <input
                  id="speed"
                  type="number"
                  v-model.number="speed"
                  class="form-control"
                  min="0"
                  max="50"
                  step="0.1"
                />
              </div>
              
              <div class="form-group">
                <label for="possession-time">Ballbesitzzeit (Sekunden)</label>
                <input
                  id="possession-time"
                  type="number"
                  v-model.number="possessionTime"
                  class="form-control"
                  min="0"
                  max="24"
                  step="0.1"
                />
              </div>
            </div>
          </div>

          <!-- Visual Settings -->
          <div class="form-section">
            <h3>
              <i class="fas fa-palette"></i>
              Darstellung
            </h3>
            
            <div class="form-row">
              <div class="form-group">
                <label for="color-code">Farbe</label>
                <div class="color-picker">
                  <input
                    id="color-code"
                    type="color"
                    v-model="form.color_code"
                    class="color-input"
                  />
                  <div class="color-presets">
                    <button
                      v-for="color in colorPresets"
                      :key="color"
                      type="button"
                      class="color-preset"
                      :style="{ backgroundColor: color }"
                      @click="form.color_code = color"
                      :class="{ active: form.color_code === color }"
                    ></button>
                  </div>
                </div>
              </div>
              
              <div class="form-group">
                <label for="priority">Priorität</label>
                <select
                  id="priority"
                  v-model="form.priority"
                  class="form-control"
                >
                  <option value="low">Niedrig</option>
                  <option value="normal">Normal</option>
                  <option value="high">Hoch</option>
                  <option value="urgent">Dringend</option>
                </select>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="tags">Tags</label>
                <div class="tags-input">
                  <div class="tags-list">
                    <span
                      v-for="(tag, index) in tagsList"
                      :key="index"
                      class="tag-item"
                    >
                      {{ tag }}
                      <button type="button" class="tag-remove" @click="removeTag(index)">
                        <i class="fas fa-times"></i>
                      </button>
                    </span>
                  </div>
                  <input
                    type="text"
                    v-model="newTag"
                    @keyup.enter="addTag"
                    @keyup.comma.prevent="addTag"
                    placeholder="Tag hinzufügen (Enter oder Komma)"
                    class="tag-input"
                  />
                </div>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group">
                <label for="visibility">Sichtbarkeit</label>
                <select
                  id="visibility"
                  v-model="form.visibility"
                  class="form-control"
                >
                  <option value="private">Privat</option>
                  <option value="team_only">Nur Team</option>
                  <option value="public">Öffentlich</option>
                </select>
              </div>
            </div>
          </div>
        </form>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <div class="footer-left">
          <div class="annotation-preview" v-if="form.title">
            <div class="preview-marker" :style="{ backgroundColor: form.color_code }">
              <i :class="getPreviewIcon()"></i>
            </div>
            <div class="preview-content">
              <div class="preview-title">{{ form.title }}</div>
              <div class="preview-meta">
                {{ formatTime(form.start_time) }} - {{ formatTime(form.end_time) }}
                <span v-if="form.play_type" class="preview-type">
                  | {{ getPlayTypeLabel(form.play_type) }}
                </span>
              </div>
            </div>
          </div>
        </div>
        
        <div class="footer-actions">
          <button type="button" class="btn btn-secondary" @click="closeModal">
            Abbrechen
          </button>
          <button 
            type="button" 
            class="btn btn-primary" 
            @click="saveAnnotation"
            :disabled="!isFormValid || isSaving"
          >
            <i v-if="isSaving" class="fas fa-spinner fa-spin"></i>
            <i v-else class="fas fa-save"></i>
            {{ isEditing ? 'Aktualisieren' : 'Erstellen' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'AnnotationModal',
  props: {
    videoId: {
      type: Number,
      required: true
    },
    annotation: {
      type: Object,
      default: null
    },
    startTime: {
      type: Number,
      default: 0
    },
    endTime: {
      type: Number,
      default: 5
    },
    courtPosition: {
      type: Object,
      default: null
    },
    videoDuration: {
      type: Number,
      default: 3600
    }
  },
  data() {
    return {
      form: {
        title: '',
        description: '',
        start_time: 0,
        end_time: 5,
        annotation_type: 'play_action',
        play_type: '',
        outcome: '',
        points_scored: null,
        player_involved: '',
        team_involved: '',
        court_position_x: null,
        court_position_y: null,
        color_code: '#007bff',
        priority: 'normal',
        visibility: 'private'
      },
      
      // Statistical data
      shotDistance: null,
      shotAngle: null,
      passDistance: null,
      speed: null,
      possessionTime: null,
      
      // Tags
      tagsList: [],
      newTag: '',
      
      // State
      errors: {},
      isSaving: false,
      
      // Constants
      colorPresets: [
        '#007bff', // Blue
        '#28a745', // Green
        '#dc3545', // Red
        '#ffc107', // Yellow
        '#17a2b8', // Teal
        '#6f42c1', // Purple
        '#fd7e14', // Orange
        '#e83e8c', // Pink
        '#6c757d', // Gray
        '#343a40'  // Dark
      ]
    }
  },
  computed: {
    isEditing() {
      return !!this.annotation
    },

    isFormValid() {
      return this.form.title.trim() !== '' && 
             this.form.start_time < this.form.end_time &&
             this.form.start_time >= 0 &&
             this.form.end_time <= this.videoDuration
    },

    shouldShowStatistics() {
      return ['shot', 'free_throw', 'pass', 'dribble'].includes(this.form.play_type)
    },

    rangeSliderStyle() {
      const startPercent = (this.form.start_time / this.videoDuration) * 100
      const endPercent = (this.form.end_time / this.videoDuration) * 100
      
      return {
        left: startPercent + '%',
        width: (endPercent - startPercent) + '%',
        backgroundColor: this.form.color_code || '#007bff'
      }
    }
  },
  mounted() {
    this.initializeForm()
  },
  methods: {
    initializeForm() {
      if (this.annotation) {
        // Edit mode - populate with existing data
        this.form = { ...this.annotation }
        this.tagsList = this.annotation.tags || []
        
        // Parse statistical data
        if (this.annotation.statistical_data) {
          const stats = this.annotation.statistical_data
          this.shotDistance = stats.shot_distance
          this.shotAngle = stats.shot_angle
          this.passDistance = stats.pass_distance
          this.speed = stats.speed
          this.possessionTime = stats.possession_time
        }
      } else {
        // Create mode - use provided defaults
        this.form.start_time = this.startTime
        this.form.end_time = this.endTime
        
        if (this.courtPosition) {
          this.form.court_position_x = this.courtPosition.x
          this.form.court_position_y = this.courtPosition.y
        }
      }
    },

    selectCourtPosition(event) {
      const svg = event.currentTarget
      const rect = svg.getBoundingClientRect()
      const x = ((event.clientX - rect.left) / rect.width) * 1000
      const y = ((event.clientY - rect.top) / rect.height) * 600
      
      this.form.court_position_x = Math.round(x)
      this.form.court_position_y = Math.round(y)
    },

    clearCourtPosition() {
      this.form.court_position_x = null
      this.form.court_position_y = null
    },

    getCourtZone(x, y) {
      if (!x || !y) return 'Unbekannt'
      
      // Simplified court zones
      if (y < 95) {
        if (x >= 190 && x <= 310) return 'Obere Zone'
        if (x < 190) return 'Linke obere Ecke'
        if (x > 310) return 'Rechte obere Ecke'
        return 'Obere Zone'
      } else if (y > 205) {
        if (x >= 190 && x <= 310) return 'Untere Zone'
        if (x < 190) return 'Linke untere Ecke'
        if (x > 310) return 'Rechte untere Ecke'
        return 'Untere Zone'
      } else {
        if (x < 150) return 'Linke Seite'
        if (x > 350) return 'Rechte Seite'
        return 'Zentrale Zone'
      }
    },

    addTag() {
      if (this.newTag.trim() && !this.tagsList.includes(this.newTag.trim()) && this.tagsList.length < 8) {
        this.tagsList.push(this.newTag.trim())
        this.newTag = ''
      }
    },

    removeTag(index) {
      this.tagsList.splice(index, 1)
    },

    getPreviewIcon() {
      const icons = {
        'shot': 'fas fa-bullseye',
        'pass': 'fas fa-arrow-right',
        'dribble': 'fas fa-basketball-ball',
        'rebound': 'fas fa-hand-paper',
        'steal': 'fas fa-hand-grabbing',
        'block': 'fas fa-shield-alt',
        'foul': 'fas fa-exclamation-triangle',
        'free_throw': 'fas fa-dot-circle'
      }
      return icons[this.form.play_type] || 'fas fa-comment'
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
        'free_throw': 'Freiwurf',
        'turnover': 'Ballverlust'
      }
      return labels[playType] || playType
    },

    async saveAnnotation() {
      if (!this.isFormValid || this.isSaving) return
      
      this.isSaving = true
      this.errors = {}
      
      try {
        // Prepare form data
        const formData = { ...this.form }
        
        // Add tags
        if (this.tagsList.length > 0) {
          formData.tags = this.tagsList
        }
        
        // Add statistical data
        const statisticalData = {}
        if (this.shotDistance !== null) statisticalData.shot_distance = this.shotDistance
        if (this.shotAngle !== null) statisticalData.shot_angle = this.shotAngle
        if (this.passDistance !== null) statisticalData.pass_distance = this.passDistance
        if (this.speed !== null) statisticalData.speed = this.speed
        if (this.possessionTime !== null) statisticalData.possession_time = this.possessionTime
        
        if (Object.keys(statisticalData).length > 0) {
          formData.statistical_data = statisticalData
        }
        
        // Make API request
        let response
        if (this.isEditing) {
          response = await fetch(`/api/videos/${this.videoId}/annotations/${this.annotation.id}`, {
            method: 'PUT',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(formData)
          })
        } else {
          response = await fetch(`/api/videos/${this.videoId}/annotations`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(formData)
          })
        }
        
        const result = await response.json()
        
        if (response.ok) {
          this.$emit('created', result.data)
          this.closeModal()
        } else {
          // Handle validation errors
          if (result.errors) {
            this.errors = result.errors
          } else {
            throw new Error(result.message || 'Fehler beim Speichern der Annotation')
          }
        }
      } catch (error) {
        console.error('Error saving annotation:', error)
        this.errors.general = error.message || 'Ein unerwarteter Fehler ist aufgetreten.'
      } finally {
        this.isSaving = false
      }
    },

    closeModal() {
      this.$emit('close')
    },

    formatTime(seconds) {
      if (!seconds && seconds !== 0) return '0:00'
      const minutes = Math.floor(seconds / 60)
      const secs = Math.floor(seconds % 60)
      return `${minutes}:${secs.toString().padStart(2, '0')}`
    },

    formatDuration(seconds) {
      if (!seconds || isNaN(seconds)) return '0s'
      if (seconds < 60) return `${Math.floor(seconds)}s`
      const minutes = Math.floor(seconds / 60)
      const remainingSecs = Math.floor(seconds % 60)
      return `${minutes}m ${remainingSecs}s`
    }
  }
}
</script>

<style scoped>
.annotation-modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10000;
  padding: 20px;
}

.annotation-modal {
  background: white;
  border-radius: 12px;
  width: 100%;
  max-width: 900px;
  max-height: 90vh;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.modal-header {
  background: #f8f9fa;
  padding: 20px 30px;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h2 {
  margin: 0;
  display: flex;
  align-items: center;
  gap: 10px;
  color: #333;
  font-size: 1.3rem;
}

.close-btn {
  background: transparent;
  border: none;
  font-size: 1.5rem;
  color: #6c757d;
  cursor: pointer;
  padding: 5px;
  border-radius: 4px;
  transition: all 0.2s ease;
}

.close-btn:hover {
  background: #e9ecef;
  color: #333;
}

.modal-body {
  padding: 30px;
  max-height: 60vh;
  overflow-y: auto;
}

.form-section {
  margin-bottom: 30px;
  border-bottom: 1px solid #f1f3f4;
  padding-bottom: 25px;
}

.form-section:last-child {
  border-bottom: none;
  margin-bottom: 0;
  padding-bottom: 0;
}

.form-section h3 {
  margin: 0 0 20px 0;
  display: flex;
  align-items: center;
  gap: 10px;
  color: #495057;
  font-size: 1.1rem;
  font-weight: 600;
}

.form-row {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
}

.form-row:last-child {
  margin-bottom: 0;
}

.form-group {
  flex: 1;
}

.form-group label {
  display: block;
  margin-bottom: 6px;
  font-weight: 500;
  color: #333;
  font-size: 0.9rem;
}

.form-control {
  width: 100%;
  padding: 10px 12px;
  border: 1px solid #ced4da;
  border-radius: 6px;
  font-size: 0.9rem;
  transition: all 0.2s ease;
  background: white;
}

.form-control:focus {
  outline: none;
  border-color: #007bff;
  box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.1);
}

.form-control.error {
  border-color: #dc3545;
  box-shadow: 0 0 0 2px rgba(220, 53, 69, 0.1);
}

.textarea {
  resize: vertical;
  min-height: 80px;
}

.error-message {
  color: #dc3545;
  font-size: 0.8rem;
  margin-top: 4px;
}

.time-display {
  font-family: 'Roboto Mono', monospace;
  font-size: 0.8rem;
  color: #6c757d;
  margin-top: 4px;
}

.duration-info {
  background: #e3f2fd;
  padding: 10px 15px;
  border-radius: 6px;
  margin-bottom: 15px;
  display: flex;
  align-items: center;
  gap: 8px;
  color: #1976d2;
  font-weight: 500;
}

.time-range-slider {
  margin-top: 15px;
}

.slider-track {
  position: relative;
  height: 6px;
  background: #e9ecef;
  border-radius: 3px;
  margin: 20px 0;
}

.slider-range {
  position: absolute;
  height: 100%;
  border-radius: 3px;
  opacity: 0.7;
}

.slider-input {
  position: absolute;
  top: -7px;
  width: 100%;
  height: 20px;
  background: transparent;
  pointer-events: none;
  -webkit-appearance: none;
  appearance: none;
}

.slider-input::-webkit-slider-thumb {
  pointer-events: auto;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #007bff;
  border: 2px solid white;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
  -webkit-appearance: none;
  cursor: pointer;
}

.slider-input::-moz-range-thumb {
  pointer-events: auto;
  width: 20px;
  height: 20px;
  border-radius: 50%;
  background: #007bff;
  border: 2px solid white;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
  cursor: pointer;
}

.court-position-section {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 30px;
}

.court-selector {
  background: #f8f9fa;
  border-radius: 8px;
  padding: 20px;
}

.court-svg {
  width: 100%;
  max-width: 400px;
  height: auto;
  border-radius: 6px;
  cursor: crosshair;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.court-lines {
  pointer-events: none;
}

.position-marker {
  cursor: pointer;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0% { opacity: 1; }
  50% { opacity: 0.6; }
  100% { opacity: 1; }
}

.court-info {
  margin-top: 15px;
  background: white;
  padding: 15px;
  border-radius: 6px;
  border: 1px solid #dee2e6;
}

.position-coords,
.position-zone {
  margin-bottom: 8px;
  font-size: 0.9rem;
  color: #495057;
}

.clear-position-btn {
  background: #dc3545;
  color: white;
  border: none;
  padding: 6px 12px;
  border-radius: 4px;
  cursor: pointer;
  font-size: 0.8rem;
  display: flex;
  align-items: center;
  gap: 6px;
}

.clear-position-btn:hover {
  background: #c82333;
}

.manual-position h4 {
  margin: 0 0 15px 0;
  color: #495057;
  font-size: 1rem;
}

.color-picker {
  display: flex;
  align-items: center;
  gap: 15px;
}

.color-input {
  width: 50px;
  height: 40px;
  border: 1px solid #ced4da;
  border-radius: 6px;
  cursor: pointer;
}

.color-presets {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.color-preset {
  width: 30px;
  height: 30px;
  border: 2px solid transparent;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s ease;
}

.color-preset:hover,
.color-preset.active {
  border-color: #333;
  transform: scale(1.1);
}

.tags-input {
  border: 1px solid #ced4da;
  border-radius: 6px;
  padding: 8px;
  background: white;
}

.tags-list {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  margin-bottom: 8px;
}

.tag-item {
  background: #007bff;
  color: white;
  padding: 4px 8px;
  border-radius: 12px;
  font-size: 0.8rem;
  display: flex;
  align-items: center;
  gap: 6px;
}

.tag-remove {
  background: transparent;
  border: none;
  color: white;
  cursor: pointer;
  padding: 0;
  font-size: 0.7rem;
}

.tag-input {
  width: 100%;
  border: none;
  outline: none;
  padding: 4px 0;
  font-size: 0.9rem;
}

.modal-footer {
  background: #f8f9fa;
  padding: 20px 30px;
  border-top: 1px solid #dee2e6;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.footer-left {
  flex: 1;
}

.annotation-preview {
  display: flex;
  align-items: center;
  gap: 12px;
}

.preview-marker {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.2rem;
}

.preview-content {
  flex: 1;
}

.preview-title {
  font-weight: 600;
  color: #333;
  margin-bottom: 2px;
}

.preview-meta {
  font-size: 0.8rem;
  color: #6c757d;
  font-family: 'Roboto Mono', monospace;
}

.preview-type {
  font-family: inherit;
}

.footer-actions {
  display: flex;
  gap: 12px;
}

.btn {
  padding: 10px 20px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  font-size: 0.9rem;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s ease;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #5a6268;
}

.btn-primary {
  background: #007bff;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #0056b3;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Responsive Design */
@media (max-width: 768px) {
  .annotation-modal {
    margin: 10px;
    max-width: none;
    max-height: 95vh;
  }
  
  .modal-header,
  .modal-body,
  .modal-footer {
    padding: 15px 20px;
  }
  
  .form-row {
    flex-direction: column;
    gap: 15px;
  }
  
  .court-position-section {
    grid-template-columns: 1fr;
    gap: 20px;
  }
  
  .color-picker {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
  }
  
  .modal-footer {
    flex-direction: column;
    gap: 15px;
    align-items: stretch;
  }
  
  .footer-actions {
    order: 1;
    justify-content: center;
  }
  
  .footer-left {
    order: 2;
  }
}
</style>