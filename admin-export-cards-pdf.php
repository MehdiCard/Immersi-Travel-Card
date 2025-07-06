<?php
/**
 * includes/admin-export-cards-pdf.php
 * Génération d’un PDF pour les cartes ITC sélectionnées.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Hook pour traiter la requête d’export PDF.
 * Attendu : un formulaire POST envoyé vers admin-post.php?action=itc_export_selected_cards_pdf
 * avec un champ “post_ids” contenant un tableau d’IDs de cartes.
 */
add_action( 'admin_post_itc_export_selected_cards_pdf', 'itc_export_selected_cards_pdf_handler' );

function itc_export_selected_cards_pdf_handler() {
    // Vérification des permissions
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Permission refusée.', 'immersi-travel-card' ) );
    }

    // Récupération des IDs sélectionnés
    $post_ids = isset( $_POST['post_ids'] ) && is_array( $_POST['post_ids'] )
        ? array_map( 'intval', $_POST['post_ids'] )
        : array();

    if ( empty( $post_ids ) ) {
        wp_redirect( wp_get_referer() );
        exit;
    }

    // Chargement de FPDF
    require_once ITC_PLUGIN_PATH . 'lib/fpdf/fpdf.php';

    // Création du PDF
    $pdf = new FPDF( 'P', 'mm', 'A4' );
    foreach ( $post_ids as $post_id ) {
        $number = get_post_meta( $post_id, '_itc_card_number', true );
        $qr_url = get_post_meta( $post_id, '_itc_qr_url', true );

        // Téléchargement temporaire du QR code
        $tmp_file = download_url( $qr_url );
        if ( is_wp_error( $tmp_file ) ) {
            continue;
        }

        // Nouvelle page A4
        $pdf->AddPage();
        $pdf->SetFont( 'Arial', 'B', 16 );
        // Titre centré
        $pdf->Cell( 0, 10, sprintf( __( 'Carte : %s', 'immersi-travel-card' ), $number ), 0, 1, 'C' );

        // Affichage du QR code (60×60 mm centré horizontalement)
        $x = ( 210 - 60 ) / 2;
        $pdf->Image( $tmp_file, $x, 30, 60, 60 );

        @unlink( $tmp_file );
    }

    // Envoi des en-têtes pour téléchargement
    header( 'Content-Type: application/pdf' );
    header( 'Content-Disposition: attachment; filename="cartes-itc-' . date( 'YmdHis' ) . '.pdf"' );

    // Génération du PDF et sortie
    $pdf->Output( 'D', '' );
    exit;
}
