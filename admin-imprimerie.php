<?php
// includes/admin-imprimerie.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue des scripts pour la page Imprimerie et Détails
 */
add_action( 'admin_enqueue_scripts', 'itc_admin_imprimerie_scripts' );
function itc_admin_imprimerie_scripts( $hook ) {
    // Pages Imprimerie et Détails
    if ( ! in_array( $hook, ['toplevel_page_itc-imprimerie', 'immersi-travel-card_page_itc-imprimerie-detail'], true ) ) {
        return;
    }
    wp_enqueue_script(
        'itc-admin-imprimerie',
        ITC_PLUGIN_URL . 'assets/js/admin-imprimerie.js',
        [ 'jquery' ],
        '1.0',
        true
    );
    wp_localize_script(
        'itc-admin-imprimerie',
        'itcImprimerieAjax',
        [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'itc_admin_nonce' ),
        ]
    );
}

/**
 * Enregistre le menu Imprimerie et le sous-menu Détails (écran séparé)
 */
add_action( 'admin_menu', 'itc_register_imprimerie_menu' );
function itc_register_imprimerie_menu() {
    // Menu Imprimerie
    add_menu_page(
        __( 'Imprimerie', 'immersi-travel-card' ),
        __( 'Imprimerie', 'immersi-travel-card' ),
        'manage_options',
        'itc-imprimerie',
        'itc_render_imprimerie_page',
        'dashicons-media-document',
        55
    );
    // Sous-menu Détails (écran caché, accessible via order_id)
    add_submenu_page(
        null,
        __( 'Détails de la commande', 'immersi-travel-card' ),
        __( 'Détails', 'immersi-travel-card' ),
        'manage_options',
        'itc-imprimerie-detail',
        'itc_render_order_detail'
    );
}

// Charge la classe WP_List_Table pour Imprimerie
require_once ITC_PLUGIN_PATH . 'includes/admin-imprimerie-list-table.php';

/**
 * Affiche la page d'administration Imprimerie ou Détails
 */
function itc_render_imprimerie_page() {
    echo '<div class="wrap">';
    if ( isset( $_GET['order_id'] ) ) {
        // Vue détaillée
        itc_render_order_detail( intval( $_GET['order_id'] ) );
    } else {
        echo '<h1>' . esc_html__( 'Imprimerie', 'immersi-travel-card' ) . '</h1>';
        echo '<form method="post">';
        $table = new Itc_Imprimerie_List_Table();
        $table->prepare_items();
        $table->display();
        echo '</form>';
    }
    echo '</div>';
}

/**
 * Basculer le statut Envoyé/Livré via AJAX.
 */
add_action( 'wp_ajax_itc_toggle_imprimerie_status', 'itc_toggle_imprimerie_status' );
function itc_toggle_imprimerie_status() {
    check_ajax_referer( 'itc_admin_nonce' );
    $order_id = intval( $_POST['order_id'] );
    $field    = in_array( $_POST['field'], ['_itc_exported','_itc_order_received'], true ) ? $_POST['field'] : '';
    $value    = isset( $_POST['value'] ) && '1' === $_POST['value'] ? 1 : 0;
    update_post_meta( $order_id, $field, $value );
    wp_send_json_success();
}

/**
 * Export PDF des commandes sélectionnées.
 */
add_action( 'admin_post_itc_export_imprimerie_pdf', 'itc_export_imprimerie_pdf' );
function itc_export_imprimerie_pdf() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Permission refusée.', 'immersi-travel-card' ) );
    }
    if ( empty( $_GET['ids'] ) ) {
        wp_redirect( admin_url( 'admin.php?page=itc-imprimerie' ) );
        exit;
    }
    $ids = array_map( 'intval', explode( ',', sanitize_text_field( wp_unslash( $_GET['ids'] ) ) ) );
    require_once ITC_PLUGIN_PATH . 'includes/lib/fpdf/fpdf.php';
    $upload_dir = wp_upload_dir();
    $pdf = new FPDF('P','mm','A4');
    foreach ( $ids as $order_id ) {
        $order = get_post( $order_id );
        if ( ! $order || 'itc_card_order' !== $order->post_type ) {
            continue;
        }
        // Données commande & revendeur
        $rev_id   = get_post_meta( $order_id, '_itc_order_revendeur', true );
        $quantity = get_post_meta( $order_id, '_itc_order_quantity', true );
        $note     = get_post_meta( $order_id, '_itc_order_note', true );
        $user     = get_userdata( $rev_id );
        $address  = get_user_meta( $rev_id, 'itc_billing_address', true );
        $phone    = get_user_meta( $rev_id, 'itc_billing_phone', true );
        // Page PDF
        $pdf->AddPage();
        $pdf->SetFont('Arial','B',14);
        $pdf->Cell(0,10, sprintf( __( 'Commande #%d', 'immersi-travel-card' ), $order_id ), 0,1);
        $pdf->SetFont('Arial','',12);
        // Revendeur
        $login = $user ? $user->user_login : $rev_id;
        $pdf->Cell(0,8, sprintf( __( 'Revendeur: %s', 'immersi-travel-card' ), $login ),0,1);
        if ( $user ) {
            $pdf->Cell(0,8, sprintf( __( 'Email: %s', 'immersi-travel-card' ), $user->user_email ),0,1);
        }
        if ( $address ) {
            $pdf->MultiCell(0,8, sprintf( __( 'Adresse: %s', 'immersi-travel-card' ), $address ));
        }
        if ( $phone ) {
            $pdf->Cell(0,8, sprintf( __( 'Téléphone: %s', 'immersi-travel-card' ), $phone ),0,1);
        }
        $pdf->Ln(4);
        $pdf->Cell(0,8, sprintf( __( 'Quantité: %d', 'immersi-travel-card' ), $quantity ),0,1);
        if ( $note ) {
            $pdf->MultiCell(0,8, sprintf( __( 'Commentaire: %s', 'immersi-travel-card' ), $note ));
        }
        $pdf->Ln(6);
        // Table cartes
        $pdf->SetFont('Arial','B',12);
        $pdf->Cell(80,10, __( 'Numéro de carte', 'immersi-travel-card' ),1);
        $pdf->Cell(80,10, __( 'QR Code', 'immersi-travel-card' ),1);
        $pdf->Ln();
        $cards = new WP_Query([
            'post_type'      => 'itc_card',
            'post_status'    => 'publish',
            'meta_query'     => [[ 'key'=>'_itc_order_id','value'=>$order_id ]],
            'posts_per_page' => -1,
        ]);
        if ( $cards->have_posts() ) {
            while ( $cards->have_posts() ) {
                $cards->the_post();
                $card_id = get_the_ID();
                $number  = get_post_meta( $card_id, '_itc_card_number', true );
                $qr_url  = get_post_meta( $card_id, '_itc_qr_url', true );
                $pdf->SetFont('Arial','',12);
                $pdf->Cell(80,20,$number,1);
                $file = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $qr_url );
                $x = $pdf->GetX(); $y = $pdf->GetY();
                $pdf->Cell(80,20,'',1);
                if ( file_exists( $file ) ) {
                    $pdf->Image($file,$x+2,$y+2,16,16);
                }
                $pdf->Ln();
            }
            wp_reset_postdata();
        } else {
            $pdf->Cell(160,8, __( 'Aucune carte générée.', 'immersi-travel-card' ),1,1,'C');
        }
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="imprimerie_export.pdf"');
    $pdf->Output('I','imprimerie_export.pdf');
    exit;
}

/**
 * Export CSV des commandes sélectionnées.
 */
add_action( 'admin_post_itc_export_imprimerie_csv', 'itc_export_imprimerie_csv' );
function itc_export_imprimerie_csv() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( __( 'Permission refusée.', 'immersi-travel-card' ) );
    }
    if ( empty( $_GET['ids'] ) ) {
        wp_redirect( admin_url( 'admin.php?page=itc-imprimerie' ) );
        exit;
    }
    $ids = array_map( 'intval', explode(',', sanitize_text_field( wp_unslash( $_GET['ids'] ) ) ) );
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="imprimerie_export.csv"');
    $output = fopen('php://output','w');
    fputcsv($output, [ 'Revendeur', 'Quantité', 'Date', 'Envoyé', 'Livré' ]);
    foreach($ids as $order_id) {
        $date     = get_post_field('post_date',$order_id);
        $rev_id   = get_post_meta($order_id,'_itc_order_revendeur',true);
        $user     = get_userdata($rev_id);
        $login    = $user?$user->user_login:$rev_id;
        $quantity = get_post_meta($order_id,'_itc_order_quantity',true);
        $exported = get_post_meta($order_id,'_itc_exported',true)?'1':'0';
        $received = get_post_meta($order_id,'_itc_order_received',true)?'1':'0';
        fputcsv($output,[$login,$quantity,$date,$exported,$received]);
    }
    fclose($output);
    exit;
}

/**
 * Vue détaillée d'une commande
 */
function itc_render_order_detail( $order_id ) {
    $order = get_post($order_id);
    if(!$order||$order->post_type!=='itc_card_order'){echo '<div class="notice notice-error"><p>'.esc_html__('Commande invalide.','immersi-travel-card').'</p></div>';return;}
    $rev_id   = get_post_meta($order_id,'_itc_order_revendeur',true);
    $user     = get_userdata($rev_id);
    $login    = $user?$user->user_login:$rev_id;
    $email    = $user?$user->user_email:'';
    $address  = get_user_meta($rev_id,'itc_billing_address',true);
    $phone    = get_user_meta($rev_id,'itc_billing_phone',true);
    $quantity = get_post_meta($order_id,'_itc_order_quantity',true);
    $note     = get_post_meta($order_id,'_itc_order_note',true);
    $date     = date_i18n(get_option('date_format').' '.get_option('time_format'),strtotime($order->post_date));
    echo '<h2>'.sprintf(esc_html__('Détails de la commande #%d','immersi-travel-card'),$order_id).'</h2>';
    echo '<table class="form-table"><tr><th>'.esc_html__('Revendeur','immersi-travel-card').'</th><td>'.esc_html($login).'</td></tr>';
    echo '<tr><th>'.esc_html__('Email','immersi-travel-card').'</th><td>'.esc_html($email).'</td></tr>';
    echo '<tr><th>'.esc_html__('Adresse','immersi-travel-card').'</th><td>'.esc_html($address).'</td></tr>';
    echo '<tr><th>'.esc_html__('Téléphone','immersi-travel-card').'</th><td>'.esc_html($phone).'</td></tr>';
    echo '<tr><th>'.esc_html__('Quantité','immersi-travel-card').'</th><td>'.esc_html($quantity).'</td></tr>';
    echo '<tr><th>'.esc_html__('Commentaire','immersi-travel-card').'</th><td>'.nl2br(esc_html($note)).'</td></tr>';
    echo '<tr><th>'.esc_html__('Date','immersi-travel-card').'</th><td>'.esc_html($date).'</td></tr></table>';
    echo '<h3>'.esc_html__('Cartes générées','immersi-travel-card').'</h3>';
    $cards=new WP_Query(['post_type'=>'itc_card','post_status'=>'publish','meta_query'=>[['key'=>'_itc_order_id','value'=>$order_id]],'posts_per_page'=>-1]);
    if($cards->have_posts()){echo '<table class="widefat fixed"><thead><tr><th>'.esc_html__('Numéro de carte','immersi-travel-card').'</th><th>'.esc_html__('QR Code','immersi-travel-card').'</th></tr></thead><tbody>';
        while($cards->have_posts()){ $cards->the_post(); $cid=get_the_ID(); $num=get_post_meta($cid,'_itc_card_number',true); $qr=esc_url(get_post_meta($cid,'_itc_qr_url',true));
            echo '<tr><td>'.esc_html($num).'</td><td><img src="'.$qr.'" width="50"/></td></tr>'; }
        echo '</tbody></table>'; wp_reset_postdata();
    } else {
        echo '<p>'.esc_html__('Aucune carte générée.','immersi-travel-card').'</p>';
    }
}