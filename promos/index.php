<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Promociones · Uno A</title>
  <link rel="stylesheet" href="../assets/style.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <style>
    .hidden { display: none !important; }
    .promo-section { margin-bottom: 32px; }
    .promo-section h2 { font-size: 1rem; font-weight: 700; margin-bottom: 14px; color: var(--accent, #a3e635); border-bottom: 1px solid rgba(255,255,255,.08); padding-bottom: 8px; }
    .steps { list-style: none; counter-reset: paso; margin: 0 0 18px; padding: 0; }
    .steps li { counter-increment: paso; position: relative; padding: 7px 0 7px 34px; font-size: .85rem; opacity: .85; line-height: 1.45; }
    .steps li::before { content: counter(paso); position: absolute; left: 0; top: 6px; width: 22px; height: 22px; border-radius: 50%; background: rgba(163,230,53,.15); color: var(--accent, #a3e635); font-size: .78rem; font-weight: 700; display: flex; align-items: center; justify-content: center; }
    .upload-box { border: 2px dashed rgba(255,255,255,.15); border-radius: 10px; padding: 32px 20px; text-align: center; cursor: pointer; transition: border-color .2s, background .2s; }
    .upload-box:hover, .upload-box.over { border-color: var(--accent, #a3e635); background: rgba(163,230,53,.04); }
    .upload-box h3 { font-size: .95rem; margin-bottom: 5px; }
    .upload-box p { font-size: .8rem; opacity: .5; }
    .dates-row { display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap; margin-top: 16px; }
    .dates-row .field { display: flex; flex-direction: column; gap: 4px; }
    .dates-row label { font-size: .75rem; opacity: .6; }
    .dates-row input[type=date] { background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12); color: inherit; padding: 8px 12px; border-radius: 8px; font-size: .88rem; font-family: inherit; color-scheme: dark; }
    .file-info { margin-top: 12px; font-size: .83rem; color: var(--accent, #a3e635); }
    .msg { padding: 10px 14px; border-radius: 8px; font-size: .83rem; margin-top: 12px; }
    .msg.ok  { background: rgba(163,230,53,.12); border: 1px solid rgba(163,230,53,.3); color: #a3e635; }
    .msg.err { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; }
    .msg.inf { background: rgba(59,130,246,.10); border: 1px solid rgba(59,130,246,.25); color: #93c5fd; }
    .data-table { width: 100%; border-collapse: collapse; font-size: .82rem; margin-top: 6px; }
    .data-table th { padding: 8px 10px; text-align: left; font-weight: 500; font-size: .78rem; opacity: .5; border-bottom: 1px solid rgba(255,255,255,.08); white-space: nowrap; }
    .data-table td { padding: 7px 10px; border-bottom: 1px solid rgba(255,255,255,.05); vertical-align: middle; }
    .data-table tr:hover td { background: rgba(255,255,255,.03); }
    .tscroll { overflow-x: auto; }
    .empty { font-size: .83rem; opacity: .4; padding: 14px 0; }
    .antes { text-decoration: line-through; opacity: .55; }
    .despues { color: var(--accent, #a3e635); font-weight: 600; }
    .badge { padding: 2px 8px; border-radius: 4px; font-size: .72rem; font-weight: 600; white-space: nowrap; }
    .badge-prog { background: rgba(59,130,246,.2);  color: #93c5fd; }
    .badge-act  { background: rgba(163,230,53,.2);  color: #a3e635; }
    .badge-fin  { background: rgba(255,255,255,.08); color: #9ca3af; }
    .badge-ven  { background: rgba(251,191,36,.15);  color: #fcd34d; }
    .badge-can  { background: rgba(251,146,60,.15);  color: #fdba74; }
    .badge-err  { background: rgba(239,68,68,.2);    color: #fca5a5; }
    .actions-row { display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; align-items: center; }
    .btn-sec { background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12); color: inherit; padding: 7px 16px; border-radius: 8px; cursor: pointer; font-size: .82rem; font-family: inherit; transition: background .2s; }
    .btn-sec:hover:not(:disabled) { background: rgba(255,255,255,.1); }
    .btn-sec:disabled { opacity: .4; cursor: not-allowed; }
    .btn-cancel { background: transparent; border: 1px solid rgba(239,68,68,.4); color: #fca5a5; padding: 3px 10px; border-radius: 6px; cursor: pointer; font-size: .75rem; font-family: inherit; white-space: nowrap; }
    .btn-cancel:hover { background: rgba(239,68,68,.1); }
    .preview-section { margin-top: 16px; }
    .warn-row td { color: #fcd34d; opacity: .8; }
    code { font-family: monospace; font-size: .8rem; background: rgba(255,255,255,.06); padding: 1px 5px; border-radius: 3px; }
    .server-time { font-size: .75rem; opacity: .4; margin-left: auto; }
    @media(max-width:600px){ .dates-row { flex-direction: column; } }
  </style>
</head>
<body>
  <header class="topbar">
    <img class="brand-logo" src="../assets/logo.png" alt="Uno A Droguerías">
    <span class="sub">Promociones Programadas</span>
  </header>

  <main class="container">
    <a class="back" href="../shopify/">← Herramientas Shopify</a>

    <!-- Cómo funciona -->
    <div class="promo-section">
      <h2>🏷️ Cargar promociones</h2>
      <ol class="steps">
        <li>Prepara un archivo (CSV o Excel) con estas columnas, en este orden: <b>SKU</b> · <b>Nombre del producto</b> · <b>Precio antes</b> (el precio actual que se va a modificar) · <b>Precio después</b> (el precio durante la promoción) · <b>Inicio</b> · <b>Fin</b>. Puedes descargar la plantilla más abajo.</li>
        <li>Arrastra el archivo o haz clic para subirlo. Revisa la vista previa: el precio tachado es el "antes" y el verde es el "después".</li>
        <li>Si tu archivo no trae fechas, escribe el <b>Inicio</b> y el <b>Fin</b> en los campos que aparecen.</li>
        <li>Pulsa <b>📅 Programar</b>. El servidor pone el precio promo el día de inicio y lo restaura solo el día de fin, aunque tu computador esté apagado.</li>
      </ol>

      <div class="upload-box" id="dropZone"
           onclick="document.getElementById('fileIn').click()"
           ondragover="event.preventDefault();this.classList.add('over')"
           ondragleave="this.classList.remove('over')"
           ondrop="event.preventDefault();this.classList.remove('over');loadFile(event.dataTransfer.files[0])">
        <h3>Arrastra tu archivo o haz clic</h3>
        <p>CSV · XLSX · XLS &nbsp;·&nbsp; SKU · Nombre · Precio antes · Precio después · Inicio · Fin</p>
        <p style="margin-top:10px">
          <a href="#" onclick="dlTemplate(event)" style="color:var(--accent,#a3e635);font-size:.78rem">📥 Descargar plantilla CSV</a>
        </p>
      </div>
      <input type="file" id="fileIn" accept=".csv,.xlsx,.xls" style="display:none" onchange="loadFile(this.files[0])">
      <div class="file-info hidden" id="fileInfo"></div>

      <div class="dates-row hidden" id="dateRow">
        <div class="field">
          <label>Inicio (si el archivo no trae fechas)</label>
          <input type="date" id="gStart">
        </div>
        <div class="field">
          <label>Fin (incluido)</label>
          <input type="date" id="gEnd">
        </div>
        <button class="flecha" id="schedBtn" onclick="schedule()" style="align-self:flex-end;cursor:pointer;border:none;font-family:inherit;font-size:.88rem;padding:8px 20px;border-radius:8px">
          📅 Programar →
        </button>
      </div>
      <div class="msg hidden" id="schedMsg"></div>

      <!-- Vista previa -->
      <div class="preview-section hidden" id="preSection">
        <div class="tscroll">
          <table class="data-table">
            <thead><tr><th>SKU</th><th>Producto</th><th>Precio antes</th><th>Precio después</th><th>Inicio</th><th>Fin</th></tr></thead>
            <tbody id="preTbody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Promociones -->
    <div class="promo-section">
      <h2>📅 Promociones</h2>
      <div class="actions-row">
        <button class="btn-sec" id="runBtn" onclick="runNow()">▶ Ejecutar pendientes ahora</button>
        <button class="btn-sec" onclick="cleanList()">🧹 Limpiar terminadas</button>
        <span class="server-time" id="serverTime"></span>
      </div>
      <div class="tscroll">
        <table class="data-table">
          <thead>
            <tr>
              <th>SKU</th><th>Producto</th><th>Antes</th><th>Después</th>
              <th>Inicio</th><th>Fin</th><th>Estado</th><th></th>
            </tr>
          </thead>
          <tbody id="schedTbody"><tr><td colspan="8" class="empty">Sin promociones</td></tr></tbody>
        </table>
      </div>
    </div>

    <!-- Historial -->
    <div class="promo-section">
      <h2>📜 Historial reciente</h2>
      <div class="tscroll">
        <table class="data-table">
          <thead><tr><th>Fecha</th><th>Acción</th><th>SKU</th><th>Detalle</th></tr></thead>
          <tbody id="histTbody"><tr><td colspan="4" class="empty">Sin movimientos</td></tr></tbody>
        </table>
      </div>
    </div>

    <footer class="footer">Uno A Droguerías · Panel interno · <?php echo date('Y'); ?></footer>
  </main>

<script>
let fileRows = [];

window.addEventListener('DOMContentLoaded', () => {
  refresh();
  setInterval(refresh, 120000);
});

function refresh() {
  api({ action: 'list' }).then(d => {
    if (!d.error) {
      render(d.schedule, d.history);
      if (d.now) el('serverTime').textContent = 'Servidor: ' + d.now + ' (Bogotá)';
    }
  });
}

// ── Detección de columnas ────────────────────────────────────────
const COL_RULES = {
  sku:     ['sku', 'referencia', 'codigo', 'código', 'ref', 'cod'],
  product: ['nombre', 'producto', 'descripcion', 'descripción', 'articulo', 'artículo', 'item'],
  before:  ['antes', 'precio antes', 'actual', 'precio actual', 'original', 'precio original', 'normal', 'pleno'],
  after:   ['despues', 'después', 'precio despues', 'precio después', 'promo', 'precio promo', 'oferta', 'rebajado', 'descuento', 'nuevo'],
  start:   ['inicio', 'fecha inicio', 'desde', 'start'],
  end:     ['fin', 'fecha fin', 'hasta', 'end'],
};

function findCol(hdrs, hints) {
  for (const hint of hints) {
    const i = hdrs.findIndex(h => h === hint);
    if (i >= 0) return i;
  }
  for (const hint of hints) {
    const i = hdrs.findIndex(h => h.includes(hint));
    if (i >= 0) return i;
  }
  return -1;
}

function loadFile(file) {
  if (!file) return;
  const ext = file.name.split('.').pop().toLowerCase();
  if (!['csv', 'xlsx', 'xls'].includes(ext)) { alert('Usa CSV, XLSX o XLS'); return; }

  const reader = new FileReader();
  reader.onload = e => {
    try {
      const wb  = XLSX.read(e.target.result, { type: ext === 'csv' ? 'string' : 'array', cellDates: true });
      const ws  = wb.Sheets[wb.SheetNames[0]];
      const all = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
      if (all.length < 2) { alert('El archivo está vacío.'); return; }

      const hdrs = all[0].map(h => String(h).toLowerCase().trim());
      const si  = findCol(hdrs, COL_RULES.sku);
      const ni  = findCol(hdrs, COL_RULES.product);
      const bi  = findCol(hdrs, COL_RULES.before);
      const ai  = findCol(hdrs, COL_RULES.after);
      const sti = findCol(hdrs, COL_RULES.start);
      const eni = findCol(hdrs, COL_RULES.end);

      if (si < 0) { alert('No encuentro la columna SKU.\nEncabezados detectados: ' + hdrs.join(', ')); return; }
      if (ai < 0) { alert('No encuentro la columna "Precio después" (el precio durante la promo).\nEncabezados detectados: ' + hdrs.join(', ')); return; }

      fileRows = all.slice(1)
        .filter(r => String(r[si] ?? '').trim())
        .map(r => ({
          sku:         String(r[si]).trim(),
          product:     ni >= 0 ? String(r[ni]).trim() : '',
          beforePrice: bi >= 0 ? cleanNum(r[bi]) : 0,
          promoPrice:  cleanNum(r[ai]),
          start:       sti >= 0 ? toISO(r[sti]) : '',
          end:         eni >= 0 ? toISO(r[eni]) : '',
        }))
        .filter(r => r.promoPrice > 0);

      if (!fileRows.length) { alert('No hay filas válidas (cada una necesita SKU + Precio después mayor que 0).'); return; }

      el('fileInfo').textContent = '📄 ' + esc(file.name) + ' · ' + fileRows.length + ' filas';
      show('fileInfo', true);
      show('dateRow', true);
      show('schedMsg', false);
      renderPreview();
    } catch (err) {
      alert('Error leyendo el archivo: ' + err.message);
    }
  };
  ext === 'csv' ? reader.readAsText(file, 'UTF-8') : reader.readAsArrayBuffer(file);
}

function renderPreview() {
  const tb = el('preTbody');
  tb.innerHTML = '';
  fileRows.slice(0, 15).forEach(r => {
    const warn = !r.start || !r.end;
    tb.innerHTML += '<tr' + (warn ? ' class="warn-row"' : '') + '>'
      + '<td><code>' + esc(r.sku) + '</code></td>'
      + '<td>' + esc(r.product || '—') + '</td>'
      + '<td class="antes">' + (r.beforePrice ? '$' + r.beforePrice.toLocaleString('es-CO') : '—') + '</td>'
      + '<td class="despues">$' + r.promoPrice.toLocaleString('es-CO') + '</td>'
      + '<td>' + (r.start || '<span style="color:#fcd34d">falta</span>') + '</td>'
      + '<td>' + (r.end   || '<span style="color:#fcd34d">falta</span>') + '</td>'
      + '</tr>';
  });
  if (fileRows.length > 15) {
    tb.innerHTML += '<tr><td colspan="6" class="empty">… y ' + (fileRows.length - 15) + ' más</td></tr>';
  }
  show('preSection', true);
}

// ── Programar ────────────────────────────────────────────────────
function schedule() {
  const gS = el('gStart').value;
  const gE = el('gEnd').value;
  const rows = fileRows.map(r => ({ ...r, start: r.start || gS, end: r.end || gE }));

  const sinFechas = rows.filter(r => !r.start || !r.end).length;
  if (sinFechas > 0) {
    alert('Faltan fechas en ' + sinFechas + ' fila(s).\nPon las fechas en el archivo o usa las fechas de abajo.');
    return;
  }
  const invertidas = rows.filter(r => r.end < r.start).length;
  if (invertidas > 0) {
    alert(invertidas + ' fila(s) tienen la fecha Fin antes que el Inicio. Corrígelas.');
    return;
  }

  if (!confirm('¿Programar ' + rows.length + ' promoción(es)?')) return;

  setBtn('schedBtn', true, 'Programando…');
  api({ action: 'schedule', rows })
    .then(d => {
      if (d.error) { showMsg('err', '⚠ ' + d.error); return; }
      let txt = '✅ ' + d.added + ' programada(s)';
      if (d.applied) txt += ' · ' + d.applied + ' aplicada(s) de inmediato';
      if (d.invalid) txt += ' · ' + d.invalid + ' inválida(s) ignorada(s)';
      showMsg('ok', txt);
      fileRows = [];
      show('preSection', false);
      show('dateRow', false);
      show('fileInfo', false);
      el('fileIn').value = '';
      renderSchedule(d.schedule);
      refresh();
    })
    .catch(err => showMsg('err', 'Error de conexión: ' + err.message))
    .finally(() => setBtn('schedBtn', false, '📅 Programar →'));
}

// ── Acciones ─────────────────────────────────────────────────────
function cancelPromo(id, sku) {
  if (!confirm('¿Cancelar la promo de ' + sku + '?\nSi está activa, el precio original se restaura.')) return;
  api({ action: 'cancel', id })
    .then(d => { if (d.schedule) { renderSchedule(d.schedule); refresh(); } });
}

function cleanList() {
  if (!confirm('¿Quitar de la lista las promos terminadas, vencidas, canceladas y con error?')) return;
  api({ action: 'clean' }).then(d => { if (d.schedule) renderSchedule(d.schedule); });
}

function runNow() {
  setBtn('runBtn', true, 'Ejecutando…');
  api({ action: 'run' })
    .then(d => {
      if (d.schedule) renderSchedule(d.schedule);
      refresh();
      const n = (d.actions || []).length;
      if (n === 0) showMsg('inf', 'ℹ Sin cambios — no hay promos pendientes en este momento.');
      else         showMsg('ok', '✅ ' + n + ' acción(es) ejecutada(s).');
    })
    .catch(err => showMsg('err', 'Error: ' + err.message))
    .finally(() => setBtn('runBtn', false, '▶ Ejecutar pendientes ahora'));
}

// ── Render ───────────────────────────────────────────────────────
const BADGE = {
  programada: '<span class="badge badge-prog">⏳ Programada</span>',
  activa:     '<span class="badge badge-act">🟢 Activa</span>',
  finalizada: '<span class="badge badge-fin">✓ Finalizada</span>',
  vencida:    '<span class="badge badge-ven">⚠ Vencida</span>',
  cancelada:  '<span class="badge badge-can">✗ Cancelada</span>',
  error:      '<span class="badge badge-err">✗ Error</span>',
};

function render(schedule, history) {
  renderSchedule(schedule);

  const tb = el('histTbody');
  tb.innerHTML = '';
  if (!history || !history.length) {
    tb.innerHTML = '<tr><td colspan="4" class="empty">Sin movimientos</td></tr>';
    return;
  }
  history.forEach(h => {
    let detalle = esc(h.detalle || h.producto || '');
    if (h.antes  != null) detalle += ' · antes $' + Number(h.antes).toLocaleString('es-CO');
    if (h.promo  != null) detalle += ' → después $' + Number(h.promo).toLocaleString('es-CO');
    if (h.precio != null && h.promo == null) detalle += ' · $' + Number(h.precio).toLocaleString('es-CO');
    tb.innerHTML += '<tr>'
      + '<td style="white-space:nowrap">' + esc(h.fecha || '') + '</td>'
      + '<td>' + esc(h.accion || '') + '</td>'
      + '<td>' + (h.sku ? '<code>' + esc(h.sku) + '</code>' : '') + '</td>'
      + '<td>' + detalle + '</td>'
      + '</tr>';
  });
}

function renderSchedule(schedule) {
  const tb = el('schedTbody');
  tb.innerHTML = '';
  if (!schedule || !schedule.length) {
    tb.innerHTML = '<tr><td colspan="8" class="empty">Sin promociones</td></tr>';
    return;
  }
  schedule.slice().reverse().forEach(p => {
    const canCancel = ['programada', 'activa'].includes(p.status);
    const btn = canCancel
      ? '<button class="btn-cancel" onclick="cancelPromo(\'' + esc(p.id) + '\',\'' + esc(p.sku) + '\')">Cancelar</button>'
      : '';

    const antes = p.beforePrice
      ? '$' + Number(p.beforePrice).toLocaleString('es-CO')
      : (p.originalPrice ? '$' + Number(p.originalPrice).toLocaleString('es-CO') : '—');

    const statusCell = (BADGE[p.status] || esc(p.status))
      + (p.msg ? '<br><span style="font-size:.7rem;opacity:.45">' + esc(p.msg) + '</span>' : '');

    tb.innerHTML += '<tr>'
      + '<td><code>' + esc(p.sku) + '</code></td>'
      + '<td>' + esc(p.product || '—') + '</td>'
      + '<td class="antes">' + antes + '</td>'
      + '<td class="despues">$' + Number(p.promoPrice).toLocaleString('es-CO') + '</td>'
      + '<td>' + esc(p.start) + '</td>'
      + '<td>' + esc(p.end) + '</td>'
      + '<td>' + statusCell + '</td>'
      + '<td>' + btn + '</td>'
      + '</tr>';
  });
}

// ── Plantilla CSV ────────────────────────────────────────────────
function dlTemplate(e) {
  e.preventDefault();
  e.stopPropagation();
  const csv = 'SKU,Nombre del producto,Precio antes,Precio despues,Inicio,Fin\n'
            + 'FINHET-001,Finasterida 1mg x30,120.000,99.000,2026-07-01,2026-07-15\n'
            + 'ISOTRET-001,Isotretinoina 20mg x30,150.000,120.000,2026-07-01,2026-07-31\n';
  const a = document.createElement('a');
  a.href = URL.createObjectURL(new Blob([csv], { type: 'text/csv;charset=utf-8;' }));
  a.download = 'plantilla_promos.csv';
  a.click();
}

// ── Helpers ──────────────────────────────────────────────────────
function api(body) {
  return fetch('api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  }).then(r => r.json());
}

/**
 * Los precios no llevan decimales. Los puntos (y comas) son separadores
 * de miles, así que se eliminan todos: 120.000 → 120000, 1.234.567 → 1234567.
 */
function cleanNum(v) {
  const s = String(v ?? '').replace(/[^0-9]/g, '');
  const n = parseInt(s, 10);
  return isNaN(n) ? 0 : n;
}

function toISO(v) {
  if (!v) return '';
  if (v instanceof Date) {
    const y = v.getFullYear(), m = String(v.getMonth()+1).padStart(2,'0'), d = String(v.getDate()).padStart(2,'0');
    return y + '-' + m + '-' + d;
  }
  const s = String(v).trim();
  let match;
  match = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (match) return match[1] + '-' + match[2] + '-' + match[3];
  match = s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/);
  if (match) return match[3] + '-' + match[2].padStart(2,'0') + '-' + match[1].padStart(2,'0');
  const num = Number(s);
  if (!isNaN(num) && num > 10000) {
    const d = new Date(Date.UTC(1900, 0, num - 1));
    return d.toISOString().slice(0, 10);
  }
  return '';
}

function esc(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function el(id)          { return document.getElementById(id); }
function show(id, v)     { const e = el(id); if (e) { if (v) e.classList.remove('hidden'); else e.classList.add('hidden'); } }
function setBtn(id, dis, txt) { const b = el(id); if (b) { b.disabled = dis; if (txt) b.textContent = txt; } }
function showMsg(type, text) {
  const e = el('schedMsg');
  e.className = 'msg ' + type;
  e.textContent = text;
  show('schedMsg', true);
}
</script>
</body>
</html>
