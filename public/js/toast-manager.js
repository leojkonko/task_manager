/**
 * Sistema de Notificações Toast
 * Fornece feedback visual agradável para o usuário
 */
class ToastManager {
    constructor() {
        this.createToastContainer();
    }

    createToastContainer() {
        if (!document.getElementById('toast-container')) {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '9999';
            document.body.appendChild(container);
        }
    }

    show(message, type = 'info', title = '', duration = 5000) {
        const toastId = 'toast-' + Date.now();
        const iconClass = this.getIconClass(type);
        const bgClass = this.getBgClass(type);
        
        // Processar quebras de linha em mensagens longas
        const formattedMessage = message.replace(/\n\n/g, '<br><br>').replace(/\n/g, '<br>');
        
        const toastHtml = `
            <div id="${toastId}" class="toast align-items-center text-white ${bgClass} border-0" role="alert" aria-live="assertive" aria-atomic="true" style="max-width: 500px;">
                <div class="d-flex">
                    <div class="toast-body d-flex align-items-start">
                        <i class="${iconClass} me-2 mt-1"></i>
                        <div style="line-height: 1.4;">
                            ${title ? `<div class="fw-bold mb-1">${title}</div>` : ''}
                            <div>${formattedMessage}</div>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `;

        const container = document.getElementById('toast-container');
        container.insertAdjacentHTML('beforeend', toastHtml);
        
        const toastElement = document.getElementById(toastId);
        
        // Ajustar duração baseada no comprimento da mensagem
        const adjustedDuration = Math.max(duration, Math.min(message.length * 50, 15000));
        
        const toast = new bootstrap.Toast(toastElement, {
            delay: adjustedDuration,
            autohide: true
        });
        
        toast.show();
        
        // Remove o elemento do DOM após ser ocultado
        toastElement.addEventListener('hidden.bs.toast', () => {
            toastElement.remove();
        });
    }

    success(message, title = 'Sucesso!') {
        this.show(message, 'success', title);
    }

    error(message, title = 'Atenção!') {
        this.show(message, 'error', title, 8000); // Errors stay longer
    }

    warning(message, title = 'Aviso!') {
        this.show(message, 'warning', title, 6000);
    }

    info(message, title = 'Informação') {
        this.show(message, 'info', title);
    }

    getIconClass(type) {
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-triangle',
            warning: 'fas fa-exclamation-circle',
            info: 'fas fa-info-circle'
        };
        return icons[type] || icons.info;
    }

    getBgClass(type) {
        const classes = {
            success: 'bg-success',
            error: 'bg-danger',
            warning: 'bg-warning',
            info: 'bg-info'
        };
        return classes[type] || classes.info;
    }
}

// Inicializar o ToastManager quando o DOM estiver carregado
let toastManager;
document.addEventListener('DOMContentLoaded', function() {
    toastManager = new ToastManager();
    
    // Converter flash messages em toasts
    convertFlashMessagesToToasts();
    
    // Interceptar submissions de formulários para mostrar feedback
    enhanceFormSubmissions();
});

/**
 * Converte flash messages tradicionais em toasts modernos
 */
function convertFlashMessagesToToasts() {
    // Procurar por alerts bootstrap e converter em toasts
    const alerts = document.querySelectorAll('.alert[data-auto-dismiss]');
    alerts.forEach(alert => {
        const message = alert.textContent.trim();
        const type = getTypeFromAlertClass(alert.className);
        
        // Mostrar toast
        toastManager.show(message, type);
        
        // Remover o alert original
        alert.remove();
    });
}

/**
 * Melhora submissions de formulários com feedback visual
 */
function enhanceFormSubmissions() {
    const forms = document.querySelectorAll('form[data-enhance-feedback]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Mostrar loading toast
            toastManager.info('⏳ Processando sua solicitação...', 'Aguarde');
            
            // Desabilitar botão de submit temporariamente
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                const originalText = submitBtn.textContent;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processando...';
                
                // Reabilitar após 3 segundos (fallback)
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }, 3000);
            }
        });
    });
}

/**
 * Extrai tipo de mensagem da classe CSS do alert
 */
function getTypeFromAlertClass(className) {
    if (className.includes('alert-success')) return 'success';
    if (className.includes('alert-danger')) return 'error';
    if (className.includes('alert-warning')) return 'warning';
    if (className.includes('alert-info')) return 'info';
    return 'info';
}

/**
 * Funções globais para fácil uso
 */
window.showSuccessToast = function(message, title) {
    if (toastManager) toastManager.success(message, title);
};

window.showErrorToast = function(message, title) {
    if (toastManager) toastManager.error(message, title);
};

window.showWarningToast = function(message, title) {
    if (toastManager) toastManager.warning(message, title);
};

window.showInfoToast = function(message, title) {
    if (toastManager) toastManager.info(message, title);
};