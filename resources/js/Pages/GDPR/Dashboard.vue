<template>
    <AppLayout title="GDPR Compliance Dashboard">
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    GDPR Compliance Dashboard
                </h2>
                <div class="flex space-x-2">
                    <Link 
                        :href="route('gdpr.admin.requests.index')"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                    >
                        View All Requests
                    </Link>
                    <Link 
                        :href="route('gdpr.admin.consents.index')"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                    >
                        Manage Consents
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Compliance Status Overview -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-yellow-100 rounded-md flex items-center justify-center">
                                        <ClockIcon class="w-5 h-5 text-yellow-600" />
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <dt class="text-sm font-medium text-gray-500 truncate">Pending Requests</dt>
                                    <dd class="text-3xl font-bold text-gray-900">{{ summary.pending_requests }}</dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-red-100 rounded-md flex items-center justify-center">
                                        <ExclamationTriangleIcon class="w-5 h-5 text-red-600" />
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <dt class="text-sm font-medium text-gray-500 truncate">Overdue Requests</dt>
                                    <dd class="text-3xl font-bold text-red-600">{{ summary.overdue_requests }}</dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-green-100 rounded-md flex items-center justify-center">
                                        <CheckCircleIcon class="w-5 h-5 text-green-600" />
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <dt class="text-sm font-medium text-gray-500 truncate">Completed This Month</dt>
                                    <dd class="text-3xl font-bold text-green-600">{{ summary.completed_requests_month }}</dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-blue-100 rounded-md flex items-center justify-center">
                                        <DocumentCheckIcon class="w-5 h-5 text-blue-600" />
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <dt class="text-sm font-medium text-gray-500 truncate">Active Consents</dt>
                                    <dd class="text-3xl font-bold text-blue-600">{{ summary.active_consents }}</dd>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Compliance Issues Alert -->
                <div 
                    v-if="complianceReport.compliance_issues && complianceReport.compliance_issues.length > 0"
                    class="bg-red-50 border border-red-200 rounded-md p-4 mb-8"
                >
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <ExclamationTriangleIcon class="h-5 w-5 text-red-400" />
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">
                                Compliance Issues Detected
                            </h3>
                            <div class="mt-2 text-sm text-red-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li v-for="issue in complianceReport.compliance_issues" :key="issue.type">
                                        {{ issue.description }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Recent Requests -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Data Subject Requests</h3>
                            
                            <div v-if="recentRequests.length === 0" class="text-gray-500 text-center py-8">
                                No recent requests
                            </div>
                            
                            <div v-else class="space-y-4">
                                <div 
                                    v-for="request in recentRequests" 
                                    :key="request.id"
                                    class="border-l-4 pl-4 py-2"
                                    :class="{
                                        'border-yellow-400': request.response_status === 'pending',
                                        'border-green-400': request.response_status === 'completed',
                                        'border-red-400': request.response_status === 'failed' || isOverdue(request),
                                        'border-blue-400': request.response_status === 'in_progress'
                                    }"
                                >
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">
                                                {{ getRequestTypeLabel(request.request_type) }}
                                            </p>
                                            <p class="text-sm text-gray-500">
                                                Request ID: {{ request.request_id }}
                                            </p>
                                            <p class="text-xs text-gray-400">
                                                Received: {{ formatDate(request.received_at) }}
                                                <span v-if="isOverdue(request)" class="text-red-600 font-medium ml-2">
                                                    (OVERDUE)
                                                </span>
                                            </p>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span 
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                                :class="getStatusColor(request.response_status)"
                                            >
                                                {{ getStatusLabel(request.response_status) }}
                                            </span>
                                            <Link 
                                                :href="route('gdpr.admin.requests.show', request.id)"
                                                class="text-blue-600 hover:text-blue-900 text-sm"
                                            >
                                                View
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-4 pt-4 border-t">
                                <Link 
                                    :href="route('gdpr.admin.requests.index')"
                                    class="text-blue-600 hover:text-blue-900 text-sm font-medium"
                                >
                                    View all requests →
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Compliance Report Summary -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Compliance Report Summary</h3>
                            
                            <!-- Request Statistics -->
                            <div class="mb-6">
                                <h4 class="text-md font-medium text-gray-700 mb-2">Data Subject Requests</h4>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div v-for="stat in complianceReport.data_subject_requests" :key="stat.request_type">
                                        <span class="text-gray-500">{{ getRequestTypeLabel(stat.request_type) }}:</span>
                                        <span class="font-medium ml-1">{{ stat.count }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Consent Statistics -->
                            <div class="mb-6">
                                <h4 class="text-md font-medium text-gray-700 mb-2">Consent Management</h4>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div v-for="stat in complianceReport.consent_management" :key="stat.consent_type">
                                        <span class="text-gray-500">{{ getConsentTypeLabel(stat.consent_type) }}:</span>
                                        <span class="font-medium ml-1">{{ stat.count }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Recommendations -->
                            <div>
                                <h4 class="text-md font-medium text-gray-700 mb-2">Recommendations</h4>
                                <ul class="text-sm text-gray-600 space-y-1">
                                    <li v-for="recommendation in complianceReport.recommendations" :key="recommendation">
                                        • {{ recommendation }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Report Generation -->
                <div class="mt-8 bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Generate Compliance Report</h3>
                        
                        <form @submit.prevent="generateReport" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label for="from_date" class="block text-sm font-medium text-gray-700">From Date</label>
                                <input 
                                    type="date" 
                                    id="from_date"
                                    v-model="reportForm.from_date"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    required
                                >
                            </div>
                            
                            <div>
                                <label for="to_date" class="block text-sm font-medium text-gray-700">To Date</label>
                                <input 
                                    type="date" 
                                    id="to_date"
                                    v-model="reportForm.to_date"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    required
                                >
                            </div>
                            
                            <div>
                                <label for="format" class="block text-sm font-medium text-gray-700">Format</label>
                                <select 
                                    id="format"
                                    v-model="reportForm.format"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="json">JSON</option>
                                    <option value="pdf">PDF</option>
                                </select>
                            </div>
                            
                            <div class="flex items-end">
                                <button 
                                    type="submit"
                                    :disabled="reportForm.processing"
                                    class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-md text-sm font-medium"
                                >
                                    <span v-if="reportForm.processing">Generating...</span>
                                    <span v-else>Generate Report</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Link, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { 
    ClockIcon, 
    ExclamationTriangleIcon, 
    CheckCircleIcon, 
    DocumentCheckIcon 
} from '@heroicons/vue/24/outline'

const props = defineProps({
    summary: Object,
    recentRequests: Array,
    complianceReport: Object,
})

const reportForm = useForm({
    from_date: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    to_date: new Date().toISOString().split('T')[0],
    format: 'json'
})

const generateReport = () => {
    reportForm.post(route('gdpr.admin.generate-report'))
}

const isOverdue = (request) => {
    return new Date() > new Date(request.deadline_date) && request.response_status !== 'completed'
}

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getRequestTypeLabel = (type) => {
    const labels = {
        'data_export': 'Data Export',
        'data_portability': 'Data Portability',
        'data_erasure': 'Data Erasure',
        'data_rectification': 'Data Rectification',
        'processing_restriction': 'Processing Restriction',
        'objection_to_processing': 'Objection to Processing'
    }
    return labels[type] || type
}

const getStatusLabel = (status) => {
    const labels = {
        'pending': 'Pending',
        'in_progress': 'In Progress',
        'completed': 'Completed',
        'failed': 'Failed',
        'rejected': 'Rejected'
    }
    return labels[status] || status
}

const getStatusColor = (status) => {
    const colors = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'in_progress': 'bg-blue-100 text-blue-800',
        'completed': 'bg-green-100 text-green-800',
        'failed': 'bg-red-100 text-red-800',
        'rejected': 'bg-red-100 text-red-800'
    }
    return colors[status] || 'bg-gray-100 text-gray-800'
}

const getConsentTypeLabel = (type) => {
    const labels = {
        'marketing': 'Marketing',
        'analytics': 'Analytics',
        'emergency_contact': 'Emergency Contacts',
        'photo_video': 'Photos & Videos'
    }
    return labels[type] || type
}
</script>