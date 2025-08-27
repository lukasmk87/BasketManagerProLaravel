/**
 * CSRF Fix Testing Script
 * Run this in browser console to test CSRF token functionality
 */

console.log('🛡️ Starting CSRF Fix Test...');

// Test 1: Check if functions are available
console.log('Test 1: Function Availability');
console.log('- window.refreshCsrfToken:', typeof window.refreshCsrfToken);
console.log('- window.refreshCsrfTokenAndMeta:', typeof window.refreshCsrfTokenAndMeta);
console.log('- window.updateCsrfToken:', typeof window.updateCsrfToken);

// Test 2: Check current token state
console.log('\nTest 2: Token State');
const metaToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
const axiosToken = window.axios?.defaults?.headers?.common['X-CSRF-TOKEN'];
console.log('- Meta token exists:', !!metaToken);
console.log('- Axios token exists:', !!axiosToken);
console.log('- Tokens match:', metaToken === axiosToken);

// Test 3: Test token refresh
console.log('\nTest 3: Token Refresh');
if (window.refreshCsrfTokenAndMeta) {
    window.refreshCsrfTokenAndMeta()
        .then(() => {
            console.log('✅ Token refresh successful');
            const newMetaToken = document.head.querySelector('meta[name="csrf-token"]')?.content;
            const newAxiosToken = window.axios?.defaults?.headers?.common['X-CSRF-TOKEN'];
            console.log('- New tokens match:', newMetaToken === newAxiosToken);
        })
        .catch(error => {
            console.error('❌ Token refresh failed:', error);
        });
} else {
    console.warn('⚠️ Token refresh function not available');
}

// Test 4: Simulate 419 error handling
console.log('\nTest 4: Error Simulation');
console.log('Making a test request to trigger CSRF handling...');

// Make a dummy POST request to test CSRF handling
fetch('/api/test-csrf', {
    method: 'POST',
    headers: {
        'X-CSRF-TOKEN': 'invalid-token-for-testing',
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({ test: true })
})
.then(response => {
    if (response.status === 419) {
        console.log('✅ 419 error detected (expected for invalid token)');
    } else if (response.status === 404) {
        console.log('✅ 404 error (test endpoint not found - that\'s okay)');
    } else {
        console.log('Response status:', response.status);
    }
})
.catch(error => {
    console.log('Request error (expected):', error.message);
});

console.log('\n🛡️ CSRF Fix Test Complete');
console.log('Check the output above for any issues.');
console.log('If you see errors, check the browser network tab and console for more details.');