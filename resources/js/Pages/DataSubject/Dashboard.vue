<template>
    <AppLayout title="My Data & Privacy">
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                My Data & Privacy
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- GDPR Rights Information -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <InformationCircleIcon class="h-6 w-6 text-blue-600" />
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-medium text-blue-900 mb-2">Your Data Protection Rights</h3>
                            <p class="text-sm text-blue-800 mb-4">
                                Under the General Data Protection Regulation (GDPR), you have several important rights regarding your personal data:
                            </p>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-800">
                                <div class="flex items-start">
                                    <CheckIcon class="h-4 w-4 text-blue-600 mr-2 mt-0.5 flex-shrink-0" />
                                    <span><strong>Right to Access:</strong> Get a copy of your personal data</span>
                                </div>
                                <div class="flex items-start">
                                    <CheckIcon class="h-4 w-4 text-blue-600 mr-2 mt-0.5 flex-shrink-0" />
                                    <span><strong>Right to Rectification:</strong> Correct inaccurate data</span>
                                </div>
                                <div class="flex items-start">
                                    <CheckIcon class="h-4 w-4 text-blue-600 mr-2 mt-0.5 flex-shrink-0" />
                                    <span><strong>Right to Erasure:</strong> Request deletion of your data</span>
                                </div>
                                <div class="flex items-start">
                                    <CheckIcon class="h-4 w-4 text-blue-600 mr-2 mt-0.5 flex-shrink-0" />
                                    <span><strong>Right to Portability:</strong> Transfer data to another service</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Data Overview Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <UserIcon class="h-8 w-8 text-blue-600" />
                                </div>
                                <div class="ml-4">
                                    <dt class="text-sm font-medium text-gray-500">Personal Data Categories</dt>
                                    <dd class="text-2xl font-bold text-gray-900">{{ Object.keys(dataOverview.personal_data).length }}</dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <DocumentCheckIcon class="h-8 w-8 text-green-600" />
                                </div>
                                <div class="ml-4">
                                    <dt class="text-sm font-medium text-gray-500">Active Consents</dt>
                                    <dd class="text-2xl font-bold text-green-600">{{ dataOverview.active_consents }}</dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <XCircleIcon class="h-8 w-8 text-orange-600" />
                                </div>
                                <div class="ml-4">
                                    <dt class="text-sm font-medium text-gray-500">Withdrawn Consents</dt>
                                    <dd class="text-2xl font-bold text-orange-600">{{ dataOverview.withdrawn_consents }}</dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <ClockIcon class="h-8 w-8 text-yellow-600" />
                                </div>
                                <div class="ml-4">
                                    <dt class="text-sm font-medium text-gray-500">Pending Requests</dt>
                                    <dd class="text-2xl font-bold text-yellow-600">{{ dataOverview.pending_requests }}</dd>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Consent Renewal Notifications -->
                <div 
                    v-if="renewalNotifications.length > 0"
                    class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-8"
                >
                    <div class="flex items-start">
                        <div class="flex-shrink-0">
                            <ExclamationTriangleIcon class="h-6 w-6 text-yellow-600" />
                        </div>
                        <div class="ml-3">
                            <h3 class="text-lg font-medium text-yellow-900 mb-2">Consent Renewal Required</h3>
                            <p class="text-sm text-yellow-800 mb-4">
                                Some of your consent preferences are older than 2 years and need to be renewed for GDPR compliance:
                            </p>
                            <div class="space-y-2">
                                <div 
                                    v-for="renewal in renewalNotifications" 
                                    :key="renewal.consent_id"
                                    class="flex justify-between items-center bg-yellow-100 rounded-md p-3"
                                >
                                    <div>
                                        <p class="font-medium text-yellow-900">{{ getConsentTypeLabel(renewal.consent_type) }}</p>
                                        <p class="text-sm text-yellow-700">Given on: {{ formatDate(renewal.given_at) }}</p>
                                    </div>
                                    <Link 
                                        :href="route('data-subject.consents')"
                                        class="bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-1 rounded text-sm"
                                    >
                                        Renew
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Your Personal Data -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-medium text-gray-900">Your Personal Data</h3>
                                <Link 
                                    :href="route('data-subject.requests.create')"
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm"
                                >
                                    Request Data Export
                                </Link>
                            </div>
                            
                            <div class="space-y-4">
                                <!-- Basic Information -->
                                <div v-if="dataOverview.personal_data.basic_info">
                                    <h4 class="font-medium text-gray-700">Basic Information</h4>
                                    <div class="mt-2 space-y-1 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Name:</span>
                                            <span>{{ dataOverview.personal_data.basic_info.name }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Email:</span>
                                            <span>{{ dataOverview.personal_data.basic_info.email }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Phone:</span>
                                            <span>{{ dataOverview.personal_data.basic_info.phone }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Account Created:</span>
                                            <span>{{ dataOverview.personal_data.basic_info.created_at }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Player Information -->
                                <div v-if="dataOverview.personal_data.player_info" class="border-t pt-4">
                                    <h4 class="font-medium text-gray-700">Player Information</h4>
                                    <div class="mt-2 space-y-1 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Jersey Number:</span>
                                            <span>{{ dataOverview.personal_data.player_info.jersey_number }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Position:</span>
                                            <span>{{ dataOverview.personal_data.player_info.position }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Team:</span>
                                            <span>{{ dataOverview.personal_data.player_info.team }}</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Emergency Contacts -->
                                <div v-if="dataOverview.personal_data.emergency_contacts" class="border-t pt-4">
                                    <h4 class="font-medium text-gray-700">Emergency Contacts</h4>
                                    <div class="mt-2 space-y-2">
                                        <div 
                                            v-for="contact in dataOverview.personal_data.emergency_contacts" 
                                            :key="contact.name"
                                            class="bg-gray-50 rounded p-2 text-sm"
                                        >
                                            <div class="flex justify-between">
                                                <span class="font-medium">{{ contact.name }}</span>
                                                <span class="text-gray-500">{{ contact.relationship }}</span>
                                            </div>
                                            <div class="text-gray-600">{{ contact.phone }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Data Protection Actions</h3>
                            
                            <div class="space-y-4">
                                <!-- Manage Consents -->
                                <div class="border rounded-lg p-4">
                                    <div class="flex items-start">
                                        <AdjustmentsHorizontalIcon class="h-6 w-6 text-blue-600 mr-3 mt-0.5" />
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900">Manage Consents</h4>
                                            <p class="text-sm text-gray-600 mt-1">
                                                Review and update your consent preferences for data processing.
                                            </p>
                                            <Link 
                                                :href="route('data-subject.consents')"
                                                class="mt-2 inline-flex text-sm text-blue-600 hover:text-blue-900"
                                            >
                                                Manage consents →
                                            </Link>
                                        </div>
                                    </div>
                                </div>

                                <!-- Request Your Data -->
                                <div class="border rounded-lg p-4">
                                    <div class="flex items-start">
                                        <DocumentArrowDownIcon class="h-6 w-6 text-green-600 mr-3 mt-0.5" />
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900">Request Your Data</h4>
                                            <p class="text-sm text-gray-600 mt-1">
                                                Download a copy of all personal data we have about you.
                                            </p>
                                            <Link 
                                                :href="route('data-subject.requests.create')"
                                                class="mt-2 inline-flex text-sm text-green-600 hover:text-green-900"
                                            >
                                                Request data export →
                                            </Link>
                                        </div>
                                    </div>
                                </div>

                                <!-- Correct Your Data -->
                                <div class="border rounded-lg p-4">
                                    <div class="flex items-start">
                                        <PencilSquareIcon class="h-6 w-6 text-yellow-600 mr-3 mt-0.5" />
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900">Correct Your Data</h4>
                                            <p class="text-sm text-gray-600 mt-1">
                                                Request corrections to inaccurate or incomplete information.
                                            </p>
                                            <Link 
                                                :href="route('data-subject.requests.create')"
                                                class="mt-2 inline-flex text-sm text-yellow-600 hover:text-yellow-900"
                                            >
                                                Request correction →
                                            </Link>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delete Your Data -->
                                <div class="border rounded-lg p-4">
                                    <div class="flex items-start">
                                        <TrashIcon class="h-6 w-6 text-red-600 mr-3 mt-0.5" />
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900">Delete Your Data</h4>
                                            <p class="text-sm text-gray-600 mt-1">
                                                Request deletion of your personal data (Right to be Forgotten).
                                            </p>
                                            <Link 
                                                :href="route('data-subject.requests.create')"
                                                class="mt-2 inline-flex text-sm text-red-600 hover:text-red-900"
                                            >
                                                Request deletion →
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Requests -->
                <div v-if="recentRequests.length > 0" class="mt-8 bg-white overflow-hidden shadow-sm rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Your Recent Requests</h3>
                            <Link 
                                :href="route('data-subject.requests')"
                                class="text-blue-600 hover:text-blue-900 text-sm"
                            >
                                View all requests →
                            </Link>
                        </div>
                        
                        <div class="space-y-4">
                            <div 
                                v-for="request in recentRequests" 
                                :key="request.id"
                                class="border-l-4 pl-4 py-2"
                                :class="{
                                    'border-yellow-400': request.response_status === 'pending',
                                    'border-green-400': request.response_status === 'completed',
                                    'border-red-400': request.response_status === 'failed',
                                    'border-blue-400': request.response_status === 'in_progress'
                                }"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ getRequestTypeLabel(request.request_type) }}
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            {{ request.request_description }}
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            Submitted: {{ formatDate(request.received_at) }}
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
                                            :href="route('data-subject.requests.show', request.id)"
                                            class="text-blue-600 hover:text-blue-900 text-sm"
                                        >
                                            View
                                        </Link>
                                    </div>
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
import { Link } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { 
    InformationCircleIcon,
    CheckIcon,
    UserIcon,
    DocumentCheckIcon,
    XCircleIcon,
    ClockIcon,
    ExclamationTriangleIcon,
    AdjustmentsHorizontalIcon,
    DocumentArrowDownIcon,
    PencilSquareIcon,
    TrashIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    dataOverview: Object,
    recentRequests: Array,
    renewalNotifications: Array,
})

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const getRequestTypeLabel = (type) => {
    const labels = {
        'data_export': 'Data Export Request',
        'data_portability': 'Data Portability Request',
        'data_erasure': 'Data Deletion Request',
        'data_rectification': 'Data Correction Request',
        'processing_restriction': 'Processing Restriction Request',
        'objection_to_processing': 'Processing Objection Request'
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
        'marketing': 'Marketing Communications',
        'analytics': 'Analytics & Performance',
        'emergency_contact': 'Emergency Contact Information',
        'photo_video': 'Photos & Videos'
    }
    return labels[type] || type
}
</script>