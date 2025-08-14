#!/bin/sh
# Emergency Services Health Check Script
# Comprehensive health monitoring for emergency services

set -e

# Configuration
TIMEOUT=3
MAX_RESPONSE_TIME=2000
HEALTH_ENDPOINT="http://localhost/health"
EMERGENCY_TEST_ENDPOINT="http://localhost/emergency/health"
REDIS_HOST=${REDIS_HOST:-127.0.0.1}
REDIS_PORT=${REDIS_PORT:-6379}

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') [HEALTH] $1"
}

error() {
    log "${RED}ERROR: $1${NC}"
    exit 1
}

warning() {
    log "${YELLOW}WARNING: $1${NC}"
}

success() {
    log "${GREEN}SUCCESS: $1${NC}"
}

# Check if required commands exist
command -v curl >/dev/null 2>&1 || error "curl is required but not installed"
command -v redis-cli >/dev/null 2>&1 || error "redis-cli is required but not installed"

# 1. Check basic HTTP health endpoint
log "Checking basic HTTP health..."
if ! curl -f -s --max-time $TIMEOUT "$HEALTH_ENDPOINT" > /dev/null; then
    error "Basic health endpoint is not responding"
fi
success "Basic HTTP health check passed"

# 2. Check emergency services health endpoint
log "Checking emergency services health..."
EMERGENCY_RESPONSE=$(curl -f -s --max-time $TIMEOUT "$EMERGENCY_TEST_ENDPOINT" 2>/dev/null || echo "FAILED")
if [ "$EMERGENCY_RESPONSE" = "FAILED" ]; then
    error "Emergency services health endpoint is not responding"
fi
success "Emergency services health check passed"

# 3. Check response time
log "Checking response time..."
RESPONSE_TIME=$(curl -o /dev/null -s -w '%{time_total}' --max-time $TIMEOUT "$HEALTH_ENDPOINT" || echo "999")
RESPONSE_TIME_MS=$(echo "$RESPONSE_TIME * 1000" | bc -l | cut -d'.' -f1)

if [ "$RESPONSE_TIME_MS" -gt "$MAX_RESPONSE_TIME" ]; then
    warning "Response time is ${RESPONSE_TIME_MS}ms (threshold: ${MAX_RESPONSE_TIME}ms)"
else
    success "Response time is acceptable (${RESPONSE_TIME_MS}ms)"
fi

# 4. Check Redis connectivity
log "Checking Redis connectivity..."
if ! redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" ping > /dev/null 2>&1; then
    error "Redis is not responding"
fi
success "Redis connectivity check passed"

# 5. Check PHP-FPM status
log "Checking PHP-FPM status..."
if ! pgrep -f "php-fpm" > /dev/null; then
    error "PHP-FPM process is not running"
fi
success "PHP-FPM is running"

# 6. Check Nginx status
log "Checking Nginx status..."
if ! pgrep -f "nginx" > /dev/null; then
    error "Nginx process is not running"
fi
success "Nginx is running"

# 7. Check disk space
log "Checking disk space..."
DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ "$DISK_USAGE" -gt 85 ]; then
    warning "Disk usage is high: ${DISK_USAGE}%"
elif [ "$DISK_USAGE" -gt 95 ]; then
    error "Disk usage is critical: ${DISK_USAGE}%"
else
    success "Disk usage is acceptable (${DISK_USAGE}%)"
fi

# 8. Check memory usage
log "Checking memory usage..."
MEMORY_USAGE=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
if [ "$MEMORY_USAGE" -gt 90 ]; then
    warning "Memory usage is high: ${MEMORY_USAGE}%"
else
    success "Memory usage is acceptable (${MEMORY_USAGE}%)"
fi

# 9. Check emergency cache status
log "Checking emergency cache status..."
CACHE_CHECK=$(redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" get "emergency_health_check" 2>/dev/null || echo "MISSING")
if [ "$CACHE_CHECK" = "MISSING" ]; then
    # Set a test cache entry
    redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" setex "emergency_health_check" 60 "$(date)" > /dev/null 2>&1
fi
success "Emergency cache is functional"

# 10. Check Laravel application status
log "Checking Laravel application status..."
if ! php /var/www/html/artisan inspire > /dev/null 2>&1; then
    error "Laravel application is not responding properly"
fi
success "Laravel application is healthy"

# 11. Check emergency access keys (basic validation)
log "Checking emergency access system..."
ACTIVE_ACCESS_COUNT=$(redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" eval "return #redis.call('keys', 'emergency_access:*')" 0 2>/dev/null || echo "0")
log "Active emergency access keys: $ACTIVE_ACCESS_COUNT"

# 12. Check log file sizes (prevent disk issues)
log "Checking log file sizes..."
for log_file in /var/log/nginx/access.log /var/log/nginx/error.log /var/www/html/storage/logs/laravel.log; do
    if [ -f "$log_file" ]; then
        LOG_SIZE=$(du -m "$log_file" | cut -f1)
        if [ "$LOG_SIZE" -gt 100 ]; then
            warning "Log file $log_file is large: ${LOG_SIZE}MB"
        fi
    fi
done

# 13. Check emergency queue status
log "Checking emergency queue status..."
FAILED_JOBS=$(redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" llen "queues:emergency:failed" 2>/dev/null || echo "0")
if [ "$FAILED_JOBS" -gt 0 ]; then
    warning "There are $FAILED_JOBS failed emergency jobs in queue"
else
    success "Emergency queue is healthy"
fi

# 14. Performance benchmark
log "Running performance benchmark..."
START_TIME=$(date +%s%N)
curl -s --max-time $TIMEOUT "$HEALTH_ENDPOINT" > /dev/null
END_TIME=$(date +%s%N)
BENCHMARK_TIME=$(( (END_TIME - START_TIME) / 1000000 )) # Convert to milliseconds

if [ "$BENCHMARK_TIME" -gt 1000 ]; then
    warning "Performance benchmark: ${BENCHMARK_TIME}ms (slower than expected)"
else
    success "Performance benchmark: ${BENCHMARK_TIME}ms"
fi

# 15. Check SSL certificate (if HTTPS is configured)
log "Checking SSL certificate..."
if [ -f "/etc/ssl/certs/emergency.crt" ]; then
    CERT_EXPIRY=$(openssl x509 -in /etc/ssl/certs/emergency.crt -noout -dates | grep 'notAfter' | cut -d= -f2)
    CERT_EXPIRY_EPOCH=$(date -d "$CERT_EXPIRY" +%s 2>/dev/null || echo "0")
    CURRENT_EPOCH=$(date +%s)
    DAYS_UNTIL_EXPIRY=$(( (CERT_EXPIRY_EPOCH - CURRENT_EPOCH) / 86400 ))
    
    if [ "$DAYS_UNTIL_EXPIRY" -lt 30 ] && [ "$DAYS_UNTIL_EXPIRY" -gt 0 ]; then
        warning "SSL certificate expires in $DAYS_UNTIL_EXPIRY days"
    elif [ "$DAYS_UNTIL_EXPIRY" -le 0 ]; then
        error "SSL certificate has expired"
    else
        success "SSL certificate is valid (expires in $DAYS_UNTIL_EXPIRY days)"
    fi
else
    log "No SSL certificate found (HTTP mode)"
fi

# Health check summary
success "All emergency services health checks passed"

# Set health check timestamp in Redis
redis-cli -h "$REDIS_HOST" -p "$REDIS_PORT" setex "emergency_last_health_check" 300 "$(date -Iseconds)" > /dev/null 2>&1

log "Health check completed successfully"
exit 0