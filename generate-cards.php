<?php
// includes/generate-cards.php

if ( ! function_exists( 'itc_generate_cards' ) ) {
    /**
     * Génère un lot de cartes ITC.
     *
     * @param int $quantity Nombre de cartes à générer.
     */
    function itc_generate_cards( $quantity ) {
        // Vérifie que la quantité est un nombre valide
        if ( ! is_numeric( $quantity ) || $quantity <= 0 ) {
            echo "<p>La quantité doit être un nombre positif.</p>";
            return;
        }

        for ( $i = 0; $i < $quantity; $i++ ) {
            // Générer un numéro de carte unique
            $card_number = 'ITC-' . str_pad( mt_rand( 1, 999999 ), 6, '0', STR_PAD_LEFT );

            // Créer le post carte
            $post_data = array(
                'post_title'  => $card_number,
                'post_type'   => 'itc_card',
                'post_status' => 'publish',
            );
            $post_id = wp_insert_post( $post_data );

            if ( $post_id && ! is_wp_error( $post_id ) ) {
                // Sauvegarde des métadonnées obligatoires
                update_post_meta( $post_id, '_itc_card_number', $card_number );
                update_post_meta( $post_id, '_itc_status', 'non active' );

                // Génération du QR code après insertion pour disposer de l'ID
                $dispatch_url = home_url( "/carte/{$card_number}/" );
                $qr_code_url  = itc_generate_qr_code( $post_id, $dispatch_url );
                update_post_meta( $post_id, '_itc_qr_code_url', $qr_code_url );

                echo "<p>Carte {$card_number} générée avec succès !</p>";
            } else {
                echo "<p>Une erreur est survenue lors de la génération de la carte {$card_number}.</p>";
            }
        }

        echo "<p>{$quantity} carte(s) ont été générées avec succès.</p>";
    }
}

