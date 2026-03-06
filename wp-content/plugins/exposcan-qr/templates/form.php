<div class="exposcan-form-container">
    <form id="exposcan-registro-form" class="exposcan-form">
        <div class="exposcan-form-row">
            <div class="exposcan-form-group">
                <label for="empresa"><?php _e('Nombre de la empresa', 'exposcan-qr'); ?></label>
<input type="text" name="empresa" id="empresa">
            </div>
        </div>
        
        <div class="exposcan-form-row">
            <div class="exposcan-form-group">
                <label for="nombre"><?php _e('Nombre', 'exposcan-qr'); ?> <span class="required">*</span></label>
                <input type="text" name="nombre" id="nombre" required>
            </div>
            
            <div class="exposcan-form-group">
                <label for="apellido"><?php _e('Apellido', 'exposcan-qr'); ?> <span class="required">*</span></label>
                <input type="text" name="apellido" id="apellido" required>
            </div>
        </div>
        
        <div class="exposcan-form-row">
           <div class="exposcan-form-group">
    <label for="telefono"><?php _e('Teléfono móvil', 'exposcan-qr'); ?> <span class="required">*</span></label>
    <div class="phone-input-container">
        <select name="codigo_pais" id="codigo_pais" required>
            <option value="+52">México (+52)</option>
            <option value="+1">USA (+1)</option>
        </select>
        <input type="tel" name="telefono_numero" id="telefono_numero" required placeholder="Número sin código">
        <input type="hidden" name="telefono" id="telefono">
    </div>
</div>
            
            <div class="exposcan-form-group">
                <label for="email"><?php _e('E-mail', 'exposcan-qr'); ?> <span class="required">*</span></label>
                <input type="email" name="email" id="email" required>
            </div>
        </div>
        
        <div class="exposcan-form-row">
    <div class="exposcan-form-group">
        <label for="web"><?php _e('Página Web', 'exposcan-qr'); ?></label>
        <div class="url-input-container">
            <span class="url-prefix">https://</span>
            <input type="text" name="web" id="web" placeholder="www.ejemplo.com">
        </div>
    </div>
</div>
        
        <div class="exposcan-form-row">
            <div class="exposcan-form-group">
                <label for="requerimientos"><?php _e('Requerimientos', 'exposcan-qr'); ?></label>
                <textarea name="requerimientos" id="requerimientos" rows="4"></textarea>
            </div>
        </div>
		
		<div class="exposcan-form-row">
    <input type="hidden" name="exposcan_form_token" value="<?php echo esc_attr($form_token); ?>">
    <?php wp_nonce_field('exposcan_qr_nonce', 'exposcan_nonce'); ?>
    <button type="submit" class="exposcan-submit-btn"><?php _e('Registrarse', 'exposcan-qr'); ?></button>
</div>
        
    </form>
    
    <div id="exposcan-form-result" style="display: none;">
        <div class="exposcan-success-message">
            <h3><?php _e('¡Registro exitoso!', 'exposcan-qr'); ?></h3>
            <p><?php _e('Tu código QR está listo:', 'exposcan-qr'); ?></p>
            <div class="exposcan-qr-container">
                <img id="exposcan-qr-image" src="" alt="QR Code">
                <div id="exposcan-qr-name"></div>
            </div>
            <div class="exposcan-qr-actions">
                <a id="exposcan-print-qr" href="#" class="exposcan-btn" target="_blank">
                    <?php _e('Imprimir QR', 'exposcan-qr'); ?>
                </a>
                <button id="exposcan-new-registration" class="exposcan-btn exposcan-btn-secondary">
                    <?php _e('Nuevo registro', 'exposcan-qr'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

