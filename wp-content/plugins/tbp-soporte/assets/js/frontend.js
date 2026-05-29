/**
 * Frontend JavaScript for TBP Soporte
 */
jQuery(document).ready(function($) {

    // Scroll chat box to bottom on load
    var $chatBox = $('.tbp-soporte-chat-box');
    if ($chatBox.length > 0) {
        $chatBox.scrollTop($chatBox[0].scrollHeight);
    }

    // Modal Control
    var $modal = $('#tbp-soporte-modal');
    
    // Trigger from Order Details Button
    $(document).on('click', '.tbp-soporte-open-modal-btn', function(e) {
        e.preventDefault();
        var orderId = $(this).data('order-id');
        openSupportModal(orderId);
    });

    // Trigger from My Orders List Actions
    $(document).on('click', 'a[href^="#solicitar-soporte-"]', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        var orderId = href.replace('#solicitar-soporte-', '');
        openSupportModal(orderId);
    });

    // Close Modal
    $(document).on('click', '.tbp-soporte-modal-close, .tbp-soporte-modal-cancel', function() {
        closeSupportModal();
    });

    // Close on click outside modal content
    $modal.on('click', function(e) {
        if ($(e.target).hasClass('tbp-soporte-modal-overlay')) {
            closeSupportModal();
        }
    });

    function openSupportModal(orderId) {
        $('#tbp_order_id').val(orderId);
        $modal.fadeIn(200);
        $('body').css('overflow', 'hidden'); // Prevent background scroll
    }

    function closeSupportModal() {
        $modal.fadeOut(200);
        $('body').css('overflow', '');
        $('#tbp-soporte-create-form')[0].reset();
        $('#tbp-file-selected-name').text('');
    }

    // File input selection display
    $('#tbp_file').on('change', function() {
        var filename = $(this).val().split('\\').pop();
        $('#tbp-file-selected-name').text(filename);
    });

    $('#reply_file').on('change', function() {
        var filename = $(this).val().split('\\').pop();
        $('#reply_file_name').text(filename);
    });

    // Submit Create Ticket Form via AJAX
    $('#tbp-soporte-create-form').on('submit', function(e) {
        e.preventDefault();
        
        var $submitBtn = $('#tbp-soporte-submit-btn');
        $submitBtn.prop('disabled', true).text('Enviando...');

        var formData = new FormData(this);
        formData.append('action', 'tbp_soporte_create_ticket');
        formData.append('nonce', tbp_soporte_ajax.nonce);

        $.ajax({
            url: tbp_soporte_ajax.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.href = response.data.redirect_url;
                } else {
                    alert(response.data.message || 'Ocurrió un error inesperado.');
                    $submitBtn.prop('disabled', false).text('Enviar Ticket');
                }
            },
            error: function() {
                alert('Error de conexión con el servidor. Inténtalo de nuevo.');
                $submitBtn.prop('disabled', false).text('Enviar Ticket');
            }
        });
    });

    // Submit Reply Form via AJAX
    $('#tbp-soporte-reply-form').on('submit', function(e) {
        e.preventDefault();

        var $submitBtn = $('#submit-reply-btn');
        $submitBtn.prop('disabled', true).text('Enviando...');

        var formData = new FormData(this);
        formData.append('action', 'tbp_soporte_submit_reply');
        formData.append('nonce', tbp_soporte_ajax.nonce);

        $.ajax({
            url: tbp_soporte_ajax.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    // Append message html to chat box
                    $chatBox.append(response.data.html);
                    $chatBox.scrollTop($chatBox[0].scrollHeight);
                    
                    // Reset form
                    $('#tbp-soporte-reply-form')[0].reset();
                    $('#reply_file_name').text('');
                } else {
                    alert(response.data.message || 'Error al enviar la respuesta.');
                }
                $submitBtn.prop('disabled', false).text('Enviar Mensaje');
            },
            error: function() {
                alert('Error de red al enviar la respuesta.');
                $submitBtn.prop('disabled', false).text('Enviar Mensaje');
            }
        });
    });

});

/**
 * Order Lookup Frontend Logic
 */
jQuery(document).ready(function($) {
    var searchTimeout;
    var $searchInput = $('#tbp-order-lookup-input');
    var $resultsContainer = $('#tbp-lookup-results-container');
    var $spinner = $('.tbp-lookup-spinner');
    var $modal = $('#tbp-order-modal');
    var $modalBody = $('#tbp-modal-body');
    var $modalTitle = $('#tbp-modal-title');

    // Make sure we have the ajax url from the localized script
    // Note: tbp_soporte_admin_ajax is localized in the shortcode for staff
    var ajaxUrl = (typeof tbp_soporte_admin_ajax !== 'undefined') ? tbp_soporte_admin_ajax.ajax_url : (typeof tbp_soporte_ajax !== 'undefined' ? tbp_soporte_ajax.ajax_url : '');
    var ajaxNonce = (typeof tbp_soporte_admin_ajax !== 'undefined') ? tbp_soporte_admin_ajax.nonce : (typeof tbp_soporte_ajax !== 'undefined' ? tbp_soporte_ajax.nonce : '');

    if ($searchInput.length > 0 && ajaxUrl) {
        $searchInput.on('input', function() {
            var term = $(this).val().trim();
            
            clearTimeout(searchTimeout);
            
            if (term.length < 3) {
                $resultsContainer.hide().empty();
                $spinner.hide();
                return;
            }

            $spinner.show();
            
            searchTimeout = setTimeout(function() {
                $.ajax({
                    url: ajaxUrl,
                    type: 'POST',
                    data: {
                        action: 'tbp_soporte_search_orders',
                        nonce: ajaxNonce,
                        term: term
                    },
                    success: function(response) {
                        $spinner.hide();
                        $resultsContainer.empty().show();
                        
                        if (response.success && response.data.results.length > 0) {
                            var html = '';
                            $.each(response.data.results, function(i, order) {
                                html += '<div class="tbp-lookup-result-item" data-id="' + order.id + '">';
                                html += '<strong>Pedido #' + order.id + '</strong> - ' + order.name + '<br>';
                                html += '<small>' + order.email + ' | ' + order.status + ' | ' + order.date + ' | ' + order.total + '</small>';
                                html += '</div>';
                            });
                            $resultsContainer.html(html);
                        } else {
                            $resultsContainer.html('<div class="tbp-lookup-result-item" style="cursor:default;">No se encontraron pedidos.</div>');
                        }
                    },
                    error: function() {
                        $spinner.hide();
                    }
                });
            }, 500); // 500ms delay
        });

        // Click on a result to open modal
        $(document).on('click', '.tbp-lookup-result-item', function() {
            var orderId = $(this).data('id');
            if (!orderId) return;

            $resultsContainer.hide();
            $searchInput.val('');
            
            $modalTitle.text('Cargando Pedido #' + orderId + '...');
            $modalBody.html('<div style="text-align:center; padding: 40px; color: #64748b;">⏳ Recuperando datos del pedido...</div>');
            $modal.fadeIn(300);
            $('body').css('overflow', 'hidden');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'tbp_soporte_get_order_details',
                    nonce: ajaxNonce,
                    order_id: orderId
                },
                success: function(response) {
                    if (response.success) {
                        var d = response.data.details;
                        $modalTitle.text('Detalles del Pedido #' + d.id);
                        
                        var html = '<div class="tbp-order-modal-grid">';
                        
                        // Main info
                        html += '<div class="tbp-order-modal-card">';
                        html += '<h4>Resumen</h4>';
                        html += '<p><strong>Estado:</strong> ' + d.status + '</p>';
                        html += '<p><strong>Fecha:</strong> ' + d.date + '</p>';
                        html += '<p><strong>Total:</strong> ' + d.total + '</p>';
                        html += '</div>';

                        // Customer info
                        html += '<div class="tbp-order-modal-card">';
                        html += '<h4>Cliente</h4>';
                        html += '<p><strong>Nombre:</strong> ' + d.customer.name + '</p>';
                        html += '<p><strong>Email:</strong> ' + d.customer.email + '</p>';
                        
                        // Buttons (WhatsApp & Ticket)
                        html += '<div style="margin-top: 15px; display:flex; gap: 10px; flex-wrap:wrap;">';
                        if (d.customer.phone) {
                            var waPhone = d.customer.phone.replace(/[^0-9]/g, '');
                            var waText = encodeURIComponent('Hola estimado ' + d.customer.name + ' somos de The Best Prom organizadores del evento, nos ponemos en contacto contigo para saludarte y verificar unos datos, )');
                            html += '<a href="https://wa.me/' + waPhone + '?text=' + waText + '" target="_blank" style="display:inline-block; background:#25D366; color:#fff; padding:8px 15px; border-radius:8px; font-size:13px; font-weight:600; text-decoration:none; text-align:center;">💬 Enviar WhatsApp</a>';
                        }
                        html += '<button type="button" id="tbp-modal-open-ticket-btn" style="background:#f5a623; color:#fff; padding:8px 15px; border-radius:8px; font-size:13px; font-weight:600; border:none; cursor:pointer;">🚨 Generar Ticket</button>';
                        html += '</div>';
                        
                        html += '</div>'; // End Customer Info card
                        html += '</div>'; // End grid

                        // Staff Ticket Form (Hidden by default)
                        html += '<div id="tbp-modal-ticket-form-container" style="display:none; margin-top:15px; padding:20px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">';
                        html += '<h4 style="margin:0 0 15px 0; color:#0f172a; border-bottom:1px solid #e2e8f0; padding-bottom:5px;">Levantar Incidencia</h4>';
                        html += '<form id="tbp-modal-ticket-form" enctype="multipart/form-data">';
                        html += '<input type="hidden" name="staff_order_id" value="' + d.id + '" />';
                        html += '<input type="hidden" name="staff_nonce" value="' + (typeof tbp_soporte_admin_ajax !== 'undefined' ? tbp_soporte_admin_ajax.staff_nonce : '') + '" />';
                        
                        html += '<div style="margin-bottom:12px;">';
                        html += '<label style="display:block; margin-bottom:5px; font-weight:600; font-size:12px; text-transform:uppercase;">Tipo de Incidencia *</label>';
                        html += '<select name="staff_incidence_type" required style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; background:#fff;">';
                        html += '<option value="">-- Selecciona una opción --</option>';
                        html += '<option value="Falta de producto / entregable">Falta de producto / entregable</option>';
                        html += '<option value="Cambio de talla / modelo">Cambio de talla / modelo</option>';
                        html += '<option value="Producto dañado / defectuoso">Producto dañado / defectuoso</option>';
                        html += '<option value="Problema con el pago">Problema con el pago</option>';
                        html += '<option value="Otro (Especificar)">Otro (Especificar)</option>';
                        html += '</select>';
                        html += '</div>';

                        html += '<div style="margin-bottom:12px;">';
                        html += '<label style="display:block; margin-bottom:5px; font-weight:600; font-size:12px; text-transform:uppercase;">Comentarios *</label>';
                        html += '<textarea name="staff_comments" required rows="3" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; background:#fff;"></textarea>';
                        html += '</div>';

                        html += '<div style="margin-bottom:15px;">';
                        html += '<label style="display:block; margin-bottom:5px; font-weight:600; font-size:12px; text-transform:uppercase;">Evidencia (Opcional)</label>';
                        html += '<input type="file" name="staff_ticket_file" accept=".jpg,.jpeg,.png,.pdf" style="width:100%; font-size:12px;" />';
                        html += '</div>';

                        html += '<button type="submit" id="tbp-modal-ticket-submit-btn" style="width:100%; background:#f5a623; color:#fff; padding:12px; border:none; border-radius:6px; font-weight:bold; cursor:pointer;">Crear Ticket y Notificar al Cliente</button>';
                        html += '<div id="tbp-modal-ticket-msg" style="display:none; margin-top:10px; padding:10px; border-radius:4px; font-size:13px;"></div>';
                        
                        html += '</form>';
                        html += '</div>';

                        // Items
                        html += '<div class="tbp-order-modal-card" style="margin-top:15px;">';
                        html += '<h4>Artículos Comprados</h4>';
                        html += '<ul style="margin:0; padding-left:20px; font-size: 13px;">';
                        $.each(d.items, function(i, item) {
                            html += '<li>' + item + '</li>';
                        });
                        html += '</ul>';
                        html += '</div>';

                        // TBP Metrics
                        if (d.tbp_metrics && d.tbp_metrics.length > 0) {
                            html += '<div class="tbp-order-modal-card" style="margin-top:15px; background: #fffbeb; border-color: #fde68a;">';
                            html += '<h4>Métricas de Entregas TBP</h4>';
                            html += '<p style="margin:0;">' + d.tbp_metrics.join('<br>') + '</p>';
                            html += '</div>';
                        }

                        // Attendees
                        if (d.attendees_html) {
                            html += d.attendees_html;
                        }

                        // Notes
                        if (d.notes_html) {
                            html += d.notes_html;
                        }

                        $modalBody.html(html);
                    } else {
                        $modalBody.html('<div class="woocommerce-error">' + (response.data.message || 'Error desconocido') + '</div>');
                    }
                },
                error: function() {
                    $modalBody.html('<div class="woocommerce-error">Error de red. Intenta de nuevo.</div>');
                }
            });
        });

        // Close Modal
        $(document).on('click', '.tbp-order-modal-close, .tbp-order-modal-backdrop', function() {
            $modal.fadeOut(300);
            $('body').css('overflow', '');
        });

        // Toggle Ticket Form in Modal
        $(document).on('click', '#tbp-modal-open-ticket-btn', function() {
            $('#tbp-modal-ticket-form-container').slideToggle(200);
            $('#tbp-modal-ticket-form')[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        // Submit Ticket Form in Modal
        $(document).on('submit', '#tbp-modal-ticket-form', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $btn = $('#tbp-modal-ticket-submit-btn');
            var $msg = $('#tbp-modal-ticket-msg');
            
            $btn.prop('disabled', true).text('Procesando...');
            $msg.hide().removeClass('woocommerce-error woocommerce-message');

            var formData = new FormData($form[0]);
            formData.append('action', 'tbp_soporte_staff_create_ticket');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        $msg.css({background: '#dcfce7', color: '#166534', border: '1px solid #bbf7d0'}).html('✅ ' + response.data.message).show();
                        $form[0].reset();
                        setTimeout(function() {
                            $('#tbp-modal-ticket-form-container').slideUp();
                        }, 3000);
                    } else {
                        $msg.css({background: '#fee2e2', color: '#991b1b', border: '1px solid #fecaca'}).html('❌ ' + response.data.message).show();
                    }
                    $btn.prop('disabled', false).text('Crear Ticket y Notificar al Cliente');
                },
                error: function() {
                    $msg.css({background: '#fee2e2', color: '#991b1b', border: '1px solid #fecaca'}).html('❌ Error de red. Inténtalo de nuevo.').show();
                    $btn.prop('disabled', false).text('Crear Ticket y Notificar al Cliente');
                }
            });
        });
    }
});
