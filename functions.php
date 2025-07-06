<?php
/**
 * functions.php
 * Enregistrement des Custom Post Types, shortcodes, menus admin,
 * inclusion des modules, endpoints front-end mot de passe,
 * gestion des accès offres, email activation, et handlers AJAX.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enregistrement des Custom Post Types
 */
function itc_register_cpt() {
    // CPT Cartes ITC
    $labels_card = [
        'name'               => __( 'Cartes ITC',       'immersi-travel-card' ),
        'singular_name'      => __( 'Carte ITC',        'immersi-travel-card' ),
        'all_items'          => __( 'Toutes les cartes','immersi-travel-card' ),
        'add_new'            => __( 'Ajouter',          'immersi-travel-card' ),
        'add_new_item'       => __( 'Ajouter une carte','immersi-travel-card' ),
        'edit_item'          => __( 'Modifier la carte','immersi-travel-card' ),
        'view_item'          => __( 'Voir la carte',    'immersi-travel-card' ),
        'search_items'       => __( 'Rechercher',       'immersi-travel-card' ),
        'not_found'          => __( 'Aucune trouvée',   'immersi-travel-card' ),
        'not_found_in_trash' => __( 'Aucune dans la corbeille','immersi-travel-card' ),
    ];
    register_post_type( 'itc_card', [
        'labels'       => $labels_card,
        'public'       => true,
        'show_ui'      => true,
        'show_in_menu' => false,
        'rewrite'      => [ 'slug' => 'itc_card' ],
        'supports'     => [ 'title' ],
        'has_archive'  => true,
        'show_in_rest' => true,
        'menu_icon'    => 'dashicons-id',
    ] );

    // CPT Commandes Revendeurs
    $labels_order = [
        'name'               => __( 'Commandes Revendeurs', 'immersi-travel-card' ),
        'singular_name'      => __( 'Commande Revendeur',  'immersi-travel-card' ),
        'add_new_item'       => __( 'Ajouter une commande', 'immersi-travel-card' ),
        'edit_item'          => __( 'Modifier la commande','immersi-travel-card' ),
        'view_item'          => __( 'Voir la commande',    'immersi-travel-card' ),
        'all_items'          => __( 'Toutes les commandes','immersi-travel-card' ),
        'search_items'       => __( 'Rechercher',           'immersi-travel-card' ),
        'not_found'          => __( 'Aucune trouvée',       'immersi-travel-card' ),
        'not_found_in_trash' => __( 'Aucune dans la corbeille','immersi-travel-card' ),
    ];
    register_post_type( 'itc_card_order', [
        'labels'       => $labels_order,
        'public'       => true,
        'show_ui'      => false,
        'show_in_menu' => false,
        'has_archive'  => false,
        'supports'     => [ 'title' ],
    ] );
}
add_action( 'init', 'itc_register_cpt' );

/**
 * Endpoints front-end pour mot de passe oublié et reset
 */
function itc_add_rewrite_endpoints() {
    add_rewrite_endpoint( 'mot-de-passe-oublie', EP_PAGES );
    add_rewrite_endpoint( 'nouveau-mot-de-passe', EP_PAGES );
}
add_action( 'init', 'itc_add_rewrite_endpoints' );

/**
 * Flush des règles de réécriture à l'activation/désactivation
 */
register_activation_hook( __FILE__, function() {
    itc_add_rewrite_endpoints();
    flush_rewrite_rules();
} );
register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
} );

/**
 * Inclusion des modules fonctionnels
 */
$itc_includes = [
    'includes/admin-bulk-handler.php',
    'includes/admin-filters.php',
    'includes/admin-card-metaboxes.php',
    'includes/custom-columns.php',
    'includes/admin-page.php',
    'includes/generate-cards.php',
    'includes/qr-generator.php',
    'includes/route-handler.php',
    'includes/client-login.php',
    'includes/client-area.php',
    'includes/client-lost-password.php',
    'includes/client-reset-password.php',
    'includes/revendeur-role.php',
    'includes/revendeur-shortcodes.php',
    'includes/card-functions.php',
    'includes/expiration-cron.php',
];
foreach ( $itc_includes as $file ) {
    $path = plugin_dir_path( __FILE__ ) . $file;
    if ( file_exists( $path ) ) {
        require_once $path;
    }
}

/**
 * Enregistrement des shortcodes
 */
function itc_register_shortcodes() {
    add_shortcode( 'itc_card_list',           'itc_shortcode_card_list' );
    add_shortcode( 'itc_revendeur_login',     'itc_shortcode_revendeur_login' );
    add_shortcode( 'itc_revendeur_dashboard', 'itc_shortcode_revendeur_dashboard' );
    add_shortcode( 'itc_client_login',        'itc_shortcode_client_login' );
    add_shortcode( 'itc_lost_password',       'itc_shortcode_lost_password' );
    add_shortcode( 'itc_reset_password',      'itc_shortcode_reset_password' );
}
add_action( 'init', 'itc_register_shortcodes' );

/**
 * Shortcode : liste des cartes
 */
function itc_shortcode_card_list( $atts ) {
    $query = new WP_Query([ 'post_type'=>'itc_card','posts_per_page'=>-1 ]);
    if ( ! $query->have_posts() ) {
        return '<p>'.esc_html__( 'Aucune carte.', 'immersi-travel-card').'</p>';
    }
    $out = '<ul class="itc-card-list">';
    while ( $query->have_posts() ) {
        $query->the_post();
        $url = get_post_meta( get_the_ID(), '_itc_qr_url', true );
        $out .= sprintf(
            '<li>%s – <img src="%s" width="50" alt="QR Code"/></li>',
            esc_html( get_the_title() ),
            esc_url( $url )
        );
    }
    wp_reset_postdata();
    return $out.'</ul>';
}

/**
 * Menus Admin
 */
function itc_register_admin_menus() {
    add_menu_page(
        __( 'Gestion Cartes', 'immersi-travel-card' ),
        __( 'Gestion Cartes', 'immersi-travel-card' ),
        'manage_options',
        'edit.php?post_type=itc_card',
        '',
        'dashicons-list-view',
        5
    );
    add_menu_page(
        __( 'Génération de Cartes', 'immersi-travel-card' ),
        __( 'Génération de Cartes', 'immersi-travel-card' ),
        'manage_options',
        'itc_generate_cards',
        'itc_generate_cards_page',
        'dashicons-plus-alt',
        6
    );
}
add_action( 'admin_menu', 'itc_register_admin_menus' );

/**
 * JS front-end : tableau de bord revendeur
 */
function itc_enqueue_front_dashboard_js() {
    if ( is_page( 'espace-revendeur' ) ) {
        wp_enqueue_script(
            'itc-front-dashboard',
            plugin_dir_url( __FILE__ ) . 'assets/js/front-dashboard.js',
            [ 'jquery' ],
            ITC_VERSION,
            true
        );
        wp_localize_script( 'itc-front-dashboard', 'itc_ajax_object', [
            'ajax_url'      => admin_url( 'admin-ajax.php' ),
            'history_nonce' => wp_create_nonce( 'itc_history_nonce' ),
        ] );
    }
}
add_action( 'wp_enqueue_scripts', 'itc_enqueue_front_dashboard_js' );

/**
 * Handlers AJAX pour DataTables
 */
add_action( 'wp_ajax_itc_get_order_history', function() {
    wp_send_json( [ 'data' => itc_get_order_history() ] );
} );
add_action( 'wp_ajax_itc_get_activation_history', function() {
    wp_send_json( [ 'data' => itc_get_activation_history() ] );
} );

/**
 * Email d'activation client
 */
function itc_send_activation_email( $user_id, $card_number, $plain_pass ) {
    $user      = get_userdata( $user_id );
    $email     = $user->user_email;
    $logo_url  = get_site_icon_url() ?: plugin_dir_url( __FILE__ ) . 'assets/images/logo.png';
    $subject   = 'Bienvenue dans l’univers ITC ! Votre carte est activée';
    $login_page= home_url( '/acces-client/' );
    $redirect  = home_url( '/offres/' );
    $login_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $login_page );

    $body  = '<html><body>';
    $body .= '<p><img src="'.esc_url($logo_url).'" style="max-width:150px;" alt="Logo"></p>';
    $body .= '<h2>Félicitations !</h2>';
    $body .= '<p>Votre carte <strong>'.esc_html($card_number).'</strong> est activée.</p>';
    $body .= '<p><strong>Identifiant :</strong> '.esc_html($card_number).'<br>';
    $body .= '<strong>Mot de passe :</strong> '.esc_html($plain_pass).'</p>';
    $body .= '<p><a href="'.esc_url($login_url).'">ACCÉDER À MES OFFRES</a></p>';
    $body .= '</body></html>';

    wp_mail( $email, $subject, $body, [ 'Content-Type: text/html; charset=UTF-8' ] );
}

/**
 * Filtre de l'URL de connexion vers page client
 */
add_filter( 'login_url', function( $url, $redirect, $force_reauth ) {
    $login_page = home_url( '/acces-client/' );
    if ( $redirect ) {
        $login_page = add_query_arg( 'redirect_to', urlencode( $redirect ), $login_page );
    }
    return $login_page;
}, 10, 3 );

/**
 * Vérifie si une carte est expirée ou inactive.
 */
function itc_card_expired( $card_id ) {
    $status = get_post_meta( $card_id, '_itc_status', true );
    if ( 'active' !== $status ) {
        return true;
    }
    $exp = get_post_meta( $card_id, '_itc_expiration_date', true );
    return ( $exp && strtotime( $exp ) < current_time( 'timestamp' ) );
}

/**
 * Redirection des actions perdu/reset vers front-end
 */
add_action( 'login_form_lostpassword', function() {
    wp_redirect( home_url( '/mot-de-passe-oublie/' ) ); exit;
});
add_action( 'login_form_rp', function() {
    $key   = sanitize_text_field( wp_unslash( $_REQUEST['key']   ?? '' ) );
    $login = sanitize_user(    wp_unslash( $_REQUEST['login'] ?? '' ), true );
    wp_redirect( add_query_arg( ['key'=>$key,'login'=>$login], home_url('/nouveau-mot-de-passe/') ) );
    exit;
});

/**
 * Protection de la page Offres : accès réservé aux porteurs
 */
add_action( 'template_redirect', 'itc_protect_offres_page' );
function itc_protect_offres_page() {
    if ( get_query_var( 'itc_dispatch' ) ) {
        return;
    }
    if ( is_page( 'offres' ) ) {
        if ( ! is_user_logged_in() ) {
            wp_redirect( home_url( '/acces-client/' ) ); exit;
        }
        $user_id = get_current_user_id();
        $card_id = get_user_meta( $user_id, '_itc_card_id', true );
        if ( ! $card_id || itc_card_expired( $card_id ) ) {
            wp_redirect( home_url( '/acces-client/?expired=1' ) ); exit;
        }
    }
}

/**
 * Récupération de l'historique des ordres revendeurs
 */
function itc_get_order_history() {
    $current = wp_get_current_user();
    $q = new WP_Query([
        'post_type'=>'itc_card_order',
        'posts_per_page'=>-1,
        'meta_query'=>[['key'=>'_itc_order_revendeur','value'=>$current->ID]],
    ]);
    $data=[];
    foreach($q->posts as $p){
        $id      = $p->ID;
        $qty     = intval(get_post_meta($id,'_itc_order_quantity',true));
        $note    = sanitize_text_field(get_post_meta($id,'_itc_order_note',true));
        $date    = get_the_date('Y-m-d H:i:s',$id);
        $exp     = get_post_meta($id,'_itc_exported',true);
        $recv    = get_post_meta($id,'_itc_order_received',true);
        $status  = $recv?__('Expédiée','immersi-travel-card'):($exp?__('Envoyée à l’imprimeur','immersi-travel-card'):__('En attente','immersi-travel-card'));
        $select  = '<input type="checkbox" class="itc-order-select" value="'.esc_attr($id).'">';
        $actions = '<a href="'.esc_url(get_edit_post_link($id)).'" target="_blank">'.esc_html__('Voir','immersi-travel-card').'</a>';
        $data[]=compact('select','order_id','date','quantity','note','status','actions');
    }
    wp_reset_postdata(); return $data;
}

/**
 * Récupération de l'historique des activations revendeurs
 */
function itc_get_activation_history() {
    global $wpdb;
    $uid   = get_current_user_id();
    $table = $wpdb->prefix.'itc_stock_log';
    $rows  = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE revendeur_id=%d ORDER BY date DESC",$uid));
    $data=[];
    foreach($rows as $r){
        $card_number     = get_post_meta($r->card_id,'_itc_card_number',true);
        $activation_date = $r->date;
        $expiration_date = get_post_meta($r->card_id,'_itc_expiration_date',true);
        $status          = get_post_meta($r->card_id,'_itc_status',true);
        $data[]=compact('card_number','activation_date','expiration_date','status');
    }
    return $data;
}
