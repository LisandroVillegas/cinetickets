// Solo presentación: el servidor calcula el importe final y valida el aforo.
export function totalEnCentavos(precio, cantidad) {
    if (!/^\d{1,8}(\.\d{1,2})?$/.test(precio) || !/^[1-9]\d*$/.test(cantidad)) return null;
    const [entero, decimal = ''] = precio.split('.');
    const centavos = Number(entero) * 100 + Number(decimal.padEnd(2, '0'));
    const total = centavos * Number(cantidad);
    return centavos > 0 && Number.isSafeInteger(total) && total <= 9999999999 ? total : null;
}

export function formatearCOP(centavos) {
    const parteEntera = Math.floor(centavos / 100).toLocaleString('es-CO');
    return `COP ${parteEntera},${String(centavos % 100).padStart(2, '0')}`;
}

export function initVenta(root = document) {
    const form = root.querySelector('[data-venta]');
    if (!form || form.dataset.inicializado) return;
    form.dataset.inicializado = 'true';
    const funcion = form.elements.id_funcion;
    const cantidad = form.elements.cantidad_entradas;
    const nombre = form.elements.nombre_cliente;
    const email = form.elements.email_cliente;
    const total = form.querySelector('[data-total]');
    const resumen = form.querySelector('[data-resumen]');
    const boton = form.querySelector('[type="submit"]');
    const estado = form.querySelector('[data-envio]');
    const avisoInicial = estado.textContent;
    let enviando = false;
    function actualizar() {
        const opcion = funcion.selectedOptions[0];
        const centavos = totalEnCentavos(opcion?.dataset.precio ?? '', cantidad.value);
        total.textContent = centavos === null ? 'Indica una cantidad válida dentro del límite de importe.' : formatearCOP(centavos);
        cantidad.setCustomValidity('');
        if (funcion.value && cantidad.value && cantidad.validity.valid && centavos === null) {
            cantidad.setCustomValidity('La cantidad supera el límite de importe permitido.');
        }
        resumen.textContent = funcion.value
            ? `${opcion.textContent.trim()} — ${cantidad.value || '0'} entrada(s). Cliente: ${nombre.value || 'Sin indicar'}. Correo: ${email.value || 'Sin indicar'}.`
            : 'Selecciona una función para revisar la compra.';
    }
    form.addEventListener('input', actualizar);
    form.addEventListener('change', actualizar);
    form.addEventListener('submit', (event) => {
        if (enviando) { event.preventDefault(); return; }
        actualizar();
        if (!form.reportValidity()) { event.preventDefault(); return; }
        if (!window.confirm(`Revisa tu compra:\n${resumen.textContent}\nTotal estimado: ${total.textContent}\n¿Confirmar compra?`)) {
            event.preventDefault(); return;
        }
        enviando = true;
        boton.disabled = true;
        boton.textContent = 'Procesando…';
        form.setAttribute('aria-busy', 'true');
        estado.textContent = 'Procesando la compra. Si se interrumpe la conexión, revisa los boletos antes de volver a enviar.';
    });
    window.addEventListener('pageshow', () => {
        enviando = false;
        boton.disabled = funcion.disabled;
        boton.textContent = 'Confirmar compra';
        form.removeAttribute('aria-busy');
        estado.textContent = avisoInicial;
        actualizar();
    });
    actualizar();
}
