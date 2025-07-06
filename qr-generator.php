<?php
// includes/qr-generator.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Charger la librairie PHPQRCode depuis le dossier includes/lib/phpqrcode/
require_once __DIR__ . '/lib/phpqrcode/qrlib.php';

/**
 * Génère un QR code PNG pour une donnée (numéro de carte ou URL) et renvoie l'URL publique.
 *
 * @param string      $data     Donnée à encoder dans le QR (ex. URL d’activation, numéro de carte).
 * @param string|null $filename Nom de fichier (sans extension). Si null, généré automatiquement.
 * @param string      $ecLevel  Niveau de correction d’erreur : 'L', 'M', 'Q' ou 'H'. Default 'H'.
 * @param int         $size     Taille du module (taille des « pixels »). Default 4.
 * @param int         $margin   Marge autour du QR. Default 2.
 * @return string               URL publique vers le PNG généré.
 */
function itc_generate_qr_code( $data, $filename = null, $ecLevel = 'H', $size = 4, $margin = 2 ) {
    // Répertoire de stockage
    $upload_dir = wp_upload_dir();
    $qr_dir     = trailingslashit( $upload_dir['basedir'] ) . 'itc-qr/';
    $qr_url     = trailingslashit( $upload_dir['baseurl'] ) . 'itc-qr/';

    // Créer le dossier si nécessaire
    if ( ! file_exists( $qr_dir ) ) {
        wp_mkdir_p( $qr_dir );
    }

    // Définir ou nettoyer le nom de fichier
    if ( ! $filename ) {
        $filename = 'itc-' . md5( $data . time() );
    } else {
        $filename = sanitize_file_name( $filename );
    }
    $file_path = $qr_dir . $filename . '.png';

    // Déterminer le niveau de correction d’erreur
    $const_ec = 'QR_ECLEVEL_' . strtoupper( $ecLevel );
    if ( defined( $const_ec ) ) {
        $ec = constant( $const_ec );
    } else {
        $ec = QR_ECLEVEL_H;
    }

    // Génération du QR code
    QRcode::png( $data, $file_path, $ec, (int) $size, (int) $margin );

    return $qr_url . $filename . '.png';
}
