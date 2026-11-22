export function initProgramacion(root = document) {
    const form = root.querySelector('[data-programacion]');
    if (!form || form.dataset.inicializado) return;
    form.dataset.inicializado = 'true';
    for (const [id, dato, sufijo] of [['pelicula_id', 'duracion', 'min'], ['sala_id', 'capacidad', 'cupos']]) {
        const select = form.querySelector(`#${id}`);
        const output = form.querySelector(`span[data-${dato}]`);
        const actualizar = () => {
            const valor = select.selectedOptions[0]?.dataset[dato];
            output.textContent = valor ? `Selección actual: ${valor} ${sufijo}.` : '';
        };
        select.addEventListener('change', actualizar);
        actualizar();
    }
}
