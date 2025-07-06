<?php
/**
 * Plugin Name: Immersi Travel Card
 * Description: Gestion des cartes ITC – génération, activation, QR, validité, et réception des commandes revendeurs.
 * Version:     1.0.0
 * Author:      Mehdi
 * Text Domain: immersi-travel-card
 * Domain Path: /languages
 */

// Sécurité : empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Version pour le versionning des assets
if ( ! defined( 'ITC_VERSION' ) ) {
    define( 'ITC_VERSION', '1.0.0' );
}

// Constantes du plugin
if ( ! defined( 'ITC_PLUGIN_PATH' ) ) {
    define( 'ITC_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'ITC_PLUGIN_URL' ) ) {
    define( 'ITC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Chargement de la traduction
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain(
        'immersi-travel-card',
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
} );

// Inclusion du fichier de définition des métadonnées
require_once ITC_PLUGIN_PATH . 'includes/meta-fields.php';

// Inclusion de la librairie FPDF (pour l’export PDF)
if ( file_exists( ITC_PLUGIN_PATH . 'includes/lib/fpdf/fpdf.php' ) ) {
    require_once ITC_PLUGIN_PATH . 'includes/lib/fpdf/fpdf.php';
}

// Inclusion des fonctionnalités principales
require_once ITC_PLUGIN_PATH . 'functions.php';                    // CPT, menus, shortcodes, loader de modules
require_once ITC_PLUGIN_PATH . 'includes/admin-bulk-handler.php';  // Bulk actions (CSV + PDF)
require_once ITC_PLUGIN_PATH . 'includes/generate-cards.php';      // Génération de cartes en lot
require_once ITC_PLUGIN_PATH . 'includes/qr-generator.php';        // Génération du QR code
require_once ITC_PLUGIN_PATH . 'includes/custom-columns.php';      // Colonnes personnalisées du CPT
require_once ITC_PLUGIN_PATH . 'includes/admin-page.php';          // Formulaire back-office de génération de cartes
require_once ITC_PLUGIN_PATH . 'includes/admin-card-metaboxes.php';// Meta-box détails carte
// Gestion frontend des dispatch QR depuis functions.php uniquement
// require_once ITC_PLUGIN_PATH . 'includes/route-handler.php';       // Gestion frontend des dispatch QR

// Module Imprimerie
require_once ITC_PLUGIN_PATH . 'includes/admin-imprimerie.php';            // Menu et page Imprimerie
require_once ITC_PLUGIN_PATH . 'includes/admin-imprimerie-list-table.php'; // Classe WP_List_Table Imprimerie

// Génération automatique des cartes pour les commandes
require_once ITC_PLUGIN_PATH . 'includes/admin-order-handler.php';         // Génération des cartes à la publication d'une commande

// Disable order delete pour revendeur
require_once ITC_PLUGIN_PATH . 'includes/disable-order-delete.php';       // Retrait des actions delete/trash

// Handlers AJAX et activation via AJAX
require_once ITC_PLUGIN_PATH . 'includes/ajax-handlers.php';              // Handlers AJAX front-end historiques et activation
require_once ITC_PLUGIN_PATH . 'includes/card-functions.php';             // Logique d’activation et création compte client

// Assets front-end Dashboard revendeur
require_once ITC_PLUGIN_PATH . 'includes/frontend-assets.php';            // Scripts/styles front-end historiques

// Front-end revendeur templates (chargés via shortcode uniquement)
// require_once ITC_PLUGIN_PATH . 'includes/frontend-revendeur-dashboard.php';
// require_once ITC_PLUGIN_PATH . 'includes/frontend-revendeur-login.php';
// require_once ITC_PLUGIN_PATH . 'includes/frontend-revendeur-order-form.php';

// Génération, QR, shortcodes, routing etc.
require_once ITC_PLUGIN_PATH . 'includes/revendeur-role.php';             // Déclaration du rôle revendeur
require_once ITC_PLUGIN_PATH . 'includes/revendeur-shortcodes.php';       // Shortcodes front revendeur

// Bloquer l’accès admin pour tous les rôles sans capacité d’édition (exclut AJAX)
add_action( 'admin_init', function() {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        return;
    }
    if ( is_user_logged_in() && ! current_user_can( 'edit_posts' ) ) {
        wp_redirect( home_url( '/acces-client/' ) );
        exit;
    }
} );

// Activation du plugin
register_activation_hook( __FILE__, 'itc_plugin_activation' );
function itc_plugin_activation() {
    // Flush rewrites pour les QR
    if ( function_exists( 'itc_flush_rewrites_on_activation' ) ) {
        itc_flush_rewrites_on_activation();
    }
    // Cron d'expiration
    if ( ! wp_next_scheduled( 'itc_daily_cron' ) ) {
        wp_schedule_event( time(), 'daily', 'itc_daily_cron' );
    }
    // Migration des métadonnées existantes
    if ( function_exists( 'itc_migrate_existing_meta' ) ) {
        itc_migrate_existing_meta();
    }
}

// Désactivation du plugin
register_deactivation_hook( __FILE__, 'itc_plugin_deactivation' );
function itc_plugin_deactivation() {
    flush_rewrite_rules();
    $timestamp = wp_next_scheduled( 'itc_daily_cron' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'itc_daily_cron' );
    }
}

// Initialisation des CPTs, shortcodes et modules
add_action( 'init', 'itc_plugin_init_components' );
function itc_plugin_init_components() {
    if ( function_exists( 'itc_register_cpt' ) ) {
        itc_register_cpt();
    }
    if ( function_exists( 'itc_register_shortcodes' ) ) {
        itc_register_shortcodes();
    }
}
