<?php
// includes/custom-columns.php
// Colonnes personnalisées pour le CPT "itc_card" avec checkbox de sélection

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Déclare les colonnes personnalisées et rend certaines triables.
 */
function itc_add_custom_columns( $columns ) {
    $new = array(
        'cb'                  => $columns['cb'],
        'itc_card_number'     => __( 'Numéro de Carte',   'immersi-travel-card' ),
        'itc_status'          => __( 'Statut',            'immersi-travel-card' ),
        'itc_qr_code'         => __( 'QR Code',           'immersi-travel-card' ),
        'itc_revendeur'       => __( 'Revendeur',         'immersi-travel-card' ),
        'itc_activation_date' => __( 'Date d’Activation', 'immersi-travel-card' ),
    );

    if ( isset( $columns['date'] ) ) {
        $new['date'] = $columns['date'];
    }

    return $new;
}
add_filter( 'manage_itc_card_posts_columns', 'itc_add_custom_columns' );

/**
 * Rend nos colonnes personnalisées triables.
 */
function itc_sortable_columns( $columns ) {
    $columns['itc_status']          = 'itc_status';
    $columns['itc_revendeur']       = 'itc_revendeur';
    $columns['itc_activation_date'] = 'itc_activation_date';
    return $columns;
}
add_filter( 'manage_edit-itc_card_sortable_columns', 'itc_sortable_columns' );

/**
 * Remplit les colonnes avec les bonnes valeurs.
 */
function itc_custom_columns( $column, $post_id ) {
    switch ( $column ) {
        case 'itc_card_number':
            echo esc_html( get_the_title( $post_id ) );
            break;

        case 'itc_status':
            $status = get_post_meta( $post_id, '_itc_status', true );
            $labels = array(
                'non_active' => __( 'Non activée', 'immersi-travel-card' ),
                'active'     => __( 'Active',       'immersi-travel-card' ),
                'expired'    => __( 'Expirée',      'immersi-travel-card' ),
            );
            $color = ( 'active' === $status ) ? 'green' : ( 'expired' === $status ? 'red' : 'orange' );
            echo '<span style="color:' . esc_attr( $color ) . ';">'
               . esc_html( $labels[ $status ] ?? $status )
               . '</span>';
            break;

        case 'itc_qr_code':
            $url = get_post_meta( $post_id, '_itc_qr_url', true );
            if ( $url ) {
                echo '<img src="' . esc_url( $url ) . '" width="50" alt="" />';
            }
            break;

        case 'itc_revendeur':
            // 1) méta dédiée
            $rev_id = get_post_meta( $post_id, '_itc_revendeur', true );
            if ( ! $rev_id ) {
                // 2) ancienne clé éventuelle
                $rev_id = get_post_meta( $post_id, '_itc_card_revendeur', true );
            }
            if ( $rev_id ) {
                $user = get_userdata( intval( $rev_id ) );
                if ( $user ) {
                    // Affiche le login WP au lieu du display_name
                    echo esc_html( $user->user_login );
                    break;
                }
            }
            // 3) fallback sur l'auteur du post (au cas où)
            $author_id = get_post_field( 'post_author', $post_id );
            $author    = get_userdata( $author_id );
            echo $author ? esc_html( $author->user_login ) : '—';
            break;

        case 'itc_activation_date':
            $date = get_post_meta( $post_id, '_itc_activation_date', true );
            if ( $date ) {
                $ts = strtotime( $date );
                echo $ts ? esc_html( date_i18n( get_option( 'date_format' ), $ts ) ) : esc_html( $date );
            } else {
                echo '—';
            }
            break;
    }
}
add_action( 'manage_itc_card_posts_custom_column', 'itc_custom_columns', 10, 2 );

/**
 * Gère le tri des colonnes basées sur meta_query.
 */
function itc_column_orderby( $query ) {
    if ( ! is_admin() || $query->get('post_type') !== 'itc_card' ) {
        return;
    }

    $orderby = $query->get( 'orderby' );
    if ( $orderby === 'itc_status' ) {
        $query->set( 'meta_key', '_itc_status' );
        $query->set( 'orderby', 'meta_value' );
    }
    if ( $orderby === 'itc_revendeur' ) {
        $query->set( 'meta_key', '_itc_revendeur' );
        $query->set( 'orderby', 'meta_value' );
    }
    if ( $orderby === 'itc_activation_date' ) {
        $query->set( 'meta_key', '_itc_activation_date' );
        $query->set( 'orderby', 'meta_value' );
        $query->set( 'order', 'DESC' );
    }
}
add_action( 'pre_get_posts', 'itc_column_orderby' );
