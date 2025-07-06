<?php
// includes/expiration-cron.php
// Cron quotidien pour expirer automatiquement les cartes ITC au bout de 7 jours

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Exécute la tâche Cron pour expirer les cartes plus anciennes que 7 jours.
 */
function itc_expire_old_cards() {
    $args = array(
        'post_type'      => 'itc_card',
        'posts_per_page' => -1,
        'meta_query'     => array(
            array(
                'key'     => '_itc_expiration_date',
                'value'   => date( 'Y-m-d H:i:s', strtotime( '-1 minute', current_time( 'timestamp' ) ) ),
                'compare' => '<=',
                'type'    => 'DATETIME',
            ),
            array(
                'key'     => '_itc_status',
                'value'   => 'active',
                'compare' => '=',
            ),
        ),
    );
    $cards = get_posts( $args );

    foreach ( $cards as $card ) {
        update_post_meta( $card->ID, '_itc_status', 'expired' );
        // (optionnel) notifier l'utilisateur de l'expiration
        // $user_id = get_user_meta( $card->ID, '_itc_card_user_id', true );
        // itc_send_expiration_email( $user_id, get_post_meta( $card->ID, '_itc_card_number', true ) );
    }
}

// Planification ou hook du Cron
add_action( 'itc_daily_cron', 'itc_expire_old_cards' );

// Enregistrement de l'événement Cron si non existant
function itc_schedule_expiration_cron() {
    if ( ! wp_next_scheduled( 'itc_daily_cron' ) ) {
        wp_schedule_event( current_time( 'timestamp' ), 'daily', 'itc_daily_cron' );
    }
}
add_action( 'wp', 'itc_schedule_expiration_cron' );

// Désactivation du Cron à la désactivation du plugin
function itc_clear_expiration_cron() {
    $timestamp = wp_next_scheduled( 'itc_daily_cron' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'itc_daily_cron' );
    }
}
register_deactivation_hook( plugin_dir_path( __FILE__ ) . '../immersi-travel-card.php', 'itc_clear_expiration_cron' );
?>
