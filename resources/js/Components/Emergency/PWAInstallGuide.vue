<template>
    <div class="pwa-install-guide">
        <!-- Header -->
        <div class="guide-header">
            <div class="icon-container">
                <SmartphoneIcon class="w-16 h-16 text-blue-600" />
            </div>
            <h1 class="guide-title">Install Emergency App</h1>
            <p class="guide-subtitle">
                Get instant access to emergency contacts during critical situations
            </p>
        </div>

        <!-- Features -->
        <div class="features-section">
            <h2 class="section-title">Emergency Features</h2>
            <div class="features-grid">
                <div v-for="feature in features" :key="feature" class="feature-item">
                    <CheckIcon class="w-5 h-5 text-green-600" />
                    <span>{{ feature }}</span>
                </div>
            </div>
        </div>

        <!-- Browser Detection & Instructions -->
        <div class="instructions-section">
            <h2 class="section-title">Installation Instructions</h2>
            
            <!-- Detected browser info -->
            <div class="browser-info">
                <div class="browser-icon">
                    {{ getBrowserIcon() }}
                </div>
                <div class="browser-details">
                    <p class="browser-name">{{ detectedBrowser }} on {{ detectedPlatform }}</p>
                    <p class="browser-note">Follow the steps below to install</p>
                </div>
            </div>

            <!-- Step-by-step instructions -->
            <div class="instruction-steps">
                <div 
                    v-for="(step, index) in currentInstructions" 
                    :key="index"
                    class="instruction-step"
                >
                    <div class="step-number">{{ index + 1 }}</div>
                    <div class="step-content">
                        <p>{{ step }}</p>
                    </div>
                </div>
            </div>

            <!-- Visual guide for common browsers -->
            <div v-if="showVisualGuide" class="visual-guide">
                <h3 class="visual-title">Visual Guide</h3>
                <div class="visual-steps">
                    <!-- Chrome/Edge Android -->
                    <div v-if="detectedBrowser.includes('Chrome') && detectedPlatform === 'Android'" class="visual-step">
                        <div class="visual-icon">⋮</div>
                        <p>Look for the three dots menu</p>
                    </div>
                    
                    <!-- Safari iOS -->
                    <div v-if="detectedBrowser.includes('Safari') && detectedPlatform === 'iOS'" class="visual-step">
                        <div class="visual-icon">□↗</div>
                        <p>Tap the share button</p>
                    </div>
                    
                    <!-- Install button -->
                    <div class="visual-step">
                        <div class="visual-icon">📱</div>
                        <p>Find "Add to Home Screen" or "Install"</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Installation Status -->
        <div class="status-section">
            <div class="status-card" :class="installStatus.type">
                <div class="status-icon">
                    <CheckCircleIcon v-if="installStatus.type === 'success'" class="w-6 h-6" />
                    <ExclamationTriangleIcon v-else-if="installStatus.type === 'warning'" class="w-6 h-6" />
                    <InformationCircleIcon v-else class="w-6 h-6" />
                </div>
                <div class="status-content">
                    <p class="status-title">{{ installStatus.title }}</p>
                    <p class="status-message">{{ installStatus.message }}</p>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <!-- Native install button (if supported) -->
            <button 
                v-if="showInstallButton" 
                @click="triggerInstall"
                class="install-btn primary"
                :disabled="isInstalling"
            >
                <div v-if="isInstalling" class="loading-spinner"></div>
                <DownloadIcon v-else class="w-5 h-5" />
                <span>{{ isInstalling ? 'Installing...' : 'Install Now' }}</span>
            </button>

            <!-- Manual install reminder -->
            <button 
                v-else
                @click="showManualSteps = !showManualSteps" 
                class="install-btn secondary"
            >
                <BookOpenIcon class="w-5 h-5" />
                <span>{{ showManualSteps ? 'Hide' : 'Show' }} Manual Steps</span>
            </button>

            <!-- Try emergency access -->
            <button @click="goToEmergencyAccess" class="install-btn emergency">
                <ExclamationTriangleIcon class="w-5 h-5" />
                <span>Continue to Emergency Access</span>
            </button>

            <!-- Share installation -->
            <button @click="shareInstallGuide" class="install-btn share">
                <ShareIcon class="w-5 h-5" />
                <span>Share Installation Guide</span>
            </button>
        </div>

        <!-- Manual steps (collapsible) -->
        <div v-if="showManualSteps" class="manual-steps">
            <h3 class="manual-title">Manual Installation</h3>
            <div class="manual-content">
                <p class="manual-intro">
                    If the automatic installation doesn't work, follow these platform-specific steps:
                </p>
                
                <div class="platform-tabs">
                    <button 
                        v-for="platform in Object.keys(installInstructions)" 
                        :key="platform"
                        @click="selectedPlatform = platform"
                        class="platform-tab"
                        :class="{ 'active': selectedPlatform === platform }"
                    >
                        {{ formatPlatformName(platform) }}
                    </button>
                </div>
                
                <div class="platform-instructions">
                    <div 
                        v-for="(instruction, browser) in installInstructions[selectedPlatform] || {}" 
                        :key="browser"
                        class="browser-instruction"
                    >
                        <h4 class="browser-title">{{ browser }}</h4>
                        <p class="browser-steps">{{ instruction }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Troubleshooting -->
        <div class="troubleshooting-section">
            <details class="troubleshooting-details">
                <summary class="troubleshooting-summary">
                    <QuestionMarkCircleIcon class="w-5 h-5" />
                    <span>Troubleshooting</span>
                </summary>
                <div class="troubleshooting-content">
                    <div class="faq-item">
                        <h4>Installation button not showing?</h4>
                        <p>Make sure you're using HTTPS and your browser supports PWAs. Try refreshing the page.</p>
                    </div>
                    <div class="faq-item">
                        <h4>App not working offline?</h4>
                        <p>Visit the emergency access page at least once while online to cache the data.</p>
                    </div>
                    <div class="faq-item">
                        <h4>Can't find the app after installation?</h4>
                        <p>Check your home screen or app drawer. The app icon might be named "Emergency Access".</p>
                    </div>
                </div>
            </details>
        </div>

        <!-- Emergency fallback -->
        <div class="emergency-fallback">
            <div class="fallback-card">
                <ExclamationTriangleIcon class="w-6 h-6 text-red-600" />
                <div class="fallback-content">
                    <h4>In Case of Emergency</h4>
                    <p>If you can't install the app, bookmark this page or call emergency services directly:</p>
                    <div class="emergency-numbers">
                        <a href="tel:112" class="emergency-number">🚑 112 - Medical/Fire</a>
                        <a href="tel:110" class="emergency-number">👮 110 - Police</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { 
    SmartphoneIcon,
    CheckIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    InformationCircleIcon,
    DownloadIcon,
    BookOpenIcon,
    ShareIcon,
    QuestionMarkCircleIcon
} from '@heroicons/vue/24/outline'

const props = defineProps({
    accessKey: {
        type: String,
        required: true
    },
    team: {
        type: Object,
        required: true
    },
    features: {
        type: Array,
        default: () => [
            'Offline-Zugriff auf Notfallkontakte',
            'Ein-Tipp-Anruf-Funktionalität',
            'Schnelle Incident-Meldung',
            'GPS-Standortfreigabe',
            'Kritische medizinische Informationen'
        ]
    },
    installInstructions: {
        type: Object,
        required: true
    }
})

// Reactive data
const showInstallButton = ref(false)
const isInstalling = ref(false)
const showManualSteps = ref(false)
const showVisualGuide = ref(true)
const selectedPlatform = ref('chrome_android')
const deferredPrompt = ref(null)

// Browser detection
const detectedBrowser = ref('Unknown Browser')
const detectedPlatform = ref('Unknown Platform')

// Computed properties
const currentInstructions = computed(() => {
    const platformKey = `${detectedBrowser.value.toLowerCase()}_${detectedPlatform.value.toLowerCase()}`
    const instructions = props.installInstructions[platformKey] || 
                        props.installInstructions[detectedPlatform.value.toLowerCase()] ||
                        props.installInstructions.general || 
                        ['Follow your browser\'s installation prompts']
    
    return Array.isArray(instructions) ? instructions : [instructions]
})

const installStatus = computed(() => {
    if (window.navigator.standalone || window.matchMedia('(display-mode: standalone)').matches) {
        return {
            type: 'success',
            title: 'App Already Installed',
            message: 'The emergency app is already installed and ready to use!'
        }
    } else if (showInstallButton.value) {
        return {
            type: 'info',
            title: 'Ready to Install',
            message: 'Click the install button below to add the emergency app to your device.'
        }
    } else {
        return {
            type: 'warning',
            title: 'Manual Installation Required',
            message: 'Follow the instructions below to manually install the emergency app.'
        }
    }
})

// Methods
const detectBrowser = () => {
    const userAgent = navigator.userAgent
    
    // Detect platform
    if (/Android/i.test(userAgent)) {
        detectedPlatform.value = 'Android'
    } else if (/iPhone|iPad/i.test(userAgent)) {
        detectedPlatform.value = 'iOS'
    } else if (/Windows/i.test(userAgent)) {
        detectedPlatform.value = 'Windows'
    } else if (/Macintosh/i.test(userAgent)) {
        detectedPlatform.value = 'macOS'
    }
    
    // Detect browser
    if (/Chrome/i.test(userAgent) && !/Edg/i.test(userAgent)) {
        detectedBrowser.value = 'Chrome'
    } else if (/Edg/i.test(userAgent)) {
        detectedBrowser.value = 'Edge'
    } else if (/Firefox/i.test(userAgent)) {
        detectedBrowser.value = 'Firefox'
    } else if (/Safari/i.test(userAgent) && !/Chrome/i.test(userAgent)) {
        detectedBrowser.value = 'Safari'
    }
    
    // Set initial platform selection
    selectedPlatform.value = `${detectedBrowser.value.toLowerCase()}_${detectedPlatform.value.toLowerCase()}`
}

const getBrowserIcon = () => {
    const icons = {
        'Chrome': '🔍',
        'Edge': '🌐',
        'Firefox': '🦊',
        'Safari': '🧭'
    }
    return icons[detectedBrowser.value] || '📱'
}

const formatPlatformName = (platform) => {
    const names = {
        'chrome_android': 'Chrome Android',
        'safari_ios': 'Safari iOS',
        'firefox_android': 'Firefox Android',
        'chrome_windows': 'Chrome Windows',
        'edge_windows': 'Edge Windows',
        'chrome_macos': 'Chrome macOS',
        'safari_macos': 'Safari macOS',
        'general': 'Other Browsers'
    }
    return names[platform] || platform
}

const triggerInstall = async () => {
    if (!deferredPrompt.value) return
    
    isInstalling.value = true
    
    try {
        const result = await deferredPrompt.value.prompt()
        console.log('Install prompt result:', result.outcome)
        
        if (result.outcome === 'accepted') {
            // Installation successful
            showInstallButton.value = false
            deferredPrompt.value = null
        }
    } catch (error) {
        console.error('Installation failed:', error)
    } finally {
        isInstalling.value = false
    }
}

const goToEmergencyAccess = () => {
    window.location.href = `/emergency/pwa/offline/${props.accessKey}`
}

const shareInstallGuide = () => {
    const shareData = {
        title: `${props.team.name} - Emergency Access Installation`,
        text: 'Install the emergency contact app for quick access during critical situations',
        url: window.location.href
    }
    
    if (navigator.share) {
        navigator.share(shareData)
    } else {
        // Fallback - copy URL to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Installation guide URL copied to clipboard!')
        })
    }
}

// Lifecycle
onMounted(() => {
    detectBrowser()
    
    // Listen for beforeinstallprompt event
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault()
        deferredPrompt.value = e
        showInstallButton.value = true
    })
    
    // Check if already installed
    window.addEventListener('appinstalled', () => {
        console.log('PWA was installed')
        showInstallButton.value = false
        deferredPrompt.value = null
    })
})
</script>

<style scoped>
.pwa-install-guide {
    @apply max-w-2xl mx-auto p-6 bg-gray-50 min-h-screen;
}

.guide-header {
    @apply text-center mb-8;
}

.icon-container {
    @apply flex justify-center mb-4;
}

.guide-title {
    @apply text-3xl font-bold text-gray-900 mb-2;
}

.guide-subtitle {
    @apply text-lg text-gray-600 max-w-md mx-auto;
}

.features-section {
    @apply mb-8;
}

.section-title {
    @apply text-xl font-semibold text-gray-900 mb-4;
}

.features-grid {
    @apply grid grid-cols-1 gap-3;
}

.feature-item {
    @apply flex items-center gap-3 p-3 bg-white rounded-lg border border-gray-200;
}

.instructions-section {
    @apply mb-8;
}

.browser-info {
    @apply flex items-center gap-4 p-4 bg-white rounded-lg border border-gray-200 mb-6;
}

.browser-icon {
    @apply text-3xl;
}

.browser-details {
    @apply flex-1;
}

.browser-name {
    @apply font-medium text-gray-900;
}

.browser-note {
    @apply text-sm text-gray-600 mt-1;
}

.instruction-steps {
    @apply space-y-4 mb-6;
}

.instruction-step {
    @apply flex gap-4 p-4 bg-white rounded-lg border border-gray-200;
}

.step-number {
    @apply flex-shrink-0 w-8 h-8 bg-blue-600 text-white rounded-full;
    @apply flex items-center justify-center text-sm font-semibold;
}

.step-content {
    @apply flex-1;
}

.visual-guide {
    @apply bg-blue-50 border border-blue-200 rounded-lg p-4;
}

.visual-title {
    @apply font-medium text-blue-900 mb-3;
}

.visual-steps {
    @apply grid grid-cols-2 gap-4;
}

.visual-step {
    @apply text-center;
}

.visual-icon {
    @apply text-2xl mb-2;
}

.visual-step p {
    @apply text-sm text-blue-800;
}

.status-section {
    @apply mb-6;
}

.status-card {
    @apply flex gap-3 p-4 rounded-lg border-2;
}

.status-card.success {
    @apply bg-green-50 border-green-200;
}

.status-card.warning {
    @apply bg-yellow-50 border-yellow-200;
}

.status-card.info {
    @apply bg-blue-50 border-blue-200;
}

.status-icon {
    @apply flex-shrink-0;
}

.status-card.success .status-icon {
    @apply text-green-600;
}

.status-card.warning .status-icon {
    @apply text-yellow-600;
}

.status-card.info .status-icon {
    @apply text-blue-600;
}

.status-content {
    @apply flex-1;
}

.status-title {
    @apply font-semibold;
}

.status-card.success .status-title {
    @apply text-green-800;
}

.status-card.warning .status-title {
    @apply text-yellow-800;
}

.status-card.info .status-title {
    @apply text-blue-800;
}

.status-message {
    @apply text-sm mt-1;
}

.status-card.success .status-message {
    @apply text-green-700;
}

.status-card.warning .status-message {
    @apply text-yellow-700;
}

.status-card.info .status-message {
    @apply text-blue-700;
}

.action-buttons {
    @apply grid grid-cols-1 gap-4 mb-8;
}

.install-btn {
    @apply flex items-center justify-center gap-3 px-6 py-4;
    @apply rounded-lg font-semibold transition-colors;
    min-height: 56px;
}

.install-btn.primary {
    @apply bg-blue-600 text-white hover:bg-blue-700;
}

.install-btn.secondary {
    @apply bg-gray-600 text-white hover:bg-gray-700;
}

.install-btn.emergency {
    @apply bg-red-600 text-white hover:bg-red-700;
}

.install-btn.share {
    @apply bg-green-600 text-white hover:bg-green-700;
}

.install-btn:disabled {
    @apply bg-gray-400 cursor-not-allowed;
}

.loading-spinner {
    @apply w-5 h-5 border-2 border-white border-t-transparent rounded-full animate-spin;
}

.manual-steps {
    @apply bg-white border border-gray-200 rounded-lg p-6 mb-8;
}

.manual-title {
    @apply text-lg font-semibold text-gray-900 mb-4;
}

.manual-intro {
    @apply text-gray-600 mb-4;
}

.platform-tabs {
    @apply flex flex-wrap gap-2 mb-4;
}

.platform-tab {
    @apply px-3 py-2 text-sm rounded-lg border;
    @apply border-gray-300 bg-white text-gray-700;
    @apply hover:bg-gray-50 transition-colors;
}

.platform-tab.active {
    @apply bg-blue-600 text-white border-blue-600;
}

.platform-instructions {
    @apply space-y-4;
}

.browser-instruction {
    @apply p-3 bg-gray-50 rounded-lg;
}

.browser-title {
    @apply font-medium text-gray-900 mb-2;
}

.browser-steps {
    @apply text-sm text-gray-700;
}

.troubleshooting-section {
    @apply mb-8;
}

.troubleshooting-details {
    @apply bg-white border border-gray-200 rounded-lg;
}

.troubleshooting-summary {
    @apply flex items-center gap-3 p-4 cursor-pointer;
    @apply font-medium text-gray-700 hover:text-gray-900;
}

.troubleshooting-content {
    @apply px-4 pb-4 border-t border-gray-200 space-y-4;
}

.faq-item h4 {
    @apply font-medium text-gray-900 mb-1;
}

.faq-item p {
    @apply text-sm text-gray-600;
}

.emergency-fallback {
    @apply bg-red-50 border border-red-200 rounded-lg p-6;
}

.fallback-card {
    @apply flex gap-4;
}

.fallback-content {
    @apply flex-1;
}

.fallback-content h4 {
    @apply font-semibold text-red-900 mb-2;
}

.fallback-content p {
    @apply text-red-800 mb-3;
}

.emergency-numbers {
    @apply grid grid-cols-1 gap-2;
}

.emergency-number {
    @apply flex items-center gap-2 p-3 bg-red-100 text-red-800;
    @apply rounded-lg font-medium hover:bg-red-200 transition-colors;
    text-decoration: none;
}

/* Mobile optimizations */
@media (max-width: 640px) {
    .pwa-install-guide {
        @apply p-4;
    }
    
    .guide-title {
        @apply text-2xl;
    }
    
    .visual-steps {
        @apply grid-cols-1 gap-3;
    }
    
    .action-buttons {
        @apply gap-3;
    }
    
    .install-btn {
        @apply py-5 text-lg;
        min-height: 64px;
    }
    
    .platform-tabs {
        @apply grid-cols-2 gap-2;
    }
    
    .platform-tab {
        @apply text-center py-3;
    }
}
</style>