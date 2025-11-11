#!/bin/bash

# Script de mise à jour des rôles et permissions pour la production
# Usage: ./scripts/update-permissions-production.sh [options]

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

show_help() {
    cat << EOF
🚀 Script de mise à jour des rôles et permissions

USAGE:
    $0 [options]

OPTIONS:
    --dry-run           Simule les changements sans les appliquer
    --reset-super-admin Remet à zéro les permissions du SuperAdmin
    --force             Force l'exécution sans confirmation
    --backup            Crée une sauvegarde avant les modifications
    --help              Affiche cette aide

EXEMPLES:
    # Voir ce qui serait modifié sans rien changer
    $0 --dry-run

    # Mise à jour normale avec confirmation
    $0

    # Mise à jour avec reset du SuperAdmin
    $0 --reset-super-admin

    # Mise à jour forcée avec sauvegarde
    $0 --force --backup

DESCRIPTION:
    Ce script met à jour les rôles et permissions pour synchroniser
    la base de données de production avec la configuration locale.

    Il effectue les actions suivantes :
    ✅ Crée les permissions manquantes
    ✅ Crée les rôles manquants
    ✅ Met à jour les permissions des rôles
    ✅ Optionnellement reset le SuperAdmin
    ✅ Vide les caches de l'application

EOF
}

create_backup() {
    log_info "Création d'une sauvegarde..."

    timestamp=$(date +"%Y%m%d_%H%M%S")
    backup_file="storage/backups/permissions_backup_${timestamp}.sql"

    mkdir -p storage/backups

    php artisan db:show | head -1 | grep -o 'mysql://[^"]*' > /dev/null 2>&1 || {
        log_error "Impossible de détecter la configuration de base de données"
        return 1
    }

    log_info "Sauvegarde des tables permissions et rôles..."
    php artisan backup:run --only-db --backup-to=storage/backups || {
        log_warning "La commande backup:run n'est pas disponible, tentative manuelle..."

        # Backup manuel si la commande backup n'existe pas
        DB_CONNECTION=$(php artisan env:get DB_CONNECTION)
        DB_HOST=$(php artisan env:get DB_HOST)
        DB_PORT=$(php artisan env:get DB_PORT)
        DB_DATABASE=$(php artisan env:get DB_DATABASE)
        DB_USERNAME=$(php artisan env:get DB_USERNAME)
        DB_PASSWORD=$(php artisan env:get DB_PASSWORD)

        if command -v mysqldump > /dev/null 2>&1; then
            mysqldump -h${DB_HOST} -P${DB_PORT} -u${DB_USERNAME} -p${DB_PASSWORD} ${DB_DATABASE} \
                permissions roles model_has_permissions model_has_roles role_has_permissions > ${backup_file} || {
                log_error "Échec de la sauvegarde manuelle"
                return 1
            }
            log_success "Sauvegarde créée: ${backup_file}"
        else
            log_warning "mysqldump non disponible, backup ignoré"
        fi
    }
}

confirm_production() {
    if [[ "${APP_ENV}" == "production" && "${FORCE}" != "true" ]]; then
        log_warning "⚠️  ENVIRONNEMENT DE PRODUCTION DÉTECTÉ ⚠️"
        echo ""
        echo "Vous êtes sur le point de modifier les rôles et permissions en production."
        echo "Cette action peut affecter l'accès des utilisateurs à l'application."
        echo ""
        read -p "Êtes-vous sûr de vouloir continuer ? (tapez 'OUI' pour confirmer): " confirmation

        if [[ "${confirmation}" != "OUI" ]]; then
            log_info "Opération annulée par l'utilisateur"
            exit 0
        fi
    fi
}

# Parse command line arguments
DRY_RUN=false
RESET_SUPER_ADMIN=false
FORCE=false
BACKUP=false

while [[ $# -gt 0 ]]; do
    case $1 in
        --dry-run)
            DRY_RUN=true
            shift
            ;;
        --reset-super-admin)
            RESET_SUPER_ADMIN=true
            shift
            ;;
        --force)
            FORCE=true
            shift
            ;;
        --backup)
            BACKUP=true
            shift
            ;;
        --help)
            show_help
            exit 0
            ;;
        *)
            log_error "Option inconnue: $1"
            show_help
            exit 1
            ;;
    esac
done

# Main execution
main() {
    log_info "🚀 Début de la mise à jour des rôles et permissions"

    # Check if we're in the Laravel root directory
    if [[ ! -f "artisan" ]]; then
        log_error "Ce script doit être exécuté depuis la racine du projet Laravel"
        exit 1
    fi

    # Load environment
    if [[ -f ".env" ]]; then
        export $(grep -v '^#' .env | xargs)
    fi

    # Confirm if production
    confirm_production

    # Create backup if requested
    if [[ "${BACKUP}" == "true" && "${DRY_RUN}" != "true" ]]; then
        create_backup
    fi

    # Build command
    cmd="php artisan permissions:update"

    if [[ "${DRY_RUN}" == "true" ]]; then
        cmd="${cmd} --dry-run"
        log_warning "Mode simulation activé - aucun changement ne sera appliqué"
    fi

    if [[ "${RESET_SUPER_ADMIN}" == "true" ]]; then
        cmd="${cmd} --reset-super-admin"
        log_info "Reset du SuperAdmin activé"
    fi

    if [[ "${FORCE}" == "true" ]]; then
        cmd="${cmd} --force"
    fi

    # Execute the command
    log_info "Exécution: ${cmd}"
    echo ""

    if ${cmd}; then
        log_success "✅ Mise à jour terminée avec succès!"

        if [[ "${DRY_RUN}" != "true" ]]; then
            log_info "🔄 Redémarrage recommandé des workers/queues si applicable"
            log_info "📝 Vérifiez les logs d'application pour tout problème"
        fi
    else
        log_error "❌ Échec de la mise à jour"
        exit 1
    fi
}

# Trap to handle script interruption
trap 'log_warning "Script interrompu par l'\''utilisateur"; exit 130' INT

# Execute main function
main