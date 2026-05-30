
    jQuery(document).ready(function($) {
        let currentQty = 0;

        $('.btn-move-order').on('click', function(e) {
            e.preventDefault();
            const orderId = $(this).data('order');
            currentQty = parseInt($(this).data('qty'));
            const currentMesa = $(this).data('mesa-actual');

            $('#move_order_id').val(orderId);
            
            // Ocultar mesas que no tienen capacidad para este pedido
            $('#move_mesa_id option').each(function() {
                if (!$(this).val()) return; // skip default
                if ($(this).val() == currentMesa) {
                    $(this).hide(); // No mover a la misma mesa
                    return;
                }
                const libre = parseInt($(this).data('libre'));
                if (libre < currentQty) {
                    $(this).prop('disabled', true).css('color', '#ccc');
                } else {
                    $(this).prop('disabled', false).css('color', '').show();
                }
            });

            $('#move_mesa_id').val('');
            $('#tbp-move-modal').css('display', 'flex');
        });

        $('#btn_cancel_move').on('click', function() {
            $('#tbp-move-modal').hide();
        });

        $('#btn_confirm_move').on('click', function() {
            const newMesaId = $('#move_mesa_id').val();
            const orderId = $('#move_order_id').val();

            if (!newMesaId) {
                alert('Selecciona una mesa destino válida.');
                return;
            }

            $(this).prop('disabled', true).text('Moviendo...');

            $.post(ajaxurl, {
                action: 'tbp_asientos_move_order',
                config_id: 0,
                order_id: orderId,
                new_mesa_id: newMesaId,
                nonce: '"dummy_nonce"'
            }, function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (response.data.message || response.data));
                    $('#btn_confirm_move').prop('disabled', false).text('Confirmar Movimiento');
                }
            });
        });
    });
    