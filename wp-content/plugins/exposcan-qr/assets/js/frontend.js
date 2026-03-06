/**
 * ExpoScan QR - JavaScript para el frontend
 */
(function($) {
    'use strict';
    
    // Función para validar el formulario
    function validateForm(form) {
        // Validar campos requeridos
        let valid = true;
        
        form.find('[required]').each(function() {
            const field = $(this);
            const value = field.val().trim();
            
            if (value === '') {
                // Marcar campo como inválido
                field.addClass('exposcan-invalid');
                valid = false;
            } else {
                field.removeClass('exposcan-invalid');
            }
        });
        
        // Validar email
        const emailField = form.find('input[type="email"]');
        if (emailField.length && emailField.val().trim() !== '') {
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(emailField.val().trim())) {
                emailField.addClass('exposcan-invalid');
                valid = false;
            }
        }
        
     // Validar teléfono (solo el número sin código)
const phoneField = form.find('#telefono_numero');
if (phoneField.length && phoneField.val().trim() !== '') {
    const phonePattern = /^[\d\s()-]{8,}$/;
    if (!phonePattern.test(phoneField.val().trim())) {
        phoneField.addClass('exposcan-invalid');
        valid = false;
    }
}
        
        return valid;
    }
    
    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        const form = $('#exposcan-registro-form');
        
        // Validar en tiempo real
        form.find('input, textarea').on('input', function() {
            $(this).removeClass('exposcan-invalid');
        });
        
        // Variable para controlar envíos múltiples
let isSubmitting = false;

// Manejar envío del formulario
form.on('submit', function(e) {
    e.preventDefault();
	
	// Combinar código de país y número de teléfono
const codigoPais = $('#codigo_pais').val();
const telefonoNumero = $('#telefono_numero').val().trim();
$('#telefono').val(codigoPais + telefonoNumero);
    
    // Prevenir envíos múltiples
    if (isSubmitting) {
        return false;
    }
    
    // Validar formulario
    if (!validateForm(form)) {
        // Mostrar alerta
        alert(exposcanQR.messages.validation || 'Por favor, completa correctamente todos los campos requeridos.');
        return false;
    }
    
    // Marcar como enviando y deshabilitar botón
    isSubmitting = true;
    form.find('button[type="submit"]').prop('disabled', true);
    
    // Mostrar cargando
    form.addClass('loading');
            
            // Recopilar datos
            const formData = new FormData(this);
            formData.append('action', 'exposcan_submit_form');
            formData.append('nonce', exposcanQR.nonce);
            
            // Enviar AJAX
            $.ajax({
                url: exposcanQR.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    // Ocultar cargando y resetear estado de envío
form.removeClass('loading');
isSubmitting = false;
form.find('button[type="submit"]').prop('disabled', false);
                    
                    if (response.success) {
                        // Mostrar QR
                        $('#exposcan-qr-image').attr('src', response.data.qr_url);
                        $('#exposcan-qr-name').text($('#nombre').val() + ' ' + $('#apellido').val());
                        $('#exposcan-print-qr').attr('href', response.data.print_url);
                        
                        // Mostrar mensaje de éxito
                        form.hide();
                        $('#exposcan-form-result').show();
                        
                        // Scroll al resultado
                        $('html, body').animate({
                            scrollTop: $('#exposcan-form-result').offset().top - 50
                        }, 500);
                    } else {
                        // Mostrar error
                        alert(response.data || exposcanQR.messages.error);
                    }
                },
                error: function() {
                    // Ocultar cargando y resetear estado de envío
form.removeClass('loading');
isSubmitting = false;
form.find('button[type="submit"]').prop('disabled', false);
                    
                    // Mostrar error
                    alert(exposcanQR.messages.error);
                }
            });
        });
        
       // Botón para nuevo registro
$('#exposcan-new-registration').on('click', function() {
    // Solicitar nuevo token
    $.ajax({
        url: exposcanQR.ajaxUrl,
        type: 'POST',
        data: {
            action: 'exposcan_generate_token',
            nonce: exposcanQR.nonce
        },
        success: function(response) {
            if (response.success) {
                // Actualizar token en el formulario
                $('input[name="exposcan_form_token"]').val(response.data.token);
                
                // Limpiar formulario
                form[0].reset();
                
                // Mostrar formulario y ocultar resultado
                $('#exposcan-form-result').hide();
                form.show();
                
                // Scroll al formulario
                $('html, body').animate({
                    scrollTop: form.offset().top - 50
                }, 500);
            } else {
                alert('Error al generar nuevo token. Por favor recarga la página.');
            }
        },
        error: function() {
            alert('Error al generar nuevo token. Por favor recarga la página.');
        }
    });
});
    });
    
})(jQuery);