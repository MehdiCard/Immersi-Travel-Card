<?php
// includes/route-handler.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register custom query vars for dispatching
 */
add_filter( 'query_vars', 'itc_register_query_vars' );
function itc_register_query_vars( $vars ) {
    $vars[] = 'itc_dispatch';
    $vars[] = 'card_number';
    return $vars;
}

/**
 * Rewrite rule for /carte/{numero}
 */
add_action( 'init', 'itc_add_dispatch_rewrite_rules' );
function itc_add_dispatch_rewrite_rules() {
    add_rewrite_rule(
        '^carte/([^/]+)/?$',
        'index.php?itc_dispatch=1&card_number=$matches[1]',
        'top'
    );
}

/**
 * Rewrite rules for the Revendeur area (login & dashboard)
 */
add_action( 'init', 'itc_add_revendeur_rewrite_rules' );
function itc_add_revendeur_rewrite_rules() {
    add_rewrite_rule(
        '^revendeur/login/?$',
        'index.php?pagename=revendeur-login',
        'top'
    );
    add_rewrite_rule(
        '^revendeur/dashboard/?$',
        'index.php?pagename=revendeur-dashboard',
        'top'
    );
}/**
 * Front-end dispatch of requests
 */
add_action( 'template_redirect', 'itc_handle_frontend_dispatch', 0 );
function itc_handle_frontend_dispatch() {
    // Ne pas intercepter en admin, preview (Elementor/Gutenberg), AJAX,
    // Elementor edit ou page d'accès client
    if (
        is_admin() ||
        is_preview() ||
        ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ||
        isset( $_GET['elementor-preview'] ) ||
        is_page( 'acces-client' )
    ) {
        return;
    }

    global $wp_query;

    // Endpoint /carte/{numero}
    if ( isset( $wp_query->query_vars['itc_dispatch'] ) && ! empty( $wp_query->query_vars['card_number'] ) ) {
        $card_number = sanitize_text_field( wp_unslash( $wp_query->query_vars['card_number'] ) );
        itc_process_card_dispatch( $card_number );
    }
}

/**
 * Helper to process the QR dispatch
 */
function itc_process_card_dispatch( $card_number ) {
    // Debug si headers déjà envoyés
    if ( headers_sent() ) {
        error_log( "[ITC] Headers already sent before redirect for card {$card_number}" );
    }

    // Lookup the card post by its meta key
    $query = new WP_Query( array(
        'post_type'      => 'itc_card',
        'meta_key'       => '_itc_card_number',
        'meta_value'     => $card_number,
        'posts_per_page' => 1,
    ) );

    // Si pas trouvé, redirige vers carte-non-active
    if ( ! $query->have_posts() ) {
        wp_safe_redirect( home_url( '/carte-non-active/' ) );
        exit;
    }

    $post_id = $query->posts[0]->ID;
    $status  = get_post_meta( $post_id, '_itc_status', true );

    // Vérifie si expirée
    $exp_date = get_post_meta( $post_id, '_itc_expiration_date', true );
    if ( $exp_date ) {
        $exp = DateTime::createFromFormat( 'Y-m-d H:i:s', $exp_date );
        if ( $exp && current_time( 'timestamp' ) > $exp->getTimestamp() ) {
            $status = 'expired';
            update_post_meta( $post_id, '_itc_status', 'expired' );
        }
    }

    // Détermine l'URL cible selon le statut
    switch ( $status ) {
        case 'active':
            // Bypass login check en ajoutant itc_dispatch
            $url = add_query_arg( 'itc_dispatch', 1, home_url( '/offres/' ) );
            break;
        case 'expired':
            $url = home_url( '/carte-expiree/' );
            break;
        default:
            $url = home_url( '/carte-non-active/' );
            break;
    }

    wp_safe_redirect( $url );
    exit;
}

/**
 * Flush rewrite rules on plugin activation
 */
register_activation_hook( __FILE__, 'itc_flush_rewrites_on_activation' );
function itc_flush_rewrites_on_activation() {
    itc_add_dispatch_rewrite_rules();
    itc_add_revendeur_rewrite_rules();
    flush_rewrite_rules();
}
