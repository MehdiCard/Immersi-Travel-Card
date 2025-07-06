<?php
// includes/revendeur-shortcodes.php
// Déclaration des shortcodes pour l'espace Revendeur et inclusion des scripts d'activation

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enregistre les shortcodes Revendeur
 */
add_action( 'init', 'itc_register_revendeur_shortcodes' );
function itc_register_revendeur_shortcodes() {
    add_shortcode( 'itc_revendeur_login',     'itc_shortcode_revendeur_login' );
    add_shortcode( 'itc_revendeur_dashboard', 'itc_shortcode_revendeur_dashboard' );
}

/**
 * Enqueue scripts pour l'activation via AJAX
 */
add_action( 'wp_enqueue_scripts', 'itc_enqueue_revendeur_scripts' );
function itc_enqueue_revendeur_scripts() {
    // Charger uniquement si on est sur une page contenant le dashboard revendeur
    if ( has_shortcode( get_post()->post_content, 'itc_revendeur_dashboard' ) ) {
        wp_enqueue_script(
            'itc-front-dashboard',
            ITC_PLUGIN_URL . 'assets/js/front-dashboard.js',
            array( 'jquery' ),
            null,
            true
        );
        wp_localize_script(
            'itc-front-dashboard',
            'itcRevendeur',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'itc-activation-nonce' ),
                'action'   => 'itc_activate_card',
            )
        );
    }
}

/**
 * Shortcode [itc_revendeur_login]
 */
function itc_shortcode_revendeur_login( $atts ) {
    // Si déjà connecté en tant que revendeur, redirige vers le dashboard
    if ( is_user_logged_in() && current_user_can( 'itc_revendeur' ) ) {
        wp_safe_redirect( home_url( '/revendeur-dashboard' ) );
        exit;
    }

    ob_start();
    include ITC_PLUGIN_PATH . 'includes/frontend-revendeur-login.php';
    return ob_get_clean();
}

/**
 * Shortcode [itc_revendeur_dashboard]
 */
function itc_shortcode_revendeur_dashboard( $atts ) {
    // Ne pas rediriger si on est dans l'éditeur Elementor ou dans l’admin
    if ( is_admin() || isset( $_GET['elementor-preview'] ) ) {
        return '<p style="color:green;">Prévisualisation du tableau de bord revendeur.</p>';
    }

    // Si pas connecté ou pas revendeur, renvoie vers la page login
    if ( ! is_user_logged_in() || ! current_user_can( 'itc_revendeur' ) ) {
        wp_safe_redirect( home_url( '/revendeur-login' ) );
        exit;
    }

    ob_start();
    include ITC_PLUGIN_PATH . 'includes/frontend-revendeur-dashboard.php';
    return ob_get_clean();
}
