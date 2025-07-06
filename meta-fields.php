<?php
/**
 * Méta-champs pour le CPT « itc_card »
 *
 * Enregistre les métadonnées et migre les cartes existantes.
 */

// Sécurité : empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enregistre les métadonnées pour les cartes ITC
 */
function itc_register_meta_fields() {
    $post_type = 'itc_card';

    register_post_meta( $post_type, '_itc_activation_date', [
        'type'              => 'string',
        'single'            => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest'      => true,
    ] );

    register_post_meta( $post_type, '_itc_expiration_date', [
        'type'              => 'string',
        'single'            => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest'      => true,
    ] );

    register_post_meta( $post_type, '_itc_revendeur_prenom', [
        'type'              => 'string',
        'single'            => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest'      => true,
    ] );

    register_post_meta( $post_type, '_itc_status', [
        'type'              => 'string',
        'single'            => true,
        'sanitize_callback' => 'sanitize_text_field',
        'show_in_rest'      => true,
    ] );

    register_post_meta( $post_type, '_itc_order_amount', [
        'type'              => 'number',
        'single'            => true,
        'sanitize_callback' => 'absint',
        'show_in_rest'      => true,
    ] );
}
add_action( 'init', 'itc_register_meta_fields' );

/**
 * Migre les cartes existantes pour créer les méta qui manquent
 */
function itc_migrate_existing_meta() {
    $cards = get_posts([
        'post_type'      => 'itc_card',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ]);

    $meta_keys = [
        '_itc_activation_date',
        '_itc_expiration_date',
        '_itc_revendeur_prenom',
        '_itc_status',
        '_itc_order_amount',
    ];

    foreach ( $cards as $card_id ) {
        foreach ( $meta_keys as $key ) {
            if ( ! metadata_exists( 'post', $card_id, $key ) ) {
                update_post_meta( $card_id, $key, '' );
            }
        }
    }
}
