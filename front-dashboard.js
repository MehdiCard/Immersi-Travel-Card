// assets/js/front-dashboard.js
jQuery(function($){
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

  // Génération des champs d'activation selon l'offre
  $('#itc_activation_offer').on('change', function() {
    var mapping   = { solo:1, duo:2, trio:3, quatuor:4, groupe:5 };
    var count     = mapping[$(this).val()] || 1;
    var container = $('#itc_code_fields').empty();

    for (var i = 0; i < count; i++) {
      var idx = i + 1;
      container.append(
        '<fieldset class="itc-activation-block">' +
          '<legend>Activation ' + idx + '</legend>' +
          '<p><label>Numéro de carte</label><br>' +
          '<input type="text" name="itc_card_codes[]" required style="width:100%;padding:0.5em;border:1px solid #ccc;" /></p>' +
          '<p><label>Prénom du client</label><br>' +
          '<input type="text" name="itc_client_firstname[]" required style="width:100%;padding:0.5em;border:1px solid #ccc;" /></p>' +
          '<p><label>Nom du client</label><br>' +
          '<input type="text" name="itc_client_lastname[]" required style="width:100%;padding:0.5em;border:1px solid #ccc;" /></p>' +
          '<p><label>E-mail du client</label><br>' +
          '<input type="email" name="itc_client_email[]" required style="width:100%;padding:0.5em;border:1px solid #ccc;" /></p>' +
          '<p><label>Téléphone du client</label><br>' +
          '<input type="tel" name="itc_client_tel[]" style="width:100%;padding:0.5em;border:1px solid #ccc;" /></p>' +
          '<p><label>Pays du client</label><br>' +
          '<input type="text" name="itc_client_country[]" style="width:100%;padding:0.5em;border:1px solid #ccc;" /></p>' +
        '</fieldset>'
      );
    }
  }).trigger('change');

  // Soumission du formulaire en AJAX
  $('#itc_activation_form').on('submit', function(e) {
    e.preventDefault();
    var data = $(this).serialize() + '&action=itc_handle_activation';
    $.post(itc_ajax_object.ajax_url, data)
      .done(function(response) {
        if (response.success) {
          alert(response.data.message);
          if (window.activationsTable) window.activationsTable.ajax.reload();
        } else {
          alert('Erreur AJAX d’activation : ' + JSON.stringify(response));
        }
      })
      .fail(function(jqXHR) {
        alert('Erreur AJAX d’activation : ' + jqXHR.responseText);
      });
  });

  // Initialisation DataTable : Commandes
  if ($.fn.DataTable.isDataTable('#itc-orders-table')) {
    $('#itc-orders-table').DataTable().clear().destroy();
    $('#itc-orders-table').empty();
  }
  window.ordersTable = $('#itc-orders-table').DataTable({
    processing: true,
    serverSide: false,
    ajax: {
      url: itc_ajax_object.ajax_url,
      type: 'POST',
      data: function(d) {
        return $.extend({}, d, {
          action: 'itc_get_order_history',
          nonce:  itc_ajax_object.history_nonce
        });
      },
      dataSrc: 'data',
      error: function(xhr) {
        console.error('Order history AJAX error:', xhr.responseText);
        alert('Erreur AJAX commandes : voir la console pour la réponse.');
      }
    },
    columns: [
      { data: 'select',    orderable: false, searchable: false, defaultContent: '' },
      { data: null,        title: 'N° Commande', render: function(data, type, row) { return row.order_id || row.ID || row.id || '-'; } },
      { data: 'date',      title: 'Date',      defaultContent: '' },
      { data: 'quantity',  title: 'Quantité',  defaultContent: '' },
      { data: 'note',      title: 'Note',      defaultContent: '' },
      { data: 'status',    title: 'Statut',    defaultContent: '' },
      { data: 'actions',   orderable: false, searchable: false, defaultContent: '' }
    ],
    order: [[1, 'desc']],
    responsive: true
  });

  // Sélection en masse des commandes
  $('#itc-select-all-orders').on('click', function() {
    var rows = ordersTable.rows({ search: 'applied' }).nodes();
    $('input.itc-order-select', rows).prop('checked', this.checked);
  });
  $('#itc-orders-table tbody').on('change', 'input.itc-order-select', function() {
    var el = $('#itc-select-all-orders').get(0);
    if (el && el.checked && !this.checked) el.indeterminate = true;
  });

  // Initialisation DataTable : Activations
  if ($.fn.DataTable.isDataTable('#itc-activations-table')) {
    $('#itc-activations-table').DataTable().clear().destroy();
    $('#itc-activations-table').empty();
  }
  window.activationsTable = $('#itc-activations-table').DataTable({
    processing: true,
    serverSide: false,
    ajax: {
      url: itc_ajax_object.ajax_url,
      type: 'POST',
      data: function(d) {
        return $.extend({}, d, {
          action: 'itc_get_activation_history',
          nonce:  itc_ajax_object.history_nonce
        });
      },
      dataSrc: 'data',
      error: function(xhr) {
        console.error('Activation history AJAX error:', xhr.responseText);
        alert('Erreur AJAX activations : voir la console pour la réponse.');
      }
    },
    columns: [
      { data: 'card_number',      title: 'N° Carte' },
      { data: 'activation_date',  title: 'Date d’activation' },
      { data: 'expiration_date',  title: 'Date d’expiration' },
      { data: 'status',           title: 'Statut' },
      { data: 'revendeur_prenom', title: 'Prénom du revendeur' }
    ],
    order: [[1, 'desc']],
    responsive: true
  });
});
