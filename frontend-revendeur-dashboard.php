<?php
// includes/frontend-revendeur-dashboard.php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_user = wp_get_current_user();

// Déterminer quel onglet doit être actif (uniquement pour la partie commandes)
$active_tab = 'itc-tab-activation';
if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
    if ( isset( $_POST['itc_order_cards_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['itc_order_cards_nonce'] ) ), 'itc_order_cards' ) ) {
        $active_tab = 'itc-tab-order';
    }
}
?>

<div class="itc-dashboard">
    <ul class="itc-tabs" style="list-style:none;padding:0;display:flex;border-bottom:2px solid #ddd;">
        <li class="<?php echo 'itc-tab-activation' === $active_tab ? 'itc-tab-active' : ''; ?>" data-tab="itc-tab-activation" style="margin-right:1em;padding:0.5em;cursor:pointer;<?php echo 'itc-tab-activation' === $active_tab ? 'border-bottom:2px solid #0073aa;color:#000;' : 'color:#0073aa;'; ?>">
            <?php esc_html_e( 'Activation de carte', 'immersi-travel-card' ); ?>
        </li>
        <li class="<?php echo 'itc-tab-order' === $active_tab ? 'itc-tab-active' : ''; ?>" data-tab="itc-tab-order" style="margin-right:1em;padding:0.5em;cursor:pointer;<?php echo 'itc-tab-order' === $active_tab ? 'border-bottom:2px solid #0073aa;color:#000;' : 'color:#0073aa;'; ?>">
            <?php esc_html_e( 'Commander des cartes', 'immersi-travel-card' ); ?>
        </li>
        <li class="<?php echo 'itc-tab-history' === $active_tab ? 'itc-tab-active' : ''; ?>" data-tab="itc-tab-history" style="padding:0.5em;cursor:pointer;<?php echo 'itc-tab-history' === $active_tab ? 'border-bottom:2px solid #0073aa;color:#000;' : 'color:#0073aa;'; ?>">
            <?php esc_html_e( 'Historique', 'immersi-travel-card' ); ?>
        </li>
    </ul>

    <!-- Onglet Activation -->
    <div id="itc-tab-activation" class="itc-tab-content" style="<?php echo 'itc-tab-activation' === $active_tab ? 'display:block;margin-top:1em;' : 'display:none;margin-top:1em;'; ?>">
        <form id="itc_activation_form" method="post">
            <?php wp_nonce_field( 'itc_activation_nonce', 'security' ); ?>

            <div style="background-color:#fff4e6;border:2px solid #d54e21;border-radius:4px;padding:1em;margin-bottom:1.5em;">
                <label for="revendeur_prenom" style="color:#d54e21;font-weight:bold;">
                    <?php esc_html_e( 'Prénom du revendeur', 'immersi-travel-card' ); ?>
                </label><br>
                <input type="text" name="revendeur_prenom" id="revendeur_prenom" required placeholder="<?php esc_attr_e( 'Ex : Mehdi', 'immersi-travel-card' ); ?>" style="width:100%;padding:0.5em;border:2px solid #d54e21;background-color:#fff4e6;" />
            </div>

            <p>
                <label for="itc_activation_offer"><?php esc_html_e( 'Offre', 'immersi-travel-card' ); ?></label><br>
                <select name="itc_activation_offer" id="itc_activation_offer" required style="width:100%;padding:0.5em;border:1px solid #ccc;">
                    <option value="solo"><?php esc_html_e( 'Solo — 1 carte', 'immersi-travel-card' ); ?></option>
                    <option value="duo"><?php esc_html_e( 'Duo — 2 cartes', 'immersi-travel-card' ); ?></option>
                    <option value="trio"><?php esc_html_e( 'Trio — 3 cartes', 'immersi-travel-card' ); ?></option>
                    <option value="quatuor"><?php esc_html_e( 'Quatuor — 4 cartes', 'immersi-travel-card' ); ?></option>
                    <option value="groupe"><?php esc_html_e( 'Groupe — 5 cartes et plus', 'immersi-travel-card' ); ?></option>
                </select>
            </p>

            <div id="itc_code_fields"></div>

            <p>
                <button type="submit" class="button button-primary" style="padding:0.5em 1em;">
                    <?php esc_html_e( 'Activer les cartes', 'immersi-travel-card' ); ?>
                </button>
            </p>
        </form>
    </div>

    <!-- Onglet Commande -->
    <div id="itc-tab-order" class="itc-tab-content" style="<?php echo 'itc-tab-order' === $active_tab ? 'display:block;margin-top:1em;' : 'display:none;margin-top:1em;'; ?>">
        <?php include __DIR__ . '/frontend-revendeur-order-form.php'; ?>
    </div>

    <!-- Onglet Historique -->
    <div id="itc-tab-history" class="itc-tab-content" style="<?php echo 'itc-tab-history' === $active_tab ? 'display:block;margin-top:1em;' : 'display:none;margin-top:1em;'; ?>">
        <ul class="itc-history-tabs" style="list-style:none;display:flex;border-bottom:2px solid #ddd;margin-bottom:20px;padding:0;">
            <li class="active" data-tab="orders" style="margin-right:1em;padding:0.5em;cursor:pointer;border-bottom:2px solid #0073aa;color:#000;">
                <?php esc_html_e( 'Historique des commandes', 'immersi-travel-card' ); ?>
            </li>
            <li data-tab="activations" style="padding:0.5em;cursor:pointer;color:#0073aa;">
                <?php esc_html_e( 'Historique des activations', 'immersi-travel-card' ); ?>
            </li>
        </ul>
        <div class="itc-history-content">
            <div id="itc-history-orders" class="itc-history-tab-content active">
                <table id="itc-orders-table" class="display" style="width:100%;">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="itc-select-all-orders"></th>
                            <th><?php esc_html_e( 'ID commande', 'immersi-travel-card' ); ?></th>
                            <th><?php esc_html_e( 'Date', 'immersi-travel-card' ); ?></th>
                            <th><?php esc_html_e( 'Quantité', 'immersi-travel-card' ); ?></th>
                            <th><?php esc_html_e( 'Note', 'immersi-travel-card' ); ?></th>
                            <th><?php esc_html_e( 'Statut', 'immersi-travel-card' ); ?></th>
                            <th><?php esc_html_e( 'Actions', 'immersi-travel-card' ); ?></th>
                        </tr>
                    </thead>
                </table>
                <button id="itc-print-selected-orders" class="button" style="margin-top:10px;">
                    <?php esc_html_e( 'Imprimer les commandes sélectionnées', 'immersi-travel-card' ); ?>
                </button>
            </div>
            <div id="itc-history-activations" class="itc-history-tab-content" style="display:none; background:#f9f9f9; padding:1em;">
                <table id="itc-activations-table" class="display" style="width:100%;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Numéro de carte', 'immersi-travel-card' ); ?></th>
                            <th><?php esc_html_e( 'Date d’activation', 'immersi-travel-card' ); ?></th>
                            <th><?php esc_html_e( 'Date d’expiration', 'immersi-travel-card' ); ?></th>
                            <th><?php esc_html_e( 'Statut', 'immersi-travel-card' ); ?></th>
                            <th><?php esc_html_e( 'Prénom du revendeur', 'immersi-travel-card' ); ?></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(function($) {
    // Onglets principaux
    $('.itc-tabs li[data-tab]').on('click', function() {
        var target = $(this).data('tab');
        $('.itc-tabs li[data-tab]').removeClass('itc-tab-active').css({ 'border-bottom': 'none', 'color': '#0073aa' });
        $(this).addClass('itc-tab-active').css({ 'border-bottom': '2px solid #0073aa', 'color': '#000' });
        $('.itc-tab-content').hide();
        $('#' + target).show();
    });

    // Onglets secondaires
    $('.itc-history-tabs li').on('click', function() {
        var tab = $(this).data('tab');
        $('.itc-history-tabs li').removeClass('active').css({ 'border-bottom': 'none', 'color': '#0073aa' });
        $(this).addClass('active').css({ 'border-bottom': '2px solid #0073aa', 'color': '#000' });
        $('.itc-history-tab-content').hide();
        $('#itc-history-' + tab).show();
        if (tab === 'orders' && window.ordersTable) window.ordersTable.columns.adjust();
        if (tab === 'activations' && window.activationsTable) window.activationsTable.columns.adjust();
    });
})(jQuery);
</script>
