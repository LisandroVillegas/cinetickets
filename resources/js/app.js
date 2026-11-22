import './bootstrap';
import { initProgramacion } from './programacion';
import { initVenta } from './venta';

initProgramacion();
initVenta();
document.querySelectorAll('form[data-confirmar]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (!window.confirm(form.dataset.confirmar)) event.preventDefault();
    });
});
document.getElementById('resumen-errores')?.focus();
