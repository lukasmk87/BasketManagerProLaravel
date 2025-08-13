<template>
    <AppLayout title="Security Dashboard">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Security Dashboard
                </h2>
                
                <div class="flex gap-4">
                    <button
                        @click="generateReport"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150"
                    >
                        <DocumentArrowDownIcon class="w-4 h-4 mr-2" />
                        Generate Report
                    </button>
                    
                    <button
                        @click="refreshData"
                        :disabled="loading"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150"
                    >
                        <ArrowPathIcon class="w-4 h-4 mr-2" :class="{ 'animate-spin': loading }" />
                        Refresh
                    </button>
                </div>
            </div>
        </template>

        <!-- Security Metrics Overview -->
        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-6 mb-8">
                    <!-- Total Events Today -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <ShieldExclamationIcon class="h-8 w-8 text-blue-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Today's Events</p>
                                    <p class="text-2xl font-semibold text-gray-900">{{ metrics.total_events_today }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Critical Events -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <ExclamationTriangleIcon class="h-8 w-8 text-red-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Critical Events</p>
                                    <p class="text-2xl font-semibold text-red-600">{{ metrics.critical_events_today }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Unresolved Events -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <ClockIcon class="h-8 w-8 text-orange-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Unresolved</p>
                                    <p class="text-2xl font-semibold text-orange-600">{{ metrics.unresolved_events }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Events -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <MegaphoneIcon class="h-8 w-8 text-yellow-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Emergency</p>
                                    <p class="text-2xl font-semibold text-yellow-600">{{ metrics.emergency_related_events_today }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- GDPR Events -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <DocumentTextIcon class="h-8 w-8 text-purple-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">GDPR Events</p>
                                    <p class="text-2xl font-semibold text-purple-600">{{ metrics.gdpr_related_events_today }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Requires Investigation -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <MagnifyingGlassIcon class="h-8 w-8 text-indigo-600" />
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Needs Investigation</p>
                                    <p class="text-2xl font-semibold text-indigo-600">{{ metrics.events_requiring_investigation }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Charts and Tables Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                    <!-- Security Trends Chart -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Security Events Trend (Last 7 Days)</h3>
                            <div class="h-64">
                                <canvas ref="trendsChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Event Type Distribution -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Event Type Distribution</h3>
                            <div class="space-y-3">
                                <div 
                                    v-for="(count, eventType) in eventTypeDistribution" 
                                    :key="eventType"
                                    class="flex items-center justify-between"
                                >
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 rounded-full mr-3" :class="getEventTypeColor(eventType)"></div>
                                        <span class="text-sm text-gray-700">{{ formatEventType(eventType) }}</span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900">{{ count }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Events and Top IPs Row -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Recent Security Events -->
                    <div class="lg:col-span-2 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Recent Security Events</h3>
                                <Link 
                                    href="/security/events" 
                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                >
                                    View All
                                </Link>
                            </div>
                            
                            <div class="space-y-3">
                                <div 
                                    v-for="event in recentEvents" 
                                    :key="event.id"
                                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors cursor-pointer"
                                    @click="viewEvent(event.id)"
                                >
                                    <div class="flex items-center space-x-3">
                                        <span class="text-lg">{{ event.severity_icon }}</span>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ event.event_id }}</p>
                                            <p class="text-xs text-gray-500">{{ event.description }}</p>
                                            <p class="text-xs text-gray-400">{{ event.time_since_occurred }}</p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-2">
                                        <span 
                                            :class="`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-${event.severity_color}-100 text-${event.severity_color}-800`"
                                        >
                                            {{ event.severity }}
                                        </span>
                                        
                                        <span 
                                            v-if="event.is_emergency_related"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800"
                                        >
                                            Emergency
                                        </span>
                                        
                                        <span 
                                            v-if="event.is_gdpr_related"
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800"
                                        >
                                            GDPR
                                        </span>
                                    </div>
                                </div>
                                
                                <div v-if="recentEvents.length === 0" class="text-center py-4">
                                    <p class="text-gray-500">No recent security events</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Source IPs -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Top Source IPs (24h)</h3>
                            <div class="space-y-3">
                                <div 
                                    v-for="ip in topSourceIPs" 
                                    :key="ip.ip"
                                    class="flex items-center justify-between"
                                >
                                    <div>
                                        <span class="text-sm font-mono text-gray-900">{{ ip.ip }}</span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-600">{{ ip.count }} events</span>
                                </div>
                                
                                <div v-if="topSourceIPs.length === 0" class="text-center py-4">
                                    <p class="text-gray-500">No events in last 24 hours</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, onMounted, nextTick } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Chart from 'chart.js/auto'
import {
    ShieldExclamationIcon,
    ExclamationTriangleIcon,
    ClockIcon,
    MegaphoneIcon,
    DocumentTextIcon,
    MagnifyingGlassIcon,
    DocumentArrowDownIcon,
    ArrowPathIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    recentEvents: Array,
    metrics: Object,
    trends: Array,
    eventTypeDistribution: Object,
    topSourceIPs: Array,
})

const loading = ref(false)
const trendsChart = ref(null)
let chartInstance = null

const refreshData = () => {
    loading.value = true
    router.reload({ only: ['recentEvents', 'metrics', 'trends', 'eventTypeDistribution', 'topSourceIPs'] })
        .then(() => {
            loading.value = false
        })
}

const generateReport = () => {
    // Implement report generation
    alert('Report generation feature coming soon!')
}

const viewEvent = (eventId) => {
    router.visit(`/security/events/${eventId}`)
}

const formatEventType = (eventType) => {
    return eventType.split('_').map(word => 
        word.charAt(0).toUpperCase() + word.slice(1)
    ).join(' ')
}

const getEventTypeColor = (eventType) => {
    const colors = {
        'authentication_failure': 'bg-red-400',
        'authorization_violation': 'bg-orange-400',
        'emergency_access_misuse': 'bg-yellow-400',
        'emergency_access_anomaly': 'bg-yellow-500',
        'gdpr_violation': 'bg-purple-400',
        'gdpr_compliance_violation': 'bg-purple-500',
        'suspicious_activity': 'bg-gray-400',
        'brute_force_attempt': 'bg-red-500',
        'data_export_unusual': 'bg-blue-400',
        'rate_limit_exceeded': 'bg-green-400',
    }
    return colors[eventType] || 'bg-gray-400'
}

const initChart = () => {
    if (chartInstance) {
        chartInstance.destroy()
    }
    
    const ctx = trendsChart.value.getContext('2d')
    
    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: props.trends.map(t => t.label),
            datasets: [
                {
                    label: 'Total Events',
                    data: props.trends.map(t => t.total_events),
                    borderColor: 'rgb(59, 130, 246)',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Critical Events',
                    data: props.trends.map(t => t.critical_events),
                    borderColor: 'rgb(239, 68, 68)',
                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Emergency Events',
                    data: props.trends.map(t => t.emergency_events),
                    borderColor: 'rgb(245, 158, 11)',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    })
}

onMounted(() => {
    nextTick(() => {
        initChart()
    })
})
</script>

<style scoped>
/* Custom styles for the security dashboard */
.transition-colors {
    transition-property: background-color, border-color, color, fill, stroke;
    transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
    transition-duration: 150ms;
}
</style>