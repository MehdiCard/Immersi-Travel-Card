<?php
// includes/frontend-assets.php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Charge DataTables et les assets front pour l'espace Revendeur (Dashboard & Historique intégrés)
 */
function itc_enqueue_front_dashboard_assets() {
    global $post;
    // Charger uniquement sur la page Dashboard revendeur ou via le shortcode [itc_revendeur_dashboard]
    if ( is_page( 'revendeur-dashboard' ) || ( is_singular() && has_shortcode( $post->post_content, 'itc_revendeur_dashboard' ) ) ) {
        // OK : on continue
    } else {
        return;
    }

    // DataTables CSS
    wp_enqueue_style(
        'itc-datatables-css',
        'https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css',
        [],
        '1.13.4'
    );

    // Styles custom pour le dashboard revendeur
    wp_enqueue_style(
        'itc-front-dashboard-css',
        ITC_PLUGIN_URL . 'assets/css/front-dashboard.css',
        [],
        defined( 'ITC_VERSION' ) ? ITC_VERSION : '1.0'
    );

    // DataTables JS
    wp_enqueue_script(
        'itc-datatables-js',
        'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js',
        [ 'jquery' ],
        '1.13.4',
        true
    );

    // Script custom front-dashboard
    wp_enqueue_script(
        'itc-front-dashboard',
        ITC_PLUGIN_URL . 'assets/js/front-dashboard.js',
        [ 'jquery', 'itc-datatables-js' ],
        defined( 'ITC_VERSION' ) ? ITC_VERSION : '1.0',
        true
    );

    // Localisation du script AJAX
    wp_localize_script(
        'itc-front-dashboard',
        'itc_ajax_object',
        [
            'ajax_url'         => admin_url( 'admin-ajax.php' ),
            'activation_nonce' => wp_create_nonce( 'itc_activation_nonce' ),
            'history_nonce'    => wp_create_nonce( 'itc_history_nonce' ),
        ]
    );
}
add_action( 'wp_enqueue_scripts', 'itc_enqueue_front_dashboard_assets' );
