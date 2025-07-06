<?php
// includes/admin-order-handler.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Génère automatiquement les cartes lors de la sauvegarde d'une commande revendeur
 */
add_action( 'save_post_itc_card_order', 'itc_handle_new_order', 10, 3 );
function itc_handle_new_order( $post_id, $post, $update ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( wp_is_post_revision( $post_id ) ) return;
    if ( 'itc_card_order' !== $post->post_type ) return;
    if ( get_post_meta( $post_id, '_itc_cards_generated', true ) ) return;

    $quantity = absint( get_post_meta( $post_id, '_itc_order_quantity', true ) );
    if ( $quantity < 1 ) return;

    $rev_id = get_post_meta( $post_id, '_itc_order_revendeur', true );
    if ( ! function_exists( 'itc_generate_qr_code' ) ) {
        require_once ITC_PLUGIN_PATH . 'includes/qr-generator.php';
    }

    for ( $i = 0; $i < $quantity; $i++ ) {
        $card_number = 'ITC-' . wp_rand( 100000, 999999 );
        $card_id = wp_insert_post([
            'post_type'   => 'itc_card',
            'post_title'  => $card_number,
            'post_status' => 'publish',
        ]);
        if ( is_wp_error( $card_id ) || ! $card_id ) {
            continue;
        }

        $activation_url = home_url( '/carte/' . $card_number );
        $qr_url = itc_generate_qr_code( $activation_url );

        update_post_meta( $card_id, '_itc_card_number',     $card_number );
        update_post_meta( $card_id, '_itc_qr_url',          $qr_url );
        update_post_meta( $card_id, '_itc_status',          'non_active' );
        update_post_meta( $card_id, '_itc_activation_date', '' );
        update_post_meta( $card_id, '_itc_expiration_date', '' );
        update_post_meta( $card_id, '_itc_revendeur',       $rev_id );
        update_post_meta( $card_id, '_itc_order_id',        $post_id );
    }

    update_post_meta( $post_id, '_itc_cards_generated', 1 );
}

/**
 * Exporte un ou plusieurs historiques de commandes en PDF et affiche inline
 *
 * @param array $order_ids Liste des IDs de commandes à exporter.
 */
function itc_export_orders_pdf( array $order_ids ) {
    // Vider les buffers pour éviter les conflits de headers
    while ( ob_get_level() ) {
        ob_end_clean();
    }

    require_once ITC_PLUGIN_PATH . 'includes/lib/fpdf/fpdf.php';

    $pdf = new FPDF();
    $pdf->AddPage();
    // Titre
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, utf8_decode('Historique des Commandes'), 0, 1, 'C');
    $pdf->Ln(5);

    // En-tête de colonnes
    $pdf->SetFont('Arial', 'B', 12);
    // Largeurs : ID 20, Revendeur 40, Date 40, Qté 25, Note 65 = 190 total
    $pdf->Cell(20, 8, utf8_decode('ID'),           1, 0, 'C');
    $pdf->Cell(40, 8, utf8_decode('Revendeur'),    1, 0, 'C');
    $pdf->Cell(40, 8, utf8_decode('Date'),         1, 0, 'C');
    $pdf->Cell(25, 8, utf8_decode('Quantité'),     1, 0, 'C');
    $pdf->Cell(65, 8, utf8_decode('Note'),         1, 1, 'C');

    // Contenu
    $pdf->SetFont('Arial', '', 12);
    foreach ( $order_ids as $order_id ) {
        $date    = get_the_date( 'Y-m-d H:i', $order_id );
        $qty     = get_post_meta( $order_id, '_itc_order_quantity', true );
        $note    = get_post_meta( $order_id, '_itc_order_note',     true );
        $rev_id  = get_post_meta( $order_id, '_itc_order_revendeur', true );
        $rev_name = '';
        if ( $rev_id ) {
            $user = get_userdata( $rev_id );
            if ( $user ) {
                $rev_name = $user->first_name . ' ' . $user->last_name;
            }
        }

        // Chaque cellule rendue avec bordure
        $pdf->Cell(20, 8, utf8_decode( $order_id ),                1, 0, 'C');
        $pdf->Cell(40, 8, utf8_decode( $rev_name ),                1, 0, 'L');
        $pdf->Cell(40, 8, utf8_decode( $date ),                    1, 0, 'C');
        $pdf->Cell(25, 8, utf8_decode( $qty ),                     1, 0, 'C');
        $pdf->Cell(65, 8, utf8_decode( $note ),                    1, 1, 'L');
    }

    // Affichage inline
    if ( ! headers_sent() ) {
        header( 'Content-Type: application/pdf' );
        header( 'Content-Disposition: inline; filename="historique_commandes.pdf"' );
    }
    $pdf->Output( 'I', 'historique_commandes.pdf' );
    exit;
}
