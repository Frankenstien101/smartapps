/**
 * Show a Bootstrap toast notification.
 * @param {string} message - The message to display.
 * @param {string} type - Bootstrap color class: success, danger, warning, info, primary, secondary.
 */
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    // Check if there's already a toast with the same message to avoid duplicates
    const existing = container.querySelector('.toast-body');
    if (existing && existing.textContent === message) {
        return;
    }

    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.role = 'alert';
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    container.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
    bsToast.show();

    // Remove toast after it hides
    toast.addEventListener('hidden.bs.toast', function () {
        toast.remove();
    });
}