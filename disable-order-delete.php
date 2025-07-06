<?php
// includes/disable-order-delete.php
// Désactive la suppression des commandes pour les utilisateurs « revendeur »

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Retire le lien « Mettre à la corbeille » et « Supprimer définitivement »
 * dans la liste des commandes (post type itc_card_order) pour les revendeurs.
 */
function itc_disable_revendeur_delete_order( $actions, $post ) {
    if ( 'itc_card_order' === $post->post_type && current_user_can( 'itc_revendeur' ) ) {
        // Retirer l’action de mise à la corbeille
        if ( isset( $actions['trash'] ) ) {
            unset( $actions['trash'] );
        }
        // Retirer l’action de suppression définitive
        if ( isset( $actions['delete'] ) ) {
            unset( $actions['delete'] );
        }
    }
    return $actions;
}
add_filter( 'post_row_actions', 'itc_disable_revendeur_delete_order', 10, 2 );

/**
 * Retire l’option de suppression en masse dans la liste des commandes
 * pour les revendeurs.
 */
function itc_disable_revendeur_bulk_delete( $bulk_actions ) {
    if ( current_user_can( 'itc_revendeur' ) ) {
        if ( isset( $bulk_actions['trash'] ) ) {
            unset( $bulk_actions['trash'] );
        }
    }
    return $bulk_actions;
}
add_filter( 'bulk_actions-edit-itc_card_order', 'itc_disable_revendeur_bulk_delete', 10, 1 );
