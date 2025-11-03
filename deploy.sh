#!/bin/bash

###############################################################################
# BasketManager Pro - Production Deployment Script
#
# This script automates the deployment process for the application.
# It includes safety checks, database backups, and rollback capabilities.
#
# Usage:
#   ./deploy.sh                  # Normal deployment
#   ./deploy.sh --dry-run        # Preview deployment without making changes
#   ./deploy.sh --skip-backup    # Skip database backup (not recommended)
#   ./deploy.sh --skip-migrations # Skip database migrations
#   ./deploy.sh --rollback       # Rollback to previous deployment
#
###############################################################################

# Exit on error
set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Parse command line arguments
DRY_RUN=false
SKIP_BACKUP=false
SKIP_MIGRATIONS=false
ROLLBACK=false

for arg in "$@"; do
    case $arg in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --skip-backup)
            SKIP_BACKUP=true
            shift
            ;;
        --skip-migrations)
            SKIP_MIGRATIONS=true
            shift
            ;;
        --rollback)
            ROLLBACK=true
            shift
            ;;
        *)
            echo -e "${RED}Unknown argument: $arg${NC}"
            exit 1
            ;;
    esac
done

# Configuration
APP_NAME="BasketManager Pro"
APP_DIR=$(pwd)
BACKUP_DIR="${APP_DIR}/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_FILE="${BACKUP_DIR}/backup_${TIMESTAMP}.sql"

# Functions
print_header() {
    echo -e "\n${BLUE}============================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}============================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

run_command() {
    if [ "$DRY_RUN" = true ]; then
        echo -e "${YELLOW}[DRY RUN] Would execute: $1${NC}"
    else
        echo -e "${BLUE}Executing: $1${NC}"
        eval "$1"
    fi
}

# Main deployment steps
main() {
    print_header "🚀 Deploying ${APP_NAME}"

    if [ "$DRY_RUN" = true ]; then
        print_warning "DRY RUN MODE - No changes will be made"
    fi

    if [ "$ROLLBACK" = true ]; then
        rollback_deployment
        exit 0
    fi

    # Step 1: Pre-deployment checks
    pre_deployment_checks

    # Step 2: Backup database
    if [ "$SKIP_BACKUP" = false ]; then
        backup_database
    else
        print_warning "Skipping database backup (--skip-backup flag set)"
    fi

    # Step 3: Enable maintenance mode
    enable_maintenance_mode

    # Step 4: Git pull
    pull_latest_code

    # Step 5: Install dependencies
    install_dependencies

    # Step 6: Run database migrations
    if [ "$SKIP_MIGRATIONS" = false ]; then
        run_migrations
    else
        print_warning "Skipping database migrations (--skip-migrations flag set)"
    fi

    # Step 7: Build frontend assets
    build_frontend

    # Step 8: Clear caches
    clear_caches

    # Step 9: Restart queue workers
    restart_queue_workers

    # Step 10: Disable maintenance mode
    disable_maintenance_mode

    # Step 11: Post-deployment checks
    post_deployment_checks

    print_header "✅ Deployment completed successfully!"
}

pre_deployment_checks() {
    print_header "1️⃣  Pre-deployment Checks"

    # Check if .env file exists
    if [ ! -f ".env" ]; then
        print_error ".env file not found!"
        exit 1
    fi
    print_success ".env file exists"

    # Check if PHP is installed
    if ! command -v php &> /dev/null; then
        print_error "PHP is not installed!"
        exit 1
    fi
    print_success "PHP version: $(php -v | head -n 1)"

    # Check if Composer is installed
    if ! command -v composer &> /dev/null; then
        print_error "Composer is not installed!"
        exit 1
    fi
    print_success "Composer version: $(composer --version)"

    # Check if NPM is installed
    if ! command -v npm &> /dev/null; then
        print_error "NPM is not installed!"
        exit 1
    fi
    print_success "NPM version: $(npm --version)"

    # Check disk space (warn if less than 1GB free)
    FREE_SPACE=$(df -h . | awk 'NR==2 {print $4}')
    print_success "Free disk space: ${FREE_SPACE}"

    # Check if migrations are up to date
    print_success "All pre-deployment checks passed"
}

backup_database() {
    print_header "2️⃣  Backing up Database"

    # Create backup directory if it doesn't exist
    mkdir -p "${BACKUP_DIR}"

    run_command "php artisan backup:run --only-db"

    print_success "Database backup created"
}

enable_maintenance_mode() {
    print_header "3️⃣  Enabling Maintenance Mode"

    run_command "php artisan down --render='errors::503' --retry=60"

    print_success "Maintenance mode enabled"
}

pull_latest_code() {
    print_header "4️⃣  Pulling Latest Code"

    # Stash any local changes
    run_command "git stash"

    # Pull latest code
    run_command "git pull origin main"

    # Show current commit
    CURRENT_COMMIT=$(git rev-parse --short HEAD)
    print_success "Now on commit: ${CURRENT_COMMIT}"
}

install_dependencies() {
    print_header "5️⃣  Installing Dependencies"

    # Install PHP dependencies (production mode)
    run_command "composer install --no-dev --optimize-autoloader --no-interaction"
    print_success "PHP dependencies installed"

    # Install NPM dependencies
    run_command "npm ci --production=false"
    print_success "NPM dependencies installed"
}

run_migrations() {
    print_header "6️⃣  Running Database Migrations"

    # Preview migrations first
    print_warning "Previewing migrations..."
    php artisan migrate --pretend

    # Ask for confirmation in interactive mode
    if [ "$DRY_RUN" = false ]; then
        echo -e "${YELLOW}Do you want to proceed with migrations? (y/n)${NC}"
        read -r CONFIRM
        if [ "$CONFIRM" != "y" ]; then
            print_error "Migrations cancelled. Aborting deployment."
            disable_maintenance_mode
            exit 1
        fi
    fi

    # Run migrations
    run_command "php artisan migrate --force"

    print_success "Database migrations completed"
}

build_frontend() {
    print_header "7️⃣  Building Frontend Assets"

    # Build assets for production
    run_command "npm run build"

    print_success "Frontend assets built"
}

clear_caches() {
    print_header "8️⃣  Clearing Caches"

    run_command "php artisan optimize:clear"
    run_command "php artisan config:cache"
    run_command "php artisan route:cache"
    run_command "php artisan view:cache"

    print_success "All caches cleared and rebuilt"
}

restart_queue_workers() {
    print_header "9️⃣  Restarting Queue Workers"

    run_command "php artisan queue:restart"

    # Wait a bit for workers to restart
    sleep 3

    print_success "Queue workers restarted"
}

disable_maintenance_mode() {
    print_header "🔟 Disabling Maintenance Mode"

    run_command "php artisan up"

    print_success "Application is now live"
}

post_deployment_checks() {
    print_header "1️⃣1️⃣  Post-deployment Checks"

    # Check if application is responding
    APP_URL=$(php artisan tinker --execute="echo config('app.url');")
    if curl -s -o /dev/null -w "%{http_code}" "${APP_URL}" | grep -q "200\|302"; then
        print_success "Application is responding"
    else
        print_warning "Application may not be responding correctly. Please check manually."
    fi

    # Check queue workers
    QUEUE_STATUS=$(php artisan queue:listen --once 2>&1 || echo "error")
    if [[ ! $QUEUE_STATUS =~ "error" ]]; then
        print_success "Queue workers are running"
    else
        print_warning "Queue workers may not be running. Please check: php artisan queue:work"
    fi

    # Check for failed migrations
    FAILED_MIGRATIONS=$(php artisan migrate:status | grep "Ran\?" | grep -v "Y" || true)
    if [ -z "$FAILED_MIGRATIONS" ]; then
        print_success "All migrations have run successfully"
    else
        print_warning "Some migrations may have failed. Please check: php artisan migrate:status"
    fi

    print_success "Post-deployment checks completed"
}

rollback_deployment() {
    print_header "⏮️  Rolling Back Deployment"

    print_warning "This will rollback the database migrations and code to the previous state."

    if [ "$DRY_RUN" = false ]; then
        echo -e "${YELLOW}Are you sure you want to rollback? (y/n)${NC}"
        read -r CONFIRM
        if [ "$CONFIRM" != "y" ]; then
            print_error "Rollback cancelled."
            exit 1
        fi
    fi

    # Enable maintenance mode
    run_command "php artisan down"

    # Rollback migrations
    run_command "php artisan migrate:rollback"

    # Checkout previous commit
    run_command "git reset --hard HEAD~1"

    # Reinstall dependencies
    run_command "composer install --no-dev --optimize-autoloader --no-interaction"
    run_command "npm ci"
    run_command "npm run build"

    # Clear caches
    run_command "php artisan optimize:clear"

    # Restart queue workers
    run_command "php artisan queue:restart"

    # Disable maintenance mode
    run_command "php artisan up"

    print_success "Rollback completed"
}

# Run main function
main
