/**
 * Admin JavaScript for TBP Soporte (Kanban & Details Panel)
 */
jQuery(document).ready(function($) {

    var $panel = $('#tbp-soporte-slide-panel');
    var $overlay = $('#tbp-soporte-panel-overlay');
    var currentOpenTicketId = null;

    // --- SLIDE-OUT PANEL CONTROLS ---

    // Open panel on card click
    $(document).on('click', '.card-btn-view-ticket, .tbp-soporte-kanban-card', function(e) {
        // Prevent click if we clicked on the view order button inside card
        if ($(e.target).closest('.card-btn-view-order').length > 0) {
            return;
        }
        
        var ticketId = $(this).data('id');
        openTicketPanel(ticketId);
    });

    // Close panel
    $(document).on('click', '.tbp-soporte-panel-close-btn, #tbp-soporte-panel-overlay', function() {
        closeTicketPanel();
    });

    function openTicketPanel(ticketId) {
        currentOpenTicketId = ticketId;
        var data = tbp_soporte_tickets_data[ticketId];
        
        if (!data) return;

        // Populate basic details
        $('#panel-ticket-title').text('Ticket #' + ticketId);
        $('#panel-ticket-id').html('<strong>ID:</strong> #' + ticketId);
        
        // Find category and author from card markup
        var $card = $('#card-' + ticketId);
        var category = $card.find('.card-category').text();
        var author = $card.find('.card-author').text();
        var orderText = $card.find('.card-order').html() || '';
        
        // Select the correct category in the dropdown
        $('#panel-ticket-category-select option').filter(function() {
            return $(this).data('name') === category || $(this).text() === category;
        }).prop('selected', true);
        
        $('#panel-ticket-author').html(author + (orderText ? ' | ' + orderText : ''));
        
        // Description & Attachments
        $('#panel-ticket-description').html(data.description);
        
        // Attachments
        var attHtml = '';
        if (data.attachments && data.attachments.length > 0) {
            attHtml += '<div class="tbp-soporte-message-attachments"><strong>Adjuntos de creación:</strong><ul>';
            data.attachments.forEach(function(att) {
                attHtml += '<li><a href="' + att.url + '" target="_blank">' + att.name + '</a></li>';
            });
            attHtml += '</ul></div>';
        }
        $('#panel-ticket-attachments').html(attHtml);

        // Order Summary
        var $orderSummaryContainer = $('#panel-ticket-order-summary');
        if (data.order_summary) {
            var orderHtml = '<strong style="color:#0073aa; display:block; margin-bottom:5px;">📦 Resumen del Pedido #' + data.order_id + ' (' + data.order_summary.status + ')</strong>';
            
            // Items with metadata
            orderHtml += '<div style="margin-bottom: 10px;"><strong>Artículos:</strong><br>' + data.order_summary.items.join('') + '</div>';
            
            // TBP Metrics
            if (data.order_summary.tbp_metrics && data.order_summary.tbp_metrics.length > 0) {
                orderHtml += '<div style="margin-bottom: 10px; background: #fff; padding: 8px; border: 1px dashed #cbd5e1; border-radius: 4px;">';
                orderHtml += data.order_summary.tbp_metrics.join('<br>');
                orderHtml += '</div>';
            }
            
            // Attendees Data (TBP Core)
            if (data.order_summary.attendees_html) {
                orderHtml += data.order_summary.attendees_html;
            }
            
            orderHtml += '<div style="display:flex; justify-content: space-between; margin-bottom: 5px;">';
            orderHtml += '<span><strong>Total:</strong><br>' + data.order_summary.total + '</span></div>';
            orderHtml += '<hr style="border:0; border-top:1px solid #e2e8f0; margin: 8px 0;">';
            orderHtml += '<div><strong>Datos de Contacto/Envío:</strong><br>' + (data.order_summary.billing_email || 'Sin email') + '<br>' + (data.order_summary.billing_phone || 'Sin teléfono') + '<br>' + (data.order_summary.shipping_addr || 'Sin dirección') + '</div>';
            
            $orderSummaryContainer.html(orderHtml).show();
        } else {
            $orderSummaryContainer.hide();
        }

        // Thread Conversation
        renderPanelChatThread(data.comments);

        // Solved button control (hide if already solved)
        var status = $card.closest('.tbp-soporte-kanban-column').data('status');
        if (status === 'solucionado') {
            $('#panel-solved-btn').hide();
            $('#tbp-soporte-admin-reply-form').hide();
        } else {
            $('#panel-solved-btn').show();
            $('#tbp-soporte-admin-reply-form').show();
        }

        $('#panel_ticket_id_input').val(ticketId);
        $overlay.fadeIn(200);
        $panel.addClass('open');
    }

    function closeTicketPanel() {
        $panel.removeClass('open');
        $overlay.fadeOut(200);
        currentOpenTicketId = null;
        $('#tbp-soporte-admin-reply-form')[0].reset();
    }

    function renderPanelChatThread(comments) {
        var $thread = $('#panel-chat-thread');
        $thread.empty();

        if (!comments || comments.length === 0) {
            $thread.html('<div class="tbp-soporte-empty-col-message">No hay conversación previa en este ticket.</div>');
            return;
        }

        comments.forEach(function(comment) {
            var msgClass = '';
            var label = '';
            
            if (comment.is_internal) {
                msgClass = 'tbp-soporte-message-internal-note';
                label = 'Nota Interna';
            } else if (comment.author.indexOf('Soporte') !== -1 || comment.author === 'Admin' || comment.author === 'Administrator') {
                msgClass = 'tbp-soporte-message-staff';
                label = 'Soporte';
            } else {
                msgClass = 'tbp-soporte-message-client';
                label = 'Cliente';
            }

            var attHtml = '';
            if (comment.attachment_url) {
                attHtml = '<div class="tbp-soporte-message-attachments">' +
                          '<strong>Adjunto:</strong> <a href="' + comment.attachment_url + '" target="_blank">' + comment.attachment_name + '</a>' +
                          '</div>';
            }

            var html = '<div class="tbp-soporte-message ' + msgClass + '">' +
                       '<div class="tbp-soporte-message-author">' + (comment.is_internal ? '📝 ' : '') + comment.author + ' (' + label + ')</div>' +
                       '<div class="tbp-soporte-message-content">' + comment.content + attHtml + '</div>' +
                       '<div class="tbp-soporte-message-time">' + comment.time + '</div>' +
                       '</div>';
            
            $thread.append(html);
        });

        // Scroll to bottom
        $thread.scrollTop($thread[0].scrollHeight);
    }

    // Submit Reply / Note via AJAX
    $('#tbp-soporte-admin-reply-form').on('submit', function(e) {
        e.preventDefault();

        var ticketId = $('#panel_ticket_id_input').val();
        var content = $('#panel_reply_content').val();
        var isInternal = $('#panel_is_internal').is(':checked');
        var $submitBtn = $('#panel-send-btn');

        if (!ticketId || !content) return;

        $submitBtn.prop('disabled', true).text('Enviando...');

        var action = isInternal ? 'tbp_soporte_add_internal_note' : 'tbp_soporte_submit_reply';
        var nonce = tbp_soporte_admin_ajax.nonce;

        $.ajax({
            url: tbp_soporte_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: action,
                nonce: nonce,
                ticket_id: ticketId,
                note_content: content,      // used by add_internal_note
                comment_content: content     // used by submit_reply
            },
            success: function(response) {
                if (response.success) {
                    // Update global state data
                    var userDisplayName = 'Soporte'; // fallback
                    var newComment = {
                        author: userDisplayName,
                        content: '<p>' + content + '</p>',
                        time: 'Hoy',
                        is_internal: isInternal,
                        attachment_url: '',
                        attachment_name: ''
                    };
                    
                    tbp_soporte_tickets_data[ticketId].comments.push(newComment);
                    
                    // Re-render chat
                    renderPanelChatThread(tbp_soporte_tickets_data[ticketId].comments);
                    
                    // Reset text area
                    $('#panel_reply_content').val('');
                    $('#panel_is_internal').prop('checked', false);

                    // If it was a public message, update column state visual representation
                    if (!isInternal) {
                        moveCardVisually(ticketId, 'esperando_cliente');
                    }
                } else {
                    alert(response.data.message || 'Error al enviar.');
                }
                $submitBtn.prop('disabled', false).text('Enviar');
            },
            error: function() {
                alert('Error de red al enviar la respuesta.');
                $submitBtn.prop('disabled', false).text('Enviar');
            }
        });
    });

    // Mark Solved inside panel
    $('#panel-solved-btn').on('click', function(e) {
        e.preventDefault();
        if (!currentOpenTicketId) return;

        var $btn = $(this);
        $btn.prop('disabled', true).text('Procesando...');

        $.ajax({
            url: tbp_soporte_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tbp_soporte_change_status',
                nonce: tbp_soporte_admin_ajax.nonce,
                ticket_id: currentOpenTicketId,
                status: 'solucionado'
            },
            success: function(response) {
                if (response.success) {
                    moveCardVisually(currentOpenTicketId, 'solucionado');
                    closeTicketPanel();
                } else {
                    alert(response.data.message || 'Error al cambiar estado.');
                }
                $btn.prop('disabled', false).text('Marcar Solucionado');
            },
            error: function() {
                alert('Error de conexión.');
                $btn.prop('disabled', false).text('Marcar Solucionado');
            }
        });
    });

    // Change category from select
    $('#panel-ticket-category-select').on('change', function() {
        if (!currentOpenTicketId) return;
        
        var categoryId = $(this).val();
        var categoryName = $(this).find('option:selected').text();
        var $select = $(this);
        
        $select.prop('disabled', true);
        
        $.ajax({
            url: tbp_soporte_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tbp_soporte_change_category',
                nonce: tbp_soporte_admin_ajax.nonce,
                ticket_id: currentOpenTicketId,
                category_id: categoryId
            },
            success: function(response) {
                if (response.success) {
                    // Update visual category on card
                    var $card = $('#card-' + currentOpenTicketId);
                    $card.find('.card-category').text(categoryName);
                    
                    // Add an internal note to the conversation to track category change
                    var timeStr = new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    var noteHtml = '<div class="tbp-soporte-message tbp-soporte-message-internal-note" style="background:#eef2ff; border-left: 4px solid #6366f1;">' +
                                   '<div class="tbp-soporte-message-author">📝 Sistema (Categoría Actualizada)</div>' +
                                   '<div class="tbp-soporte-message-content">Categoría cambiada a: <strong>' + categoryName + '</strong></div>' +
                                   '<div class="tbp-soporte-message-time">Hoy ' + timeStr + '</div>' +
                                   '</div>';
                    $('#panel-chat-thread').append(noteHtml);
                    $('#panel-chat-thread').scrollTop($('#panel-chat-thread')[0].scrollHeight);
                    
                    // If filter is active, we might need to re-apply it
                    if (typeof applyFilters === 'function') {
                        applyFilters();
                    }
                } else {
                    alert(response.data.message || 'Error al cambiar categoría.');
                }
                $select.prop('disabled', false);
            },
            error: function() {
                alert('Error de conexión.');
                $select.prop('disabled', false);
            }
        });
    });

    // Visually move card to a different column
    function moveCardVisually(ticketId, newStatus) {
        var $card = $('#card-' + ticketId);
        var $targetColumn = $('[data-status="' + newStatus + '"]');
        var $targetCardsContainer = $targetColumn.find('.tbp-soporte-column-cards');

        // Remove empty message if any
        $targetCardsContainer.find('.tbp-soporte-empty-col-message').remove();

        // Move the card
        $card.appendTo($targetCardsContainer);

        // Recalculate counters
        recalculateColumnCounters();
    }

    function recalculateColumnCounters() {
        // We defer to the new filtered counter to maintain consistency if a filter is active
        if (typeof recalculateColumnCountersFiltered === 'function') {
            recalculateColumnCountersFiltered();
            return;
        }

        $('.tbp-soporte-kanban-column').each(function() {
            var $col = $(this);
            var cardCount = $col.find('.tbp-soporte-kanban-card').length;
            $col.find('.count').text(cardCount);
            
            var $cardsContainer = $col.find('.tbp-soporte-column-cards');
            if (cardCount === 0 && $cardsContainer.find('.tbp-soporte-empty-col-message').length === 0) {
                $cardsContainer.html('<div class="tbp-soporte-empty-col-message">No hay tickets</div>');
            }
        });
    }

    // --- DRAG AND DROP KANBAN MECHANICALS ---

    var cards = document.querySelectorAll('.tbp-soporte-kanban-card');
    var columns = document.querySelectorAll('.tbp-soporte-kanban-column');
    var draggedCard = null;

    cards.forEach(function(card) {
        card.addEventListener('dragstart', dragStart);
        card.addEventListener('dragend', dragEnd);
    });

    columns.forEach(function(column) {
        column.addEventListener('dragover', dragOver);
        column.addEventListener('dragenter', dragEnter);
        column.addEventListener('dragleave', dragLeave);
        column.addEventListener('drop', dragDrop);
    });

    function dragStart() {
        draggedCard = this;
        $(this).addClass('dragging');
    }

    function dragEnd() {
        $(this).removeClass('dragging');
        draggedCard = null;
    }

    function dragOver(e) {
        e.preventDefault();
    }

    function dragEnter(e) {
        e.preventDefault();
        $(this).addClass('drag-over');
    }

    function dragLeave() {
        $(this).removeClass('drag-over');
    }

    function dragDrop() {
        $(this).removeClass('drag-over');
        
        if (!draggedCard) return;

        var ticketId = $(draggedCard).data('id');
        var newStatus = $(this).data('status');
        var $col = $(this);
        var $cardsContainer = $col.find('.tbp-soporte-column-cards');

        // Check if card is dropped in the same column
        var oldStatus = $(draggedCard).closest('.tbp-soporte-kanban-column').data('status');
        if (oldStatus === newStatus) return;

        // Perform AJAX save
        $.ajax({
            url: tbp_soporte_admin_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'tbp_soporte_change_status',
                nonce: tbp_soporte_admin_ajax.nonce,
                ticket_id: ticketId,
                status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    // Visual Drop movement
                    $cardsContainer.find('.tbp-soporte-empty-col-message').remove();
                    $cardsContainer.append(draggedCard);
                    recalculateColumnCounters();
                } else {
                    alert(response.data.message || 'Error al actualizar el estado del ticket.');
                }
            },
            error: function() {
                alert('Error al conectar con el servidor.');
            }
        });
    }

    // --- KANBAN FILTERING LOGIC ---
    var $searchInput = $('#tbp-kanban-search');
    var $categorySelect = $('#tbp-kanban-category');
    var $clearBtn = $('#tbp-kanban-clear-filters');

    function applyFilters() {
        var searchTerm = $searchInput.val().toLowerCase().trim();
        var categoryFilter = $categorySelect.val();
        
        var isFiltering = searchTerm !== '' || categoryFilter !== '';
        
        if (isFiltering) {
            $clearBtn.show();
        } else {
            $clearBtn.hide();
        }

        $('.tbp-soporte-kanban-card').each(function() {
            var $card = $(this);
            var isMatch = true;

            // Category Check
            if (categoryFilter !== '') {
                var cardCategory = $card.find('.card-category').text().trim();
                // Decode HTML entities if needed or exact match
                if (cardCategory !== categoryFilter) {
                    isMatch = false;
                }
            }

            // Text Search Check (ID, Title, Customer, Order)
            if (isMatch && searchTerm !== '') {
                var cardText = $card.text().toLowerCase();
                // We add the ticket ID explicitly since it has a # symbol
                var cardId = $card.data('id').toString();
                if (cardText.indexOf(searchTerm) === -1 && cardId.indexOf(searchTerm) === -1) {
                    isMatch = false;
                }
            }

            // Toggle visibility
            if (isMatch) {
                $card.show();
                $card.removeClass('filtered-out');
            } else {
                $card.hide();
                $card.addClass('filtered-out');
            }
        });

        recalculateColumnCountersFiltered();
    }

    function recalculateColumnCountersFiltered() {
        $('.tbp-soporte-kanban-column').each(function() {
            var $col = $(this);
            var $cardsContainer = $col.find('.tbp-soporte-column-cards');
            
            // Count only visible cards
            var visibleCount = $cardsContainer.find('.tbp-soporte-kanban-card:not(.filtered-out)').length;
            $col.find('.count').text(visibleCount);
            
            // Handle empty state
            $cardsContainer.find('.tbp-soporte-empty-col-message').remove();
            if (visibleCount === 0) {
                var totalCount = $cardsContainer.find('.tbp-soporte-kanban-card').length;
                if (totalCount === 0) {
                    $cardsContainer.append('<div class="tbp-soporte-empty-col-message">No hay tickets</div>');
                } else {
                    $cardsContainer.append('<div class="tbp-soporte-empty-col-message" style="color:#888; font-style:italic;">No hay coincidencias para tu búsqueda</div>');
                }
            }
        });
    }

    $searchInput.on('keyup', applyFilters);
    $categorySelect.on('change', applyFilters);

    $clearBtn.on('click', function() {
        $searchInput.val('');
        $categorySelect.val('');
        applyFilters();
    });



});
