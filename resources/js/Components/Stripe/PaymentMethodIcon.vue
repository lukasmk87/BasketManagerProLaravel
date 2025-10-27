<template>
    <div
        class="payment-method-icon"
        :class="[sizeClass, colorClass]"
        :title="iconTitle"
    >
        <!-- Card Brands -->
        <svg v-if="type === 'visa'" viewBox="0 0 48 32" class="icon">
            <path fill="#1434CB" d="M0 4a4 4 0 014-4h40a4 4 0 014 4v24a4 4 0 01-4 4H4a4 4 0 01-4-4V4z"/>
            <path fill="#FFF" d="M19.687 18.284l1.602-9.93h2.565l-1.602 9.93h-2.565zm11.585-9.696c-.509-.197-1.305-.41-2.3-.41-2.536 0-4.32 1.31-4.333 3.186-.012 1.387 1.275 2.16 2.25 2.622.998.472 1.334.774 1.33 1.196-.007.645-.797 0.94-1.533.94-1.024 0-1.567-.145-2.407-.505l-.33-.154-.359 2.16c.6.27 1.71.504 2.863.516 2.696 0 4.445-1.297 4.462-3.304.008-.11 0-2.188-2.69-3.117-.978-.45-1.578-.751-1.571-1.207 0-.404.469-.838 1.482-.838.846-.013 1.459.176 1.936.373l.232.112.358-2.14zm5.53 6.482c.213-.558.99-2.618.99-2.618-.014.025.204-.544.33-.896l.168.808s.476 2.25.576 2.72h-2.063v-.014zm3.175-7.013h-1.978c-.613 0-1.073.173-1.342.805l-3.806 8.85h2.696l.537-1.447h3.295c.077.338.313 1.447.313 1.447h2.38l-2.08-9.68-.015.025zM15.09 8.356l-2.514 6.765-.268-1.342c-.466-1.54-1.922-3.214-3.55-4.048l2.296 8.568 2.717-.007 4.04-9.936H15.09z"/>
            <path fill="#F7981D" d="M7.5 8.354H3.056l-.033.196c3.207.798 5.33 2.725 6.208 5.04l-.896-4.43c-.155-.617-.606-.79-1.21-.806H7.5z"/>
        </svg>

        <svg v-else-if="type === 'mastercard'" viewBox="0 0 48 32" class="icon">
            <path fill="#1F1F1F" d="M0 4a4 4 0 014-4h40a4 4 0 014 4v24a4 4 0 01-4 4H4a4 4 0 01-4-4V4z"/>
            <circle cx="19" cy="16" r="8" fill="#EB001B"/>
            <circle cx="29" cy="16" r="8" fill="#F79E1B"/>
            <path fill="#FF5F00" d="M24 9.5a7.97 7.97 0 00-5 6.5 7.97 7.97 0 005 6.5 7.97 7.97 0 005-6.5 7.97 7.97 0 00-5-6.5z"/>
        </svg>

        <svg v-else-if="type === 'amex' || type === 'american_express'" viewBox="0 0 48 32" class="icon">
            <path fill="#006FCF" d="M0 4a4 4 0 014-4h40a4 4 0 014 4v24a4 4 0 01-4 4H4a4 4 0 01-4-4V4z"/>
            <path fill="#FFF" d="M12.5 12.5h3.2l1.6 3.5 1.6-3.5h3.2L18.3 21h-2l-1.3-2.8L13.7 21h-2l-3.8-8.5h3.2l1.6 3.5 1.6-3.5zm14.4 0h7.6v2.2h-4.4v1h4.2v2h-4.2v1.1h4.4V21h-7.6v-8.5zm9.2 0h3.2l1.8 3.2 1.7-3.2h3.2L42 21h-3.2l-1.8-3.2-1.8 3.2H32L36 12.5z"/>
        </svg>

        <svg v-else-if="type === 'sepa' || type === 'sepa_debit'" viewBox="0 0 48 32" class="icon">
            <path fill="#003C7C" d="M0 4a4 4 0 014-4h40a4 4 0 014 4v24a4 4 0 01-4 4H4a4 4 0 01-4-4V4z"/>
            <path fill="#FFF" d="M8 12h4v2H8v-2zm0 4h4v2H8v-2zm6-4h4v2h-4v-2zm0 4h4v2h-4v-2zm6-4h4v2h-4v-2zm0 4h4v2h-4v-2zm6-4h4v2h-4v-2zm0 4h4v2h-4v-2zm6-4h4v2h-4v-2zm0 4h4v2h-4v-2z"/>
            <text x="24" y="24" font-size="6" font-weight="bold" text-anchor="middle" fill="#FFF">SEPA</text>
        </svg>

        <svg v-else-if="type === 'giropay'" viewBox="0 0 48 32" class="icon">
            <path fill="#003C7C" d="M0 4a4 4 0 014-4h40a4 4 0 014 4v24a4 4 0 01-4 4H4a4 4 0 01-4-4V4z"/>
            <circle cx="24" cy="16" r="10" fill="#EC0016"/>
            <text x="24" y="20" font-size="6" font-weight="bold" text-anchor="middle" fill="#FFF">giro</text>
        </svg>

        <svg v-else-if="type === 'sofort'" viewBox="0 0 48 32" class="icon">
            <path fill="#EE7F00" d="M0 4a4 4 0 014-4h40a4 4 0 014 4v24a4 4 0 01-4 4H4a4 4 0 01-4-4V4z"/>
            <text x="24" y="20" font-size="8" font-weight="bold" text-anchor="middle" fill="#FFF">sofort.</text>
        </svg>

        <svg v-else-if="type === 'ideal'" viewBox="0 0 48 32" class="icon">
            <path fill="#CC0066" d="M0 4a4 4 0 014-4h40a4 4 0 014 4v24a4 4 0 01-4 4H4a4 4 0 01-4-4V4z"/>
            <text x="24" y="20" font-size="8" font-weight="bold" text-anchor="middle" fill="#FFF">iDEAL</text>
        </svg>

        <svg v-else-if="type === 'bancontact'" viewBox="0 0 48 32" class="icon">
            <path fill="#005498" d="M0 4a4 4 0 014-4h40a4 4 0 014 4v24a4 4 0 01-4 4H4a4 4 0 01-4-4V4z"/>
            <text x="24" y="13" font-size="5" font-weight="bold" text-anchor="middle" fill="#FFF">Bancontact</text>
            <text x="24" y="21" font-size="5" font-weight="bold" text-anchor="middle" fill="#FFC845">PAYCONIQ</text>
        </svg>

        <svg v-else-if="type === 'eps'" viewBox="0 0 48 32" class="icon">
            <path fill="#E30613" d="M0 4a4 4 0 014-4h40a4 4 0 014 4v24a4 4 0 01-4 4H4a4 4 0 01-4-4V4z"/>
            <circle cx="24" cy="16" r="8" fill="#FFF"/>
            <text x="24" y="19" font-size="6" font-weight="bold" text-anchor="middle" fill="#E30613">eps</text>
        </svg>

        <svg v-else-if="type === 'discover'" viewBox="0 0 48 32" class="icon">
            <path fill="#F47216" d="M0 4a4 4 0 014-4h40a4 4 0 014 4v24a4 4 0 01-4 4H4a4 4 0 01-4-4V4z"/>
            <circle cx="38" cy="16" r="6" fill="#FFF"/>
            <text x="14" y="20" font-size="7" font-weight="bold" text-anchor="middle" fill="#FFF">DISCOVER</text>
        </svg>

        <svg v-else-if="type === 'jcb'" viewBox="0 0 48 32" class="icon">
            <path fill="#006CB7" d="M0 4a4 4 0 014-4h40a4 4 0 014 4v24a4 4 0 01-4 4H4a4 4 0 01-4-4V4z"/>
            <path fill="#FFF" d="M14 12h5v8h-5v-8zm7 0h5v8h-5v-8zm7 0h5v8h-5v-8z"/>
            <text x="24" y="26" font-size="4" font-weight="bold" text-anchor="middle" fill="#FFF">JCB</text>
        </svg>

        <svg v-else-if="type === 'diners' || type === 'diners_club'" viewBox="0 0 48 32" class="icon">
            <path fill="#0079BE" d="M0 4a4 4 0 014-4h40a4 4 0 014 4v24a4 4 0 01-4 4H4a4 4 0 01-4-4V4z"/>
            <circle cx="24" cy="16" r="10" fill="#FFF"/>
            <circle cx="20" cy="16" r="6" fill="#0079BE"/>
            <circle cx="28" cy="16" r="6" fill="#0079BE"/>
        </svg>

        <svg v-else-if="type === 'unionpay'" viewBox="0 0 48 32" class="icon">
            <path fill="#E21836" d="M0 4a4 4 0 014-4h40a4 4 0 014 4v24a4 4 0 01-4 4H4a4 4 0 01-4-4V4z"/>
            <path fill="#0066B2" d="M0 4a4 4 0 014-4h20v32H4a4 4 0 01-4-4V4z"/>
            <text x="24" y="20" font-size="6" font-weight="bold" text-anchor="middle" fill="#FFF">UnionPay</text>
        </svg>

        <!-- Generic Card Icon -->
        <svg v-else viewBox="0 0 48 32" class="icon">
            <path fill="#6B7280" d="M0 4a4 4 0 014-4h40a4 4 0 014 4v24a4 4 0 01-4 4H4a4 4 0 01-4-4V4z"/>
            <rect x="4" y="8" width="40" height="4" fill="#9CA3AF" rx="1"/>
            <rect x="4" y="16" width="16" height="3" fill="#D1D5DB" rx="1"/>
            <rect x="4" y="21" width="12" height="2" fill="#D1D5DB" rx="1"/>
            <rect x="28" y="16" width="16" height="8" fill="#D1D5DB" rx="1"/>
        </svg>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: {
        type: String,
        required: true,
    },
    size: {
        type: String,
        default: 'md', // 'xs' | 'sm' | 'md' | 'lg' | 'xl'
        validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value),
    },
    colorMode: {
        type: String,
        default: 'default', // 'default' | 'grayscale'
        validator: (value) => ['default', 'grayscale'].includes(value),
    },
    showTitle: {
        type: Boolean,
        default: true,
    },
});

const sizeClass = computed(() => {
    const sizes = {
        xs: 'icon-xs',
        sm: 'icon-sm',
        md: 'icon-md',
        lg: 'icon-lg',
        xl: 'icon-xl',
    };
    return sizes[props.size] || sizes.md;
});

const colorClass = computed(() => {
    return props.colorMode === 'grayscale' ? 'icon-grayscale' : '';
});

const iconTitle = computed(() => {
    if (!props.showTitle) return '';

    const titles = {
        visa: 'Visa',
        mastercard: 'Mastercard',
        amex: 'American Express',
        american_express: 'American Express',
        sepa: 'SEPA Lastschrift',
        sepa_debit: 'SEPA Lastschrift',
        giropay: 'Giropay',
        sofort: 'SOFORT Überweisung',
        ideal: 'iDEAL',
        bancontact: 'Bancontact',
        eps: 'EPS',
        discover: 'Discover',
        jcb: 'JCB',
        diners: 'Diners Club',
        diners_club: 'Diners Club',
        unionpay: 'UnionPay',
        card: 'Kreditkarte',
    };

    return titles[props.type?.toLowerCase()] || 'Zahlungsmethode';
});
</script>

<style scoped>
.payment-method-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.icon {
    display: block;
    width: 100%;
    height: auto;
    border-radius: 4px;
}

.icon-xs {
    width: 24px;
    height: 16px;
}

.icon-sm {
    width: 32px;
    height: 21px;
}

.icon-md {
    width: 48px;
    height: 32px;
}

.icon-lg {
    width: 64px;
    height: 42px;
}

.icon-xl {
    width: 96px;
    height: 64px;
}

.icon-grayscale .icon {
    filter: grayscale(100%);
    opacity: 0.7;
}
</style>
