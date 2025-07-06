<?php
// includes/admin-bulk-handler.php
// Gère les Bulk Actions pour le CPT itc_card : exporter CSV, exporter PDF, supprimer

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Ajouter Bulk Actions personnalisées
 */
function itc_register_bulk_actions( $bulk_actions ) {
    // Export CSV
    $bulk_actions['export_cards']     = __( 'Exporter CSV', 'immersi-travel-card' );
    // Export PDF
    $bulk_actions['export_cards_pdf'] = __( 'Exporter en PDF', 'immersi-travel-card' );
    return $bulk_actions;
}
add_filter( 'bulk_actions-edit-itc_card', 'itc_register_bulk_actions' );

/**
 * Intercepter les Bulk Actions
 */
function itc_handle_bulk_actions( $redirect_to, $action, $post_ids ) {
    // 1) Export CSV
    if ( $action === 'export_cards' ) {
        $filename = 'cartes-itc-export-' . date( 'YmdHis' ) . '.csv';
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

        $output = fopen( 'php://output', 'w' );
        fputcsv( $output, array( 'Numéro de Carte', 'Statut', 'Revendeur', "Date d'Activation" ) );

        foreach ( $post_ids as $post_id ) {
            $number = get_post_meta( $post_id, '_itc_card_number', true );
            $status = get_post_meta( $post_id, '_itc_status', true );
            $rev    = get_post_meta( $post_id, '_itc_revendeur', true );
            $date   = get_post_meta( $post_id, '_itc_activation_date', true );
            fputcsv( $output, array( $number, $status, $rev, $date ) );
        }
        exit;
    }

    // 2) Export PDF
    if ( $action === 'export_cards_pdf' ) {
        itc_export_cards_pdf_by_ids( $post_ids );
        exit;
    }

    return $redirect_to;
}
add_filter( 'handle_bulk_actions-edit-itc_card', 'itc_handle_bulk_actions', 10, 3 );

/**
 * Génère et envoie un PDF contenant numéro et QR code des cartes.
 */
function itc_export_cards_pdf_by_ids( array $post_ids ) {
    // Charger FPDF depuis includes/lib/fpdf
    require_once ITC_PLUGIN_PATH . 'includes/lib/fpdf/fpdf.php';

    $pdf = new FPDF( 'P', 'mm', 'A4' );
    foreach ( $post_ids as $post_id ) {
        $number = get_post_meta( $post_id, '_itc_card_number', true );
        $qr_url = get_post_meta( $post_id, '_itc_qr_url', true );

        // Télécharger temporairement le QR code
        $tmp_file = download_url( $qr_url );
        if ( is_wp_error( $tmp_file ) ) {
            continue;
        }

        // Nouvelle page A4
        $pdf->AddPage();
        $pdf->SetFont( 'Arial', 'B', 16 );
        $pdf->Cell( 0, 10, sprintf( __( 'Carte : %s', 'immersi-travel-card' ), $number ), 0, 1, 'C' );

        // Centrer et afficher le QR code (60×60 mm)
        $x = (210 - 60) / 2;
        $pdf->Image( $tmp_file, $x, 30, 60, 60 );

        @unlink( $tmp_file );
    }

    // Envoyer le PDF au navigateur
    header( 'Content-Type: application/pdf' );
    header( 'Content-Disposition: attachment; filename="cartes-itc.pdf"' );
    $pdf->Output( 'D', 'cartes-itc.pdf' );
}
