/** app.js - Comportamiento de la interfaz de GolApp. */

// Pide confirmación antes de acciones marcadas con data-confirm.
document.addEventListener('click', function (ev) {
    var el = ev.target.closest('[data-confirm]');
    if (el && !window.confirm(el.getAttribute('data-confirm'))) { ev.preventDefault(); }
});

// Validación de contraseña en el registro (mínimo 8, letras y números).
var formRegistro = document.getElementById('formRegistro');
if (formRegistro) {
    formRegistro.addEventListener('submit', function (ev) {
        var pass = document.getElementById('password').value;
        var conf = document.getElementById('password2').value;
        var msg = '';
        if (pass.length < 8 || !/[A-Za-z]/.test(pass) || !/[0-9]/.test(pass)) {
            msg = 'La contraseña debe tener mínimo 8 caracteres, con letras y números.';
        } else if (pass !== conf) {
            msg = 'Las contraseñas no coinciden.';
        }
        if (msg) { ev.preventDefault(); window.alert(msg); }
    });
}

// Total estimado en el formulario de reserva.
var selCancha = document.getElementById('id_cancha');
var selDuracion = document.getElementById('duracion');
var totalEl = document.getElementById('totalEstimado');
function actualizarTotal() {
    if (!selCancha || !selDuracion || !totalEl) { return; }
    var opt = selCancha.options[selCancha.selectedIndex];
    if (!opt) { totalEl.textContent = '-'; return; }
    var precio = parseFloat(opt.getAttribute('data-precio') || 0);
    totalEl.textContent = '$' + (precio * parseInt(selDuracion.value, 10)).toLocaleString('es-CO');
}
if (selCancha && selDuracion) {
    selCancha.addEventListener('change', actualizarTotal);
    selDuracion.addEventListener('change', actualizarTotal);
    actualizarTotal();
}
