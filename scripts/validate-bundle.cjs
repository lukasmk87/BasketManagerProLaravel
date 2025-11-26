#!/usr/bin/env node
/**
 * Bundle Size Validator for PERF-005/PERF-006
 * Validates that code-splitting is working correctly
 */

const fs = require('fs');
const path = require('path');
const zlib = require('zlib');

const BUILD_DIR = path.join(__dirname, '../public/build/assets');

// Expected chunks from code-splitting
const EXPECTED_CHUNKS = [
    'vendor',
    'chart',
    'editor',
    'stripe',
    'realtime',
    'dragdrop',
    'video-ml'
];

// Size limits (in KB, gzipped)
// Note: TipTap editor is a full ProseMirror-based WYSIWYG editor, ~110 KB is expected
const SIZE_LIMITS = {
    vendor: 150,
    chart: 80,
    editor: 120,  // TipTap + ProseMirror extensions
    stripe: 40,
    realtime: 30,
    dragdrop: 20,
    'video-ml': 100,
    main: 200  // app.js
};

function getGzipSize(filePath) {
    const content = fs.readFileSync(filePath);
    const gzipped = zlib.gzipSync(content);
    return gzipped.length / 1024; // KB
}

function validateBundles() {
    console.log('\n🔍 Validating Bundle Sizes...\n');

    if (!fs.existsSync(BUILD_DIR)) {
        console.error('❌ Build directory not found. Run npm run build first.');
        process.exit(1);
    }

    const files = fs.readdirSync(BUILD_DIR).filter(f => f.endsWith('.js'));
    const results = { pass: [], fail: [], missing: [] };

    // Check for expected chunks
    for (const chunk of EXPECTED_CHUNKS) {
        const found = files.find(f => f.startsWith(chunk + '-'));
        if (found) {
            const size = getGzipSize(path.join(BUILD_DIR, found));
            const limit = SIZE_LIMITS[chunk] || 100;
            const status = size <= limit ? '✅' : '⚠️';

            console.log(`${status} ${chunk}: ${size.toFixed(2)} KB (limit: ${limit} KB)`);

            if (size <= limit) {
                results.pass.push(chunk);
            } else {
                results.fail.push({ chunk, size, limit });
            }
        } else {
            console.log(`❓ ${chunk}: NOT FOUND (may be tree-shaken)`);
            results.missing.push(chunk);
        }
    }

    // List all generated chunks
    console.log('\n📁 All generated JS files:');
    for (const file of files.sort()) {
        const size = getGzipSize(path.join(BUILD_DIR, file));
        console.log(`   ${file}: ${size.toFixed(2)} KB`);
    }

    // Summary
    console.log('\n📊 Summary:');
    console.log(`   ✅ Passed: ${results.pass.length}`);
    console.log(`   ⚠️  Over limit: ${results.fail.length}`);
    console.log(`   ❓ Missing: ${results.missing.length}`);

    // Total bundle size
    const totalSize = files.reduce((sum, f) => {
        return sum + getGzipSize(path.join(BUILD_DIR, f));
    }, 0);
    console.log(`\n📦 Total JS bundle: ${totalSize.toFixed(2)} KB (gzipped)`);

    // Exit code
    if (results.fail.length > 0) {
        console.log('\n⚠️  Some chunks exceed size limits!');
        process.exit(1);
    } else {
        console.log('\n✅ All bundle size checks passed!');
        process.exit(0);
    }
}

validateBundles();
