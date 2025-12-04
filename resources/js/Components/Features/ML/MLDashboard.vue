<template>
  <div class="ml-dashboard">
    <div class="dashboard-header">
      <h1>ML Analytics Dashboard</h1>
      <div class="header-controls">
        <select v-model="selectedTeam" @change="loadDashboardData" class="team-select">
          <option value="">Alle Teams</option>
          <option v-for="team in teams" :key="team.id" :value="team.id">
            {{ team.name }}
          </option>
        </select>
        <button @click="refreshData" :disabled="loading" class="refresh-btn">
          <i :class="['fas', 'fa-sync-alt', { 'fa-spin': loading }]"></i>
          Aktualisieren
        </button>
      </div>
    </div>

    <div v-if="loading" class="loading-overlay">
      <div class="loading-spinner">
        <i class="fas fa-spinner fa-spin"></i>
        <p>Lade ML-Analysen...</p>
      </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="dashboard-tabs">
      <button 
        v-for="tab in tabs" 
        :key="tab.key"
        :class="['tab-btn', { 'active': activeTab === tab.key }]"
        @click="activeTab = tab.key"
      >
        <i :class="tab.icon"></i>
        {{ tab.label }}
      </button>
    </div>

    <!-- Dashboard Overview -->
    <div v-if="activeTab === 'overview'" class="tab-content">
      <div class="overview-grid">
        <!-- System Health -->
        <div class="metric-card health-card">
          <div class="metric-header">
            <h3>System Health</h3>
            <div :class="['health-indicator', dashboardOverview?.system_health?.status || 'unknown']">
              <i :class="getHealthIcon(dashboardOverview?.system_health?.status)"></i>
              {{ getHealthStatus(dashboardOverview?.system_health?.status) }}
            </div>
          </div>
          <div class="metric-content">
            <div class="health-stats">
              <div class="health-stat">
                <span class="label">Letzte Vorhersage:</span>
                <span class="value">{{ formatDateTime(dashboardOverview?.system_health?.last_prediction) }}</span>
              </div>
              <div class="health-stat">
                <span class="label">Fehler (letzte Stunde):</span>
                <span class="value">{{ dashboardOverview?.system_health?.failed_predictions_last_hour || 0 }}</span>
              </div>
              <div class="health-stat">
                <span class="label">System Load:</span>
                <span class="value">{{ Math.round((dashboardOverview?.system_health?.system_load || 0) * 100) }}%</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Models Overview -->
        <div class="metric-card models-card">
          <div class="metric-header">
            <h3>ML Modelle</h3>
            <div class="metric-value">
              {{ dashboardOverview?.models_summary?.active_models || 0 }} / {{ dashboardOverview?.models_summary?.total_models || 0 }}
            </div>
          </div>
          <div class="metric-content">
            <div class="models-chart">
              <canvas ref="modelsChart"></canvas>
            </div>
            <div class="models-stats">
              <div class="model-stat">
                <span class="label">Durchschnittliche Genauigkeit:</span>
                <span class="value">{{ formatPercentage(dashboardOverview?.models_summary?.overall_accuracy) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Performance Metrics -->
        <div class="metric-card performance-card">
          <div class="metric-header">
            <h3>Vorhersage-Performance</h3>
            <div class="metric-value">
              {{ formatPercentage(dashboardOverview?.performance_metrics?.overall_accuracy) }}
            </div>
          </div>
          <div class="metric-content">
            <div class="performance-breakdown">
              <div v-for="(accuracy, type) in dashboardOverview?.performance_metrics?.accuracy_by_type" 
                   :key="type" 
                   class="accuracy-item">
                <span class="type-label">{{ formatPredictionType(type) }}:</span>
                <span class="accuracy-value">{{ formatPercentage(accuracy.accuracy_rate) }}</span>
                <div class="accuracy-bar">
                  <div class="accuracy-fill" :style="{ width: (accuracy.accuracy_rate * 100) + '%' }"></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="metric-card activity-card">
          <div class="metric-header">
            <h3>Aktuelle Aktivität</h3>
            <div class="metric-value">
              {{ dashboardOverview?.recent_predictions?.recent_predictions?.length || 0 }}
            </div>
          </div>
          <div class="metric-content">
            <div class="activity-timeline">
              <div v-for="prediction in dashboardOverview?.recent_predictions?.recent_predictions?.slice(0, 5)" 
                   :key="prediction.id" 
                   class="activity-item">
                <div class="activity-type">
                  <i :class="getPredictionTypeIcon(prediction.prediction_type)"></i>
                  {{ formatPredictionType(prediction.prediction_type) }}
                </div>
                <div class="activity-time">{{ formatRelativeTime(prediction.created_at) }}</div>
                <div class="activity-confidence">{{ formatPercentage(prediction.confidence_score) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Daily Prediction Volume Chart -->
      <div class="chart-card">
        <div class="chart-header">
          <h3>Tägliches Vorhersagevolumen</h3>
        </div>
        <div class="chart-content">
          <canvas ref="dailyVolumeChart" height="200"></canvas>
        </div>
      </div>
    </div>

    <!-- Player Performance Tab -->
    <div v-if="activeTab === 'performance'" class="tab-content">
      <div class="performance-dashboard">
        <!-- Team Performance Overview -->
        <div class="section-card" v-if="performanceDashboard?.team_overview">
          <div class="section-header">
            <h3>Team Performance Überblick</h3>
          </div>
          <div class="section-content">
            <div class="team-stats-grid">
              <div class="team-stat">
                <div class="stat-label">Spieler Total</div>
                <div class="stat-value">{{ performanceDashboard.team_overview.total_players }}</div>
              </div>
              <div class="team-stat">
                <div class="stat-label">Aktuelle Vorhersagen</div>
                <div class="stat-value">{{ performanceDashboard.team_overview.recent_predictions }}</div>
              </div>
              <div class="team-stat">
                <div class="stat-label">Durchschnittliche Performance</div>
                <div class="stat-value">{{ formatNumber(performanceDashboard.team_overview.avg_predicted_performance) }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Top Performers -->
        <div class="section-card">
          <div class="section-header">
            <h3>Top Predicted Performers</h3>
          </div>
          <div class="section-content">
            <div class="performers-list">
              <div v-for="performer in performanceDashboard?.top_performers?.slice(0, 10)" 
                   :key="performer.player.id" 
                   class="performer-item">
                <div class="performer-info">
                  <div class="performer-name">{{ performer.player.first_name }} {{ performer.player.last_name }}</div>
                  <div class="performer-position">{{ performer.player.position }}</div>
                </div>
                <div class="performer-metrics">
                  <div class="predicted-points">{{ formatNumber(performer.predicted_points) }} Punkte</div>
                  <div class="confidence">{{ formatPercentage(performer.confidence) }} Vertrauen</div>
                </div>
                <div class="performer-chart">
                  <div class="performance-bar">
                    <div class="performance-fill" 
                         :style="{ width: Math.min(100, (performer.predicted_points / 30) * 100) + '%' }"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Breakout Candidates -->
        <div class="section-card" v-if="performanceDashboard?.breakout_candidates?.length">
          <div class="section-header">
            <h3>Breakout Kandidaten</h3>
            <div class="section-subtitle">Spieler mit steigender Leistungstendenz</div>
          </div>
          <div class="section-content">
            <div class="candidates-grid">
              <div v-for="candidate in performanceDashboard.breakout_candidates" 
                   :key="candidate.player.id" 
                   class="candidate-card">
                <div class="candidate-header">
                  <div class="candidate-name">{{ candidate.player.first_name }} {{ candidate.player.last_name }}</div>
                  <div class="breakout-probability">{{ formatPercentage(candidate.breakout_probability) }}</div>
                </div>
                <div class="candidate-trend">
                  <div class="trend-indicator">
                    <i :class="getTrendIcon(candidate.trend_analysis.trend)"></i>
                    {{ formatTrend(candidate.trend_analysis.trend) }}
                  </div>
                  <div class="trend-confidence">{{ formatPercentage(candidate.trend_analysis.confidence) }} Vertrauen</div>
                </div>
                <div class="candidate-analysis">{{ candidate.trend_analysis.analysis }}</div>
              </div>
            </div>
          </div>
        </div>

        <!-- Upcoming Predictions -->
        <div class="section-card" v-if="performanceDashboard?.upcoming_predictions?.length">
          <div class="section-header">
            <h3>Bevorstehende Spiel-Vorhersagen</h3>
          </div>
          <div class="section-content">
            <div class="upcoming-predictions">
              <div v-for="prediction in performanceDashboard.upcoming_predictions.slice(0, 8)" 
                   :key="`${prediction.game.id}-${prediction.player.id}`" 
                   class="upcoming-prediction">
                <div class="game-info">
                  <div class="game-date">{{ formatDate(prediction.game.game_datetime) }}</div>
                  <div class="game-teams">{{ getGameTitle(prediction.game) }}</div>
                </div>
                <div class="player-prediction">
                  <div class="player-name">{{ prediction.player.first_name }} {{ prediction.player.last_name }}</div>
                  <div class="prediction-value">{{ formatNumber(prediction.prediction.prediction_value) }} Punkte</div>
                  <div class="prediction-confidence">{{ formatPercentage(prediction.prediction.confidence_score) }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Injury Risk Tab -->
    <div v-if="activeTab === 'injury'" class="tab-content">
      <div class="injury-dashboard">
        <!-- Team Risk Overview -->
        <div class="section-card" v-if="injuryDashboard?.team_risk_overview">
          <div class="section-header">
            <h3>Team Verletzungsrisiko Überblick</h3>
          </div>
          <div class="section-content">
            <div class="risk-overview-grid">
              <div class="risk-metric">
                <div class="risk-label">Gesamtrisiko</div>
                <div :class="['risk-value', getRiskClass(injuryDashboard.team_risk_overview.team_overall_risk?.overall_risk_level)]">
                  {{ formatRiskLevel(injuryDashboard.team_risk_overview.team_overall_risk?.overall_risk_level) }}
                </div>
              </div>
              <div class="risk-metric">
                <div class="risk-label">Hochrisiko Spieler</div>
                <div class="risk-value">{{ injuryDashboard.team_risk_overview.team_overall_risk?.players_at_risk || 0 }}</div>
              </div>
              <div class="risk-metric">
                <div class="risk-label">Durchschnittliche Wahrscheinlichkeit</div>
                <div class="risk-value">{{ formatPercentage(injuryDashboard.team_risk_overview.team_overall_risk?.average_risk_probability) }}</div>
              </div>
            </div>

            <!-- Risk Distribution -->
            <div class="risk-distribution">
              <h4>Risiko-Verteilung</h4>
              <div class="distribution-bars">
                <div v-for="(count, level) in injuryDashboard.team_risk_overview.team_risk_distribution" 
                     :key="level" 
                     class="distribution-bar">
                  <div class="bar-label">{{ formatRiskLevel(level) }}</div>
                  <div class="bar-container">
                    <div :class="['bar-fill', getRiskClass(level)]" 
                         :style="{ width: getDistributionWidth(count, injuryDashboard.team_risk_overview.team_risk_distribution) }"></div>
                  </div>
                  <div class="bar-count">{{ count }}</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- High Risk Players -->
        <div class="section-card" v-if="injuryDashboard?.high_risk_players?.length">
          <div class="section-header">
            <h3>Hochrisiko Spieler</h3>
            <div class="section-subtitle">Spieler mit erhöhtem Verletzungsrisiko</div>
          </div>
          <div class="section-content">
            <div class="high-risk-players">
              <div v-for="riskPlayer in injuryDashboard.high_risk_players" 
                   :key="riskPlayer.player.id" 
                   class="risk-player-card">
                <div class="risk-player-header">
                  <div class="player-info">
                    <div class="player-name">{{ riskPlayer.player.first_name }} {{ riskPlayer.player.last_name }}</div>
                    <div class="player-position">{{ riskPlayer.player.position }}</div>
                  </div>
                  <div :class="['risk-indicator', getRiskClass(riskPlayer.risk_prediction.risk_level)]">
                    {{ formatPercentage(riskPlayer.risk_prediction.injury_risk_probability) }}
                  </div>
                </div>
                
                <div class="risk-factors">
                  <h5>Primäre Risikofaktoren:</h5>
                  <div class="factors-list">
                    <div v-for="factor in riskPlayer.risk_factors.slice(0, 3)" 
                         :key="factor.factor" 
                         class="risk-factor">
                      <span class="factor-name">{{ factor.factor }}</span>
                      <span :class="['factor-impact', factor.impact]">{{ formatImpact(factor.impact) }}</span>
                    </div>
                  </div>
                </div>

                <div class="recommendations" v-if="riskPlayer.risk_prediction.recommended_actions?.length">
                  <h5>Empfehlungen:</h5>
                  <div class="recommendations-list">
                    <div v-for="action in riskPlayer.risk_prediction.recommended_actions.slice(0, 2)" 
                         :key="action.action" 
                         :class="['recommendation', action.priority]">
                      <i :class="getRecommendationIcon(action.priority)"></i>
                      {{ action.action }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Risk Factors Analysis -->
        <div class="section-card" v-if="injuryDashboard?.risk_factors_analysis">
          <div class="section-header">
            <h3>Risikofaktoren Analyse</h3>
          </div>
          <div class="section-content">
            <div class="factors-analysis">
              <div class="analysis-summary">
                <div class="summary-stat">
                  <span class="label">Analysierte Spieler:</span>
                  <span class="value">{{ injuryDashboard.risk_factors_analysis.players_analyzed }}</span>
                </div>
                <div class="summary-stat">
                  <span class="label">Identifizierte Risikofaktoren:</span>
                  <span class="value">{{ injuryDashboard.risk_factors_analysis.total_risk_factors_identified }}</span>
                </div>
              </div>
              
              <div class="common-factors">
                <h4>Häufigste Risikofaktoren</h4>
                <div class="factors-chart">
                  <div v-for="(frequency, factor) in injuryDashboard.risk_factors_analysis.common_risk_factors" 
                       :key="factor" 
                       class="factor-bar">
                    <div class="factor-label">{{ factor }}</div>
                    <div class="factor-frequency">
                      <div class="frequency-bar">
                        <div class="frequency-fill" 
                             :style="{ width: getFactorWidth(frequency, injuryDashboard.risk_factors_analysis.common_risk_factors) }"></div>
                      </div>
                      <span class="frequency-count">{{ frequency }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Injury Prevention Recommendations -->
        <div class="section-card">
          <div class="section-header">
            <h3>Verletzungsprävention Empfehlungen</h3>
          </div>
          <div class="section-content">
            <div class="prevention-recommendations">
              <div v-for="(recommendation, key) in injuryDashboard?.injury_prevention_recommendations" 
                   :key="key" 
                   :class="['prevention-card', recommendation.priority]">
                <div class="prevention-header">
                  <div class="prevention-title">{{ formatPreventionTitle(key) }}</div>
                  <div :class="['priority-badge', recommendation.priority]">{{ formatPriority(recommendation.priority) }}</div>
                </div>
                <div class="prevention-description">{{ recommendation.description }}</div>
                <div class="prevention-details" v-if="recommendation.focus_areas || recommendation.affected_players">
                  <div v-if="recommendation.focus_areas" class="detail-section">
                    <strong>Schwerpunkte:</strong> {{ recommendation.focus_areas.join(', ') }}
                  </div>
                  <div v-if="recommendation.implementation" class="detail-section">
                    <strong>Umsetzung:</strong> {{ recommendation.implementation }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Experiments Tab -->
    <div v-if="activeTab === 'experiments'" class="tab-content">
      <div class="experiments-dashboard">
        <!-- Experiment Success Rates -->
        <div class="section-card">
          <div class="section-header">
            <h3>Experiment Erfolgsrate</h3>
          </div>
          <div class="section-content">
            <div class="success-metrics">
              <div class="success-metric">
                <div class="metric-label">Total Experimente</div>
                <div class="metric-value">{{ experimentDashboard?.experiment_success_rates?.total_experiments || 0 }}</div>
              </div>
              <div class="success-metric">
                <div class="metric-label">Erfolgsrate</div>
                <div class="metric-value">{{ formatPercentage(experimentDashboard?.experiment_success_rates?.success_rate) }}</div>
              </div>
              <div class="success-metric">
                <div class="metric-label">Signifikante Ergebnisse</div>
                <div class="metric-value">{{ formatPercentage(experimentDashboard?.experiment_success_rates?.significance_rate) }}</div>
              </div>
            </div>
            <div class="success-chart">
              <canvas ref="successChart"></canvas>
            </div>
          </div>
        </div>

        <!-- Recent Experiments -->
        <div class="section-card">
          <div class="section-header">
            <h3>Aktuelle Experimente</h3>
          </div>
          <div class="section-content">
            <div class="experiments-list">
              <div v-for="experiment in experimentDashboard?.recent_experiments" 
                   :key="experiment.id" 
                   class="experiment-item">
                <div class="experiment-header">
                  <div class="experiment-info">
                    <div class="experiment-name">{{ experiment.name }}</div>
                    <div class="experiment-type">{{ formatExperimentType(experiment.type) }}</div>
                  </div>
                  <div :class="['experiment-status', experiment.status]">{{ formatExperimentStatus(experiment.status) }}</div>
                </div>
                <div class="experiment-metrics">
                  <div class="experiment-metric" v-if="experiment.improvement !== null">
                    <span class="label">Verbesserung:</span>
                    <span class="value">{{ formatPercentage(experiment.improvement) }}</span>
                  </div>
                  <div class="experiment-metric" v-if="experiment.duration">
                    <span class="label">Dauer:</span>
                    <span class="value">{{ experiment.duration }} min</span>
                  </div>
                  <div class="experiment-metric" v-if="experiment.significant !== null">
                    <span class="label">Signifikant:</span>
                    <span :class="['value', { 'significant': experiment.significant }]">{{ experiment.significant ? 'Ja' : 'Nein' }}</span>
                  </div>
                </div>
                <div class="experiment-footer">
                  <span class="created-by">von {{ experiment.created_by }}</span>
                  <span class="created-at">{{ formatRelativeTime(experiment.created_at) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Running Experiments -->
        <div class="section-card" v-if="experimentDashboard?.running_experiments?.length">
          <div class="section-header">
            <h3>Laufende Experimente</h3>
          </div>
          <div class="section-content">
            <div class="running-experiments">
              <div v-for="experiment in experimentDashboard.running_experiments" 
                   :key="experiment.id" 
                   class="running-experiment">
                <div class="running-header">
                  <div class="running-name">{{ experiment.name }}</div>
                  <div class="running-progress">{{ Math.round(experiment.progress * 100) }}%</div>
                </div>
                <div class="progress-bar">
                  <div class="progress-fill" :style="{ width: (experiment.progress * 100) + '%' }"></div>
                </div>
                <div class="running-details">
                  <span class="started">Gestartet: {{ formatRelativeTime(experiment.started_at) }}</span>
                  <span v-if="experiment.estimated_completion" class="completion">
                    Voraussichtlich: {{ formatRelativeTime(experiment.estimated_completion) }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Model Performance Comparison -->
        <div class="section-card">
          <div class="section-header">
            <h3>Modell Performance Vergleich</h3>
          </div>
          <div class="section-content">
            <div class="performance-comparison">
              <div v-for="performance in experimentDashboard?.model_performance_comparison" 
                   :key="performance.experiment_type" 
                   class="performance-item">
                <div class="performance-type">{{ formatExperimentType(performance.experiment_type) }}</div>
                <div class="performance-stats">
                  <div class="perf-stat">
                    <span class="label">Experimente:</span>
                    <span class="value">{{ performance.experiments_count }}</span>
                  </div>
                  <div class="perf-stat">
                    <span class="label">Ø Verbesserung:</span>
                    <span class="value">{{ formatPercentage(performance.avg_improvement) }}</span>
                  </div>
                  <div class="perf-stat">
                    <span class="label">Beste Verbesserung:</span>
                    <span class="value">{{ formatPercentage(performance.best_improvement) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Experiment Insights -->
        <div class="section-card">
          <div class="section-header">
            <h3>Experiment Insights</h3>
          </div>
          <div class="section-content">
            <div class="insights-grid">
              <div class="insight-card">
                <div class="insight-title">Erfolgreichster Algorithmus</div>
                <div class="insight-content">
                  <div class="algorithm-name">{{ experimentDashboard?.experiment_insights?.most_successful_algorithm?.algorithm }}</div>
                  <div class="success-rate">{{ formatPercentage(experimentDashboard?.experiment_insights?.most_successful_algorithm?.success_rate) }} Erfolgsrate</div>
                </div>
              </div>
              
              <div class="insight-card">
                <div class="insight-title">Top Features</div>
                <div class="insight-content">
                  <div class="top-features">
                    <div v-for="feature in experimentDashboard?.experiment_insights?.feature_importance_insights?.top_features" 
                         :key="feature" 
                         class="feature-item">{{ feature }}</div>
                  </div>
                </div>
              </div>
              
              <div class="insight-card recommendations-card">
                <div class="insight-title">Empfehlungen</div>
                <div class="insight-content">
                  <div class="insight-recommendations">
                    <div v-for="recommendation in experimentDashboard?.experiment_insights?.recommendations" 
                         :key="recommendation" 
                         class="insight-recommendation">
                      <i class="fas fa-lightbulb"></i>
                      {{ recommendation }}
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, nextTick, computed } from 'vue'
import { Chart, registerables } from 'chart.js'

Chart.register(...registerables)

export default {
  name: 'MLDashboard',
  setup() {
    const loading = ref(false)
    const selectedTeam = ref('')
    const activeTab = ref('overview')
    
    const teams = ref([])
    const dashboardOverview = ref(null)
    const performanceDashboard = ref(null)
    const injuryDashboard = ref(null)
    const experimentDashboard = ref(null)

    // Chart refs
    const modelsChart = ref(null)
    const dailyVolumeChart = ref(null)
    const successChart = ref(null)

    const tabs = [
      { key: 'overview', label: 'Überblick', icon: 'fas fa-tachometer-alt' },
      { key: 'performance', label: 'Performance', icon: 'fas fa-chart-line' },
      { key: 'injury', label: 'Verletzungsrisiko', icon: 'fas fa-shield-alt' },
      { key: 'experiments', label: 'Experimente', icon: 'fas fa-flask' },
    ]

    // API calls
    const loadTeams = async () => {
      try {
        const response = await fetch('/api/teams')
        if (response.ok) {
          teams.value = await response.json()
        }
      } catch (error) {
        console.error('Failed to load teams:', error)
      }
    }

    const loadDashboardOverview = async () => {
      try {
        const response = await fetch('/api/ml-analytics/dashboard-overview')
        if (response.ok) {
          dashboardOverview.value = await response.json()
          await nextTick()
          createCharts()
        }
      } catch (error) {
        console.error('Failed to load dashboard overview:', error)
      }
    }

    const loadPerformanceDashboard = async () => {
      if (activeTab.value !== 'performance') return
      
      try {
        const params = new URLSearchParams()
        if (selectedTeam.value) params.append('team_id', selectedTeam.value)
        
        const response = await fetch(`/api/ml-analytics/performance-dashboard?${params}`)
        if (response.ok) {
          performanceDashboard.value = await response.json()
        }
      } catch (error) {
        console.error('Failed to load performance dashboard:', error)
      }
    }

    const loadInjuryDashboard = async () => {
      if (activeTab.value !== 'injury' || !selectedTeam.value) return
      
      try {
        const params = new URLSearchParams()
        params.append('team_id', selectedTeam.value)
        
        const response = await fetch(`/api/ml-analytics/injury-dashboard?${params}`)
        if (response.ok) {
          injuryDashboard.value = await response.json()
        }
      } catch (error) {
        console.error('Failed to load injury dashboard:', error)
      }
    }

    const loadExperimentDashboard = async () => {
      if (activeTab.value !== 'experiments') return
      
      try {
        const response = await fetch('/api/ml-analytics/experiment-dashboard')
        if (response.ok) {
          experimentDashboard.value = await response.json()
          await nextTick()
          createExperimentCharts()
        }
      } catch (error) {
        console.error('Failed to load experiment dashboard:', error)
      }
    }

    const loadDashboardData = async () => {
      loading.value = true
      try {
        await loadDashboardOverview()
        
        switch (activeTab.value) {
          case 'performance':
            await loadPerformanceDashboard()
            break
          case 'injury':
            await loadInjuryDashboard()
            break
          case 'experiments':
            await loadExperimentDashboard()
            break
        }
      } finally {
        loading.value = false
      }
    }

    const refreshData = () => {
      loadDashboardData()
    }

    // Chart creation
    const createCharts = () => {
      createModelsChart()
      createDailyVolumeChart()
    }

    const createModelsChart = () => {
      if (!modelsChart.value || !dashboardOverview.value?.models_summary) return

      const ctx = modelsChart.value.getContext('2d')
      new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels: ['Aktive Modelle', 'Inaktive Modelle'],
          datasets: [{
            data: [
              dashboardOverview.value.models_summary.active_models,
              dashboardOverview.value.models_summary.total_models - dashboardOverview.value.models_summary.active_models
            ],
            backgroundColor: ['#10b981', '#ef4444'],
            borderWidth: 2,
            borderColor: '#ffffff'
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              position: 'bottom'
            }
          }
        }
      })
    }

    const createDailyVolumeChart = () => {
      if (!dailyVolumeChart.value || !dashboardOverview.value?.recent_predictions?.daily_volume) return

      const ctx = dailyVolumeChart.value.getContext('2d')
      const dailyData = dashboardOverview.value.recent_predictions.daily_volume

      new Chart(ctx, {
        type: 'line',
        data: {
          labels: Object.keys(dailyData),
          datasets: [{
            label: 'Vorhersagen',
            data: Object.values(dailyData),
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
            fill: true,
            tension: 0.4
          }]
        },
        options: {
          responsive: true,
          scales: {
            y: {
              beginAtZero: true
            }
          },
          plugins: {
            legend: {
              display: false
            }
          }
        }
      })
    }

    const createExperimentCharts = () => {
      createSuccessChart()
    }

    const createSuccessChart = () => {
      if (!successChart.value || !experimentDashboard.value?.experiment_success_rates) return

      const ctx = successChart.value.getContext('2d')
      const data = experimentDashboard.value.experiment_success_rates

      new Chart(ctx, {
        type: 'bar',
        data: {
          labels: ['Erfolgreiche', 'Signifikante', 'Total'],
          datasets: [{
            data: [
              data.successful_experiments,
              data.significant_results,
              data.total_experiments
            ],
            backgroundColor: ['#10b981', '#f59e0b', '#6b7280'],
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              display: false
            }
          },
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      })
    }

    // Formatting helpers
    const formatPercentage = (value) => {
      if (value === null || value === undefined) return 'N/A'
      return `${Math.round(value * 100)}%`
    }

    const formatNumber = (value, decimals = 1) => {
      if (value === null || value === undefined) return 'N/A'
      return Number(value).toFixed(decimals)
    }

    const formatDateTime = (dateTime) => {
      if (!dateTime) return 'N/A'
      return new Date(dateTime).toLocaleString('de-DE')
    }

    const formatDate = (dateTime) => {
      if (!dateTime) return 'N/A'
      return new Date(dateTime).toLocaleDateString('de-DE')
    }

    const formatRelativeTime = (dateTime) => {
      if (!dateTime) return 'N/A'
      const now = new Date()
      const date = new Date(dateTime)
      const diff = now - date
      const hours = Math.floor(diff / (1000 * 60 * 60))
      const days = Math.floor(diff / (1000 * 60 * 60 * 24))
      
      if (days > 0) return `vor ${days} Tag${days > 1 ? 'en' : ''}`
      if (hours > 0) return `vor ${hours} Stunde${hours > 1 ? 'n' : ''}`
      return 'vor Kurzem'
    }

    const formatPredictionType = (type) => {
      const types = {
        'player_performance': 'Spieler Performance',
        'injury_risk': 'Verletzungsrisiko',
        'game_outcome': 'Spielergebnis'
      }
      return types[type] || type
    }

    const formatTrend = (trend) => {
      const trends = {
        'improving': 'Verbesserung',
        'declining': 'Rückgang',
        'stable': 'Stabil',
        'insufficient_data': 'Ungenügend Daten'
      }
      return trends[trend] || trend
    }

    const formatRiskLevel = (level) => {
      const levels = {
        'very_high': 'Sehr Hoch',
        'high': 'Hoch',
        'medium': 'Mittel',
        'low': 'Niedrig',
        'very_low': 'Sehr Niedrig'
      }
      return levels[level] || level
    }

    const formatImpact = (impact) => {
      const impacts = {
        'high': 'Hoch',
        'medium': 'Mittel',
        'low': 'Niedrig'
      }
      return impacts[impact] || impact
    }

    const formatPriority = (priority) => {
      const priorities = {
        'urgent': 'Dringend',
        'high': 'Hoch',
        'medium': 'Mittel',
        'low': 'Niedrig'
      }
      return priorities[priority] || priority
    }

    const formatExperimentType = (type) => {
      const types = {
        'model_comparison': 'Modell Vergleich',
        'hyperparameter_tuning': 'Hyperparameter Tuning',
        'feature_selection': 'Feature Selektion',
        'data_augmentation': 'Data Augmentation',
        'cross_validation': 'Cross Validation',
        'ab_test': 'A/B Test'
      }
      return types[type] || type
    }

    const formatExperimentStatus = (status) => {
      const statuses = {
        'planned': 'Geplant',
        'running': 'Läuft',
        'completed': 'Abgeschlossen',
        'failed': 'Fehlgeschlagen',
        'cancelled': 'Abgebrochen',
        'analyzing': 'Analysiert'
      }
      return statuses[status] || status
    }

    const formatPreventionTitle = (key) => {
      const titles = {
        'load_management': 'Belastungssteuerung',
        'recovery_protocols': 'Recovery Protokolle',
        'monitoring': 'Monitoring'
      }
      return titles[key] || key
    }

    // Helper functions
    const getHealthIcon = (status) => {
      return status === 'healthy' ? 'fas fa-check-circle' : 'fas fa-exclamation-triangle'
    }

    const getHealthStatus = (status) => {
      return status === 'healthy' ? 'Gesund' : 'Beeinträchtigt'
    }

    const getPredictionTypeIcon = (type) => {
      const icons = {
        'player_performance': 'fas fa-user-graduate',
        'injury_risk': 'fas fa-shield-alt',
        'game_outcome': 'fas fa-trophy'
      }
      return icons[type] || 'fas fa-chart-line'
    }

    const getTrendIcon = (trend) => {
      const icons = {
        'improving': 'fas fa-arrow-up',
        'declining': 'fas fa-arrow-down',
        'stable': 'fas fa-equals'
      }
      return icons[trend] || 'fas fa-minus'
    }

    const getRiskClass = (level) => {
      const classes = {
        'very_high': 'risk-very-high',
        'high': 'risk-high',
        'medium': 'risk-medium',
        'low': 'risk-low',
        'very_low': 'risk-very-low'
      }
      return classes[level] || 'risk-medium'
    }

    const getRecommendationIcon = (priority) => {
      const icons = {
        'urgent': 'fas fa-exclamation-circle',
        'high': 'fas fa-exclamation-triangle',
        'medium': 'fas fa-info-circle',
        'low': 'fas fa-lightbulb'
      }
      return icons[priority] || 'fas fa-info'
    }

    const getDistributionWidth = (count, distribution) => {
      const total = Object.values(distribution).reduce((sum, val) => sum + val, 0)
      return total > 0 ? `${(count / total) * 100}%` : '0%'
    }

    const getFactorWidth = (frequency, factors) => {
      const max = Math.max(...Object.values(factors))
      return max > 0 ? `${(frequency / max) * 100}%` : '0%'
    }

    const getGameTitle = (game) => {
      return `${game.home_team?.name || 'Home'} vs ${game.away_team?.name || 'Away'}`
    }

    // Watch for tab changes
    const watchActiveTab = () => {
      switch (activeTab.value) {
        case 'performance':
          loadPerformanceDashboard()
          break
        case 'injury':
          loadInjuryDashboard()
          break
        case 'experiments':
          loadExperimentDashboard()
          break
      }
    }

    onMounted(async () => {
      await loadTeams()
      await loadDashboardData()
    })

    return {
      // State
      loading,
      selectedTeam,
      activeTab,
      teams,
      dashboardOverview,
      performanceDashboard,
      injuryDashboard,
      experimentDashboard,
      tabs,
      
      // Chart refs
      modelsChart,
      dailyVolumeChart,
      successChart,
      
      // Methods
      loadDashboardData,
      refreshData,
      watchActiveTab,
      
      // Formatting
      formatPercentage,
      formatNumber,
      formatDateTime,
      formatDate,
      formatRelativeTime,
      formatPredictionType,
      formatTrend,
      formatRiskLevel,
      formatImpact,
      formatPriority,
      formatExperimentType,
      formatExperimentStatus,
      formatPreventionTitle,
      
      // Helpers
      getHealthIcon,
      getHealthStatus,
      getPredictionTypeIcon,
      getTrendIcon,
      getRiskClass,
      getRecommendationIcon,
      getDistributionWidth,
      getFactorWidth,
      getGameTitle
    }
  },
  
  watch: {
    activeTab() {
      this.watchActiveTab()
    }
  }
}
</script>

<style scoped>
.ml-dashboard {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.dashboard-header h1 {
  color: #1f2937;
  font-size: 2rem;
  font-weight: 600;
}

.header-controls {
  display: flex;
  gap: 1rem;
  align-items: center;
}

.team-select {
  padding: 0.5rem 1rem;
  border: 1px solid #d1d5db;
  border-radius: 0.5rem;
  background: white;
}

.refresh-btn {
  padding: 0.5rem 1rem;
  background: #3b82f6;
  color: white;
  border: none;
  border-radius: 0.5rem;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.refresh-btn:hover {
  background: #2563eb;
}

.refresh-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.loading-spinner {
  background: white;
  padding: 2rem;
  border-radius: 1rem;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.loading-spinner i {
  font-size: 2rem;
  color: #3b82f6;
  margin-bottom: 1rem;
}

.dashboard-tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 2rem;
  border-bottom: 1px solid #e5e7eb;
}

.tab-btn {
  padding: 1rem 1.5rem;
  border: none;
  background: none;
  cursor: pointer;
  border-bottom: 3px solid transparent;
  color: #6b7280;
  font-weight: 500;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.tab-btn:hover {
  color: #374151;
  background: #f9fafb;
}

.tab-btn.active {
  color: #3b82f6;
  border-bottom-color: #3b82f6;
}

.tab-content {
  min-height: 500px;
}

.overview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.metric-card {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
}

.metric-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.metric-header h3 {
  color: #1f2937;
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0;
}

.metric-value {
  font-size: 2rem;
  font-weight: 700;
  color: #3b82f6;
}

.health-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.875rem;
  font-weight: 500;
}

.health-indicator.healthy {
  background: #d1fae5;
  color: #065f46;
}

.health-indicator.degraded {
  background: #fef2f2;
  color: #991b1b;
}

.health-stats {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.health-stat {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
}

.health-stat .label {
  color: #6b7280;
}

.health-stat .value {
  color: #374151;
  font-weight: 500;
}

.models-chart {
  height: 200px;
  margin-bottom: 1rem;
}

.models-stats {
  border-top: 1px solid #e5e7eb;
  padding-top: 1rem;
}

.model-stat {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
}

.model-stat .label {
  color: #6b7280;
}

.model-stat .value {
  color: #374151;
  font-weight: 500;
}

.performance-breakdown {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.accuracy-item {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.type-label {
  min-width: 120px;
  font-size: 0.875rem;
  color: #6b7280;
}

.accuracy-value {
  min-width: 50px;
  font-weight: 600;
  color: #374151;
}

.accuracy-bar {
  flex: 1;
  height: 0.5rem;
  background: #f3f4f6;
  border-radius: 0.25rem;
  overflow: hidden;
}

.accuracy-fill {
  height: 100%;
  background: #10b981;
  transition: width 0.3s ease;
}

.activity-timeline {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.activity-item {
  display: flex;
  justify-content: between;
  align-items: center;
  gap: 1rem;
  font-size: 0.875rem;
}

.activity-type {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex: 1;
}

.activity-time {
  color: #6b7280;
}

.activity-confidence {
  color: #374151;
  font-weight: 500;
}

.chart-card {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
}

.chart-header {
  margin-bottom: 1rem;
}

.chart-header h3 {
  color: #1f2937;
  font-size: 1.125rem;
  font-weight: 600;
  margin: 0;
}

.section-card {
  background: white;
  border-radius: 1rem;
  padding: 1.5rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
  border: 1px solid #e5e7eb;
  margin-bottom: 1.5rem;
}

.section-header {
  margin-bottom: 1rem;
}

.section-header h3 {
  color: #1f2937;
  font-size: 1.25rem;
  font-weight: 600;
  margin: 0 0 0.25rem 0;
}

.section-subtitle {
  color: #6b7280;
  font-size: 0.875rem;
}

.team-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.team-stat {
  text-align: center;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
}

.stat-label {
  color: #6b7280;
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
}

.stat-value {
  color: #1f2937;
  font-size: 1.5rem;
  font-weight: 600;
}

.performers-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.performer-item {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
}

.performer-info {
  flex: 1;
}

.performer-name {
  font-weight: 600;
  color: #1f2937;
}

.performer-position {
  color: #6b7280;
  font-size: 0.875rem;
}

.performer-metrics {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
}

.predicted-points {
  font-weight: 600;
  color: #059669;
}

.confidence {
  font-size: 0.875rem;
  color: #6b7280;
}

.performer-chart {
  width: 100px;
}

.performance-bar {
  width: 100%;
  height: 0.5rem;
  background: #e5e7eb;
  border-radius: 0.25rem;
  overflow: hidden;
}

.performance-fill {
  height: 100%;
  background: linear-gradient(90deg, #10b981, #059669);
  transition: width 0.3s ease;
}

.candidates-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1rem;
}

.candidate-card {
  padding: 1rem;
  background: #f0fdf4;
  border: 1px solid #bbf7d0;
  border-radius: 0.5rem;
}

.candidate-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.candidate-name {
  font-weight: 600;
  color: #1f2937;
}

.breakout-probability {
  color: #059669;
  font-weight: 600;
}

.candidate-trend {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.trend-indicator {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  color: #059669;
}

.trend-confidence {
  font-size: 0.875rem;
  color: #6b7280;
}

.candidate-analysis {
  font-size: 0.875rem;
  color: #374151;
}

.upcoming-predictions {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.upcoming-prediction {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
}

.game-info {
  flex: 1;
}

.game-date {
  font-weight: 600;
  color: #1f2937;
}

.game-teams {
  color: #6b7280;
  font-size: 0.875rem;
}

.player-prediction {
  display: flex;
  flex-direction: column;
  align-items: end;
  gap: 0.25rem;
}

.player-name {
  font-weight: 500;
  color: #374151;
}

.prediction-value {
  color: #059669;
  font-weight: 600;
}

.prediction-confidence {
  font-size: 0.875rem;
  color: #6b7280;
}

.risk-overview-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.risk-metric {
  text-align: center;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
}

.risk-label {
  color: #6b7280;
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
}

.risk-value {
  font-size: 1.25rem;
  font-weight: 600;
}

.risk-value.risk-very-high { color: #dc2626; }
.risk-value.risk-high { color: #ea580c; }
.risk-value.risk-medium { color: #d97706; }
.risk-value.risk-low { color: #65a30d; }
.risk-value.risk-very-low { color: #16a34a; }

.risk-distribution h4 {
  color: #1f2937;
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.distribution-bars {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.distribution-bar {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.bar-label {
  min-width: 80px;
  font-size: 0.875rem;
  color: #374151;
}

.bar-container {
  flex: 1;
  height: 1rem;
  background: #f3f4f6;
  border-radius: 0.5rem;
  overflow: hidden;
}

.bar-fill {
  height: 100%;
  transition: width 0.3s ease;
}

.bar-fill.risk-very-high { background: #dc2626; }
.bar-fill.risk-high { background: #ea580c; }
.bar-fill.risk-medium { background: #d97706; }
.bar-fill.risk-low { background: #65a30d; }
.bar-fill.risk-very-low { background: #16a34a; }

.bar-count {
  min-width: 30px;
  text-align: right;
  font-weight: 500;
  color: #374151;
}

.high-risk-players {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.risk-player-card {
  padding: 1rem;
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 0.5rem;
}

.risk-player-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.player-info .player-name {
  font-weight: 600;
  color: #1f2937;
}

.player-info .player-position {
  color: #6b7280;
  font-size: 0.875rem;
}

.risk-indicator {
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-weight: 600;
  font-size: 0.875rem;
}

.risk-indicator.risk-very-high { background: #fee2e2; color: #dc2626; }
.risk-indicator.risk-high { background: #fed7aa; color: #ea580c; }

.risk-factors h5 {
  color: #1f2937;
  font-size: 0.875rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.factors-list {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.risk-factor {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem;
}

.factor-name {
  color: #374151;
}

.factor-impact {
  padding: 0.125rem 0.5rem;
  border-radius: 0.25rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.factor-impact.high { background: #fecaca; color: #dc2626; }
.factor-impact.medium { background: #fed7aa; color: #ea580c; }
.factor-impact.low { background: #fef3c7; color: #d97706; }

.recommendations {
  margin-top: 1rem;
}

.recommendations h5 {
  color: #1f2937;
  font-size: 0.875rem;
  font-weight: 600;
  margin: 0 0 0.5rem 0;
}

.recommendations-list {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.recommendation {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #374151;
}

.recommendation.urgent { color: #dc2626; }
.recommendation.high { color: #ea580c; }

.factors-analysis {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.analysis-summary {
  display: flex;
  gap: 2rem;
}

.summary-stat {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.summary-stat .label {
  color: #6b7280;
  font-size: 0.875rem;
}

.summary-stat .value {
  color: #1f2937;
  font-size: 1.5rem;
  font-weight: 600;
}

.common-factors h4 {
  color: #1f2937;
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.factors-chart {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.factor-bar {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.factor-label {
  min-width: 150px;
  font-size: 0.875rem;
  color: #374151;
}

.factor-frequency {
  flex: 1;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.frequency-bar {
  flex: 1;
  height: 0.75rem;
  background: #f3f4f6;
  border-radius: 0.375rem;
  overflow: hidden;
}

.frequency-fill {
  height: 100%;
  background: #3b82f6;
  transition: width 0.3s ease;
}

.frequency-count {
  min-width: 30px;
  text-align: right;
  font-weight: 500;
  color: #374151;
}

.prevention-recommendations {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.prevention-card {
  padding: 1rem;
  border-radius: 0.5rem;
  border-left: 4px solid;
}

.prevention-card.high {
  background: #fef2f2;
  border-left-color: #dc2626;
}

.prevention-card.medium {
  background: #fffbeb;
  border-left-color: #d97706;
}

.prevention-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.prevention-title {
  font-weight: 600;
  color: #1f2937;
}

.priority-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.priority-badge.high {
  background: #fee2e2;
  color: #dc2626;
}

.priority-badge.medium {
  background: #fef3c7;
  color: #d97706;
}

.prevention-description {
  color: #374151;
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
}

.prevention-details {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-section {
  font-size: 0.875rem;
  color: #6b7280;
}

.success-metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 1rem;
  margin-bottom: 2rem;
}

.success-metric {
  text-align: center;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
}

.metric-label {
  color: #6b7280;
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
}

.metric-value {
  color: #1f2937;
  font-size: 1.5rem;
  font-weight: 600;
}

.success-chart {
  height: 200px;
}

.experiments-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.experiment-item {
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
}

.experiment-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.experiment-info .experiment-name {
  font-weight: 600;
  color: #1f2937;
}

.experiment-info .experiment-type {
  color: #6b7280;
  font-size: 0.875rem;
}

.experiment-status {
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.75rem;
  font-weight: 500;
}

.experiment-status.completed {
  background: #d1fae5;
  color: #065f46;
}

.experiment-status.running {
  background: #dbeafe;
  color: #1e40af;
}

.experiment-status.failed {
  background: #fee2e2;
  color: #991b1b;
}

.experiment-metrics {
  display: flex;
  gap: 1rem;
  margin-bottom: 0.5rem;
}

.experiment-metric {
  display: flex;
  gap: 0.25rem;
  font-size: 0.875rem;
}

.experiment-metric .label {
  color: #6b7280;
}

.experiment-metric .value {
  color: #374151;
  font-weight: 500;
}

.experiment-metric .value.significant {
  color: #059669;
}

.experiment-footer {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: #9ca3af;
}

.running-experiments {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.running-experiment {
  padding: 1rem;
  background: #dbeafe;
  border-radius: 0.5rem;
}

.running-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.running-name {
  font-weight: 600;
  color: #1f2937;
}

.running-progress {
  color: #1e40af;
  font-weight: 600;
}

.progress-bar {
  width: 100%;
  height: 0.5rem;
  background: rgba(255, 255, 255, 0.3);
  border-radius: 0.25rem;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.progress-fill {
  height: 100%;
  background: #2563eb;
  transition: width 0.3s ease;
}

.running-details {
  display: flex;
  justify-content: space-between;
  font-size: 0.75rem;
  color: #6b7280;
}

.performance-comparison {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.performance-item {
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
}

.performance-type {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 0.5rem;
}

.performance-stats {
  display: flex;
  gap: 2rem;
}

.perf-stat {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.perf-stat .label {
  color: #6b7280;
  font-size: 0.875rem;
}

.perf-stat .value {
  color: #374151;
  font-weight: 600;
}

.insights-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1rem;
}

.insight-card {
  padding: 1rem;
  background: #f9fafb;
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
}

.insight-title {
  font-weight: 600;
  color: #1f2937;
  margin-bottom: 1rem;
}

.algorithm-name {
  font-size: 1.25rem;
  font-weight: 600;
  color: #3b82f6;
  margin-bottom: 0.25rem;
}

.success-rate {
  color: #6b7280;
  font-size: 0.875rem;
}

.top-features {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.feature-item {
  padding: 0.5rem;
  background: white;
  border-radius: 0.25rem;
  border: 1px solid #e5e7eb;
  font-size: 0.875rem;
  color: #374151;
}

.recommendations-card {
  grid-column: 1 / -1;
}

.insight-recommendations {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.insight-recommendation {
  display: flex;
  align-items: start;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #374151;
}

.insight-recommendation i {
  color: #f59e0b;
  margin-top: 0.125rem;
}

@media (max-width: 768px) {
  .ml-dashboard {
    padding: 1rem;
  }
  
  .dashboard-header {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }
  
  .header-controls {
    justify-content: space-between;
  }
  
  .overview-grid {
    grid-template-columns: 1fr;
  }
  
  .candidates-grid {
    grid-template-columns: 1fr;
  }
  
  .insights-grid {
    grid-template-columns: 1fr;
  }
  
  .recommendations-card {
    grid-column: 1;
  }
}
</style>