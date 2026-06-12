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
    .saving { font-size: .72rem; color: #a3e635; opacity: .8; }
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

    <!-- Cargar archivo -->
    <div class="promo-section">
      <h2>🏷️ Cargar promociones</h2>
      <div class="upload-box" id="dropZone"
           onclick="document.getElementById('fileIn').click()"
           ondragover="event.preventDefault();this.classList.add('over')"
           ondragleave="this.classList.remove('over')"
           ondrop="event.preventDefault();this.classList.remove('over');loadFile(event.dataTransfer.files[0])">
        <h3>Arrastra tu archivo o haz clic</h3>
        <p>CSV · XLSX · XLS &nbsp;·&nbsp; Columnas: SKU · Precio Promo · Inicio · Fin</p>
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

      <!-- Preview de filas cargadas -->
      <div class="preview-section hidden" id="preSection">
        <div class="tscroll">
          <table class="data-table">
            <thead><tr><th>SKU</th><th>Precio Promo</th><th>Inicio</th><th>Fin</th></tr></thead>
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
              <th>SKU</th><th>Producto</th><th>Promo</th><th>Original</th>
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
  // Auto-refresh cada 2 minutos
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
// Para cada tipo de columna, se busca el primer encabezado que contenga alguna de estas palabras.
// El orden importa: "precio promo" debe ganar sobre "precio" sólo en la columna de promo.
const COL_RULES = {
  sku:   ['sku', 'ref', 'codigo', 'referencia', 'cod'],
  price: ['promo', 'oferta', 'rebajado', 'descuento', 'nuevo precio', 'precio promo', 'precio oferta'],
  start: ['inicio', 'desde', 'start', 'fecha inicio', 'fecha desde'],
  end:   ['fin', 'hasta', 'end', 'fecha fin', 'fecha hasta'],
};

function findCol(hdrs, hints) {
  // Primero buscar coincidencia exacta de frase completa
  for (const hint of hints) {
    const i = hdrs.findIndex(h => h === hint);
    if (i >= 0) return i;
  }
  // Luego buscar que el encabezado contenga la pista
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
      if (all.length < 2) { alert('Archivo vacío'); return; }

      const hdrs = all[0].map(h => String(h).toLowerCase().trim());
      const si  = findCol(hdrs, COL_RULES.sku);
      const pi  = findCol(hdrs, COL_RULES.price);
      const sti = findCol(hdrs, COL_RULES.start);
      const eni = findCol(hdrs, COL_RULES.end);

      if (si < 0) { alert('No encuentro la columna SKU.\nEncabezados detectados: ' + hdrs.join(', ')); return; }
      if (pi < 0) { alert('No encuentro la columna de Precio Promo.\nEncabezados detectados: ' + hdrs.join(', ')); return; }

      fileRows = all.slice(1)
        .filter(r => String(r[si] ?? '').trim())
        .map(r => ({
          sku:        String(r[si]).trim(),
          promoPrice: cleanNum(r[pi]),
          start:      sti >= 0 ? toISO(r[sti]) : '',
          end:        eni >= 0 ? toISO(r[eni]) : '',
        }))
        .filter(r => r.promoPrice > 0);

      if (!fileRows.length) { alert('No hay filas válidas (necesitan SKU + precio > 0)'); return; }

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
  const preview = fileRows.slice(0, 15);
  preview.forEach(r => {
    const warn   = !r.start || !r.end;
    const trClass = warn ? ' class="warn-row"' : '';
    tb.innerHTML += '<tr' + trClass + '>'
      + '<td><code>' + esc(r.sku) + '</code></td>'
      + '<td>$' + r.promoPrice.toLocaleString('es-CO') + '</td>'
      + '<td>' + (r.start || '<span style="color:#fcd34d">falta</span>') + '</td>'
      + '<td>' + (r.end   || '<span style="color:#fcd34d">falta</span>') + '</td>'
      + '</tr>';
  });
  if (fileRows.length > 15) {
    tb.innerHTML += '<tr><td colspan="4" class="empty">… y ' + (fileRows.length - 15) + ' más</td></tr>';
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
    alert('Faltan fechas en ' + sinFechas + ' fila(s).\nPon las fechas en el archivo o usa las fechas globales.');
    return;
  }

  const invalidas = rows.filter(r => r.end < r.start).length;
  if (invalidas > 0) {
    alert(invalidas + ' fila(s) tienen la fecha Fin antes que Inicio. Corrígelas.');
    return;
  }

  if (!confirm('¿Programar ' + rows.length + ' promocion(es)?')) return;

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
  if (!confirm('¿Eliminar de la lista las promos terminadas, vencidas, canceladas y con error?')) return;
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
    if (h.promo  != null) detalle += ' → promo $' + Number(h.promo).toLocaleString('es-CO');
    if (h.precio != null && !h.promo) detalle += ' · $' + Number(h.precio).toLocaleString('es-CO');
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

    // Mostrar precio original sólo cuando ya se conoce (promo activa o finalizada)
    const origCell = p.originalPrice
      ? '$' + Number(p.originalPrice).toLocaleString('es-CO')
        + (p.status === 'activa' && p.originalPrice > p.promoPrice
            ? ' <span class="saving">(' + Math.round((1 - p.promoPrice/p.originalPrice)*100) + '% dto)</span>'
            : '')
      : '—';

    const statusCell = (BADGE[p.status] || esc(p.status))
      + (p.msg ? '<br><span style="font-size:.7rem;opacity:.45">' + esc(p.msg) + '</span>' : '');

    tb.innerHTML += '<tr>'
      + '<td><code>' + esc(p.sku) + '</code></td>'
      + '<td>' + esc(p.product || '—') + '</td>'
      + '<td>$' + Number(p.promoPrice).toLocaleString('es-CO') + '</td>'
      + '<td>' + origCell + '</td>'
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
  const csv = 'SKU,Precio Promo,Inicio,Fin\nFINHET-001,99000,2026-07-01,2026-07-15\nISOTRET-001,120000,2026-07-01,2026-07-31\n';
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
 * Convierte un valor de celda a número, manejando formatos colombianos
 * y europeos (1.234.567 = un millón doscientos treinta y cuatro mil...).
 */
function cleanNum(v) {
  let s = String(v ?? '').replace(/\s/g, '').replace(/[^0-9.,]/g, '').trim();
  if (!s) return 0;

  const dots   = (s.match(/\./g)  || []).length;
  const commas = (s.match(/,/g)   || []).length;

  if (dots > 1) {
    // 1.234.567 → miles con punto, sin decimal (Colombia/España)
    s = s.replace(/\./g, '');
    if (commas === 1) s = s.replace(',', '.'); // también tiene decimal con coma
  } else if (commas > 1) {
    // 1,234,567 → miles con coma, sin decimal (EE.UU. raro)
    s = s.replace(/,/g, '');
  } else if (dots === 1 && commas === 1) {
    // Ambos separadores: el primero indica miles
    s = s.indexOf('.') < s.indexOf(',')
      ? s.replace('.', '').replace(',', '.')   // 1.234,56 → 1234.56
      : s.replace(',', '');                    // 1,234.56 → 1234.56
  } else if (commas === 1) {
    // Una sola coma: puede ser decimal (99,50) → tratar como decimal
    s = s.replace(',', '.');
  }
  // Si queda sólo un punto → separador decimal normal

  const n = parseFloat(s);
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
  // YYYY-MM-DD
  match = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (match) return match[1] + '-' + match[2] + '-' + match[3];
  // DD/MM/YYYY o DD-MM-YYYY
  match = s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/);
  if (match) return match[3] + '-' + match[2].padStart(2,'0') + '-' + match[1].padStart(2,'0');
  // Número de serie de Excel (días desde 1900-01-01)
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
