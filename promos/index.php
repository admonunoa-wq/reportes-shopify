<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Promociones · Uno A</title>
  <link rel="stylesheet" href="../assets/style.css">
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <style>
    .promo-section { margin-bottom: 32px; }
    .promo-section h2 { font-size: 1rem; font-weight: 700; margin-bottom: 14px; color: var(--accent, #a3e635); border-bottom: 1px solid rgba(255,255,255,.08); padding-bottom: 8px; }
    .upload-box { border: 2px dashed rgba(255,255,255,.15); border-radius: 10px; padding: 32px 20px; text-align: center; cursor: pointer; transition: .2s; }
    .upload-box:hover, .upload-box.over { border-color: var(--accent, #a3e635); background: rgba(163,230,53,.04); }
    .upload-box h3 { font-size: .95rem; margin-bottom: 5px; }
    .upload-box p { font-size: .8rem; opacity: .5; }
    .dates-row { display: flex; gap: 14px; align-items: flex-end; flex-wrap: wrap; margin-top: 16px; }
    .dates-row .field { display: flex; flex-direction: column; gap: 4px; }
    .dates-row label { font-size: .75rem; opacity: .6; }
    .dates-row input[type=date] { background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12); color: inherit; padding: 8px 12px; border-radius: 8px; font-size: .88rem; font-family: inherit; color-scheme: dark; }
    .file-info { margin-top: 12px; font-size: .83rem; color: var(--accent, #a3e635); }
    .msg { padding: 10px 14px; border-radius: 8px; font-size: .83rem; margin-top: 12px; display: none; }
    .msg.ok  { background: rgba(163,230,53,.12); border: 1px solid rgba(163,230,53,.3); color: #a3e635; }
    .msg.err { background: rgba(239,68,68,.12); border: 1px solid rgba(239,68,68,.3); color: #fca5a5; }
    .data-table { width: 100%; border-collapse: collapse; font-size: .82rem; margin-top: 6px; }
    .data-table th { padding: 8px 10px; text-align: left; font-weight: 500; font-size: .78rem; opacity: .5; border-bottom: 1px solid rgba(255,255,255,.08); white-space: nowrap; }
    .data-table td { padding: 7px 10px; border-bottom: 1px solid rgba(255,255,255,.05); white-space: nowrap; }
    .data-table tr:hover td { background: rgba(255,255,255,.03); }
    .tscroll { overflow-x: auto; }
    .empty { font-size: .83rem; opacity: .4; padding: 14px 0; }
    .badge-prog  { background: rgba(59,130,246,.2);  color: #93c5fd; padding: 2px 8px; border-radius: 4px; font-size: .72rem; font-weight: 600; }
    .badge-act   { background: rgba(163,230,53,.2);  color: #a3e635; padding: 2px 8px; border-radius: 4px; font-size: .72rem; font-weight: 600; }
    .badge-fin   { background: rgba(255,255,255,.08); color: #9ca3af; padding: 2px 8px; border-radius: 4px; font-size: .72rem; font-weight: 600; }
    .badge-err   { background: rgba(239,68,68,.2);   color: #fca5a5; padding: 2px 8px; border-radius: 4px; font-size: .72rem; font-weight: 600; }
    .badge-can   { background: rgba(251,146,60,.15); color: #fdba74; padding: 2px 8px; border-radius: 4px; font-size: .72rem; font-weight: 600; }
    .actions-row { display: flex; gap: 10px; margin-bottom: 14px; flex-wrap: wrap; }
    .btn-sec { background: rgba(255,255,255,.06); border: 1px solid rgba(255,255,255,.12); color: inherit; padding: 7px 16px; border-radius: 8px; cursor: pointer; font-size: .82rem; font-family: inherit; transition: .2s; }
    .btn-sec:hover:not(:disabled) { background: rgba(255,255,255,.1); }
    .btn-sec:disabled { opacity: .4; cursor: not-allowed; }
    .btn-cancel { background: transparent; border: 1px solid rgba(239,68,68,.4); color: #fca5a5; padding: 3px 10px; border-radius: 6px; cursor: pointer; font-size: .75rem; font-family: inherit; }
    .btn-cancel:hover { background: rgba(239,68,68,.1); }
    .preview-section { margin-top: 16px; }
    code { font-family: monospace; font-size: .8rem; background: rgba(255,255,255,.06); padding: 1px 5px; border-radius: 3px; }
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
      <div class="msg" id="schedMsg"></div>

      <!-- Preview -->
      <div class="preview-section hidden" id="preSection">
        <div class="tscroll">
          <table class="data-table">
            <thead><tr><th>SKU</th><th>Precio Promo</th><th>Inicio</th><th>Fin</th></tr></thead>
            <tbody id="preTbody"></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Promociones activas / programadas -->
    <div class="promo-section">
      <h2>📅 Promociones</h2>
      <div class="actions-row">
        <button class="btn-sec" id="runBtn" onclick="runNow()">▶ Ejecutar pendientes ahora</button>
        <button class="btn-sec" onclick="cleanList()">🧹 Limpiar terminadas</button>
      </div>
      <div class="tscroll">
        <table class="data-table">
          <thead><tr><th>SKU</th><th>Producto</th><th>Promo</th><th>Inicio</th><th>Fin</th><th>Estado</th><th></th></tr></thead>
          <tbody id="schedTbody"><tr><td colspan="7" class="empty">Sin promociones</td></tr></tbody>
        </table>
      </div>
    </div>

    <!-- Historial -->
    <div class="promo-section">
      <h2>📜 Historial</h2>
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

// Cargar al iniciar
window.addEventListener('DOMContentLoaded', refresh);

function refresh() {
  api({ action: 'list' }).then(d => { if (!d.error) render(d.schedule, d.history); });
}

// ── Archivo ──────────────────────────────────────────────────────
const SKU_H   = ['sku','ref','codigo','referencia'];
const PRICE_H = ['promo','precio','price','oferta'];
const START_H = ['inicio','desde','start'];
const END_H   = ['fin','hasta','end'];

function loadFile(file) {
  if (!file) return;
  const ext = file.name.split('.').pop().toLowerCase();
  if (!['csv','xlsx','xls'].includes(ext)) { alert('Usa CSV, XLSX o XLS'); return; }

  const reader = new FileReader();
  reader.onload = e => {
    const wb = XLSX.read(e.target.result, { type: ext === 'csv' ? 'string' : 'array', cellDates: true });
    const ws = wb.Sheets[wb.SheetNames[0]];
    const all = XLSX.utils.sheet_to_json(ws, { header: 1, defval: '' });
    if (all.length < 2) { alert('Archivo vacío'); return; }

    const hdrs = all[0].map(h => String(h).toLowerCase().trim());
    const col  = hints => hdrs.findIndex(h => hints.some(x => h.includes(x)));
    const si = col(SKU_H), pi = col(PRICE_H), sti = col(START_H), eni = col(END_H);

    if (si < 0 || pi < 0) { alert('No encuentro SKU y Precio Promo. Revisa los encabezados.'); return; }

    fileRows = all.slice(1)
      .filter(r => String(r[si] ?? '').trim())
      .map(r => ({ sku: String(r[si]).trim(), promoPrice: cleanNum(r[pi]), start: sti >= 0 ? toISO(r[sti]) : '', end: eni >= 0 ? toISO(r[eni]) : '' }))
      .filter(r => r.promoPrice > 0);

    if (!fileRows.length) { alert('No hay filas válidas (SKU + precio > 0)'); return; }

    el('fileInfo').textContent = '📄 ' + file.name + ' · ' + fileRows.length + ' promociones';
    show('fileInfo', true); show('dateRow', true); show('schedMsg', false);
    renderPreview();
  };
  ext === 'csv' ? reader.readAsText(file, 'UTF-8') : reader.readAsArrayBuffer(file);
}

function renderPreview() {
  const tb = el('preTbody');
  tb.innerHTML = '';
  fileRows.slice(0, 10).forEach(r => {
    tb.innerHTML += '<tr><td><code>' + r.sku + '</code></td><td>$' + r.promoPrice.toLocaleString('es-CO') +
      '</td><td>' + (r.start || '—') + '</td><td>' + (r.end || '—') + '</td></tr>';
  });
  if (fileRows.length > 10) tb.innerHTML += '<tr><td colspan="4" class="empty">… y ' + (fileRows.length - 10) + ' más</td></tr>';
  show('preSection', true);
}

// ── Programar ────────────────────────────────────────────────────
function schedule() {
  const gS = el('gStart').value, gE = el('gEnd').value;
  const rows = fileRows.map(r => ({ ...r, start: r.start || gS, end: r.end || gE }));
  if (rows.some(r => !r.start || !r.end)) { alert('Faltan fechas. Ponlas en el archivo o usa las fechas globales.'); return; }
  if (!confirm('¿Programar ' + rows.length + ' promociones?')) return;

  setBtn('schedBtn', true, 'Programando...');
  api({ action: 'schedule', rows })
    .then(d => {
      if (d.error) { msg('err', d.error); return; }
      msg('ok', '✅ ' + d.added + ' programadas · ' + d.applied + ' aplicadas de inmediato' + (d.invalid ? ' · ' + d.invalid + ' inválidas' : ''));
      fileRows = []; show('preSection', false); show('dateRow', false); show('fileInfo', false); el('fileIn').value = '';
      renderSchedule(d.schedule); refresh();
    })
    .catch(() => msg('err', 'Error de conexión'))
    .finally(() => setBtn('schedBtn', false, '📅 Programar →'));
}

// ── Acciones ─────────────────────────────────────────────────────
function cancelPromo(id, sku) {
  if (!confirm('¿Cancelar la promo de ' + sku + '?\nSi está activa, el precio se restaura ya.')) return;
  api({ action: 'cancel', id }).then(d => { if (d.schedule) { renderSchedule(d.schedule); refresh(); } });
}
function cleanList() { api({ action: 'clean' }).then(d => { if (d.schedule) renderSchedule(d.schedule); }); }
function runNow() {
  setBtn('runBtn', true, 'Ejecutando...');
  api({ action: 'run' })
    .then(d => { if (d.schedule) renderSchedule(d.schedule); refresh(); })
    .finally(() => setBtn('runBtn', false, '▶ Ejecutar pendientes ahora'));
}

// ── Render ───────────────────────────────────────────────────────
const BADGE = {
  programada: '<span class="badge-prog">⏳ Programada</span>',
  activa:     '<span class="badge-act">🟢 Activa</span>',
  finalizada: '<span class="badge-fin">✓ Finalizada</span>',
  vencida:    '<span class="badge-can">Vencida</span>',
  cancelada:  '<span class="badge-can">Cancelada</span>',
  error:      '<span class="badge-err">✗ Error</span>',
};

function render(schedule, history) {
  renderSchedule(schedule);
  const tb = el('histTbody');
  tb.innerHTML = '';
  (history || []).forEach(h => {
    tb.innerHTML += '<tr><td>' + (h.fecha||'') + '</td><td>' + (h.accion||'') + '</td><td>' +
      (h.sku ? '<code>'+h.sku+'</code>' : '') + '</td><td>' +
      (h.detalle || h.producto || '') +
      (h.promo  ? ' · promo $' + Number(h.promo).toLocaleString('es-CO')   : '') +
      (h.precio ? ' · $'       + Number(h.precio).toLocaleString('es-CO') : '') + '</td></tr>';
  });
  if (!history || !history.length) tb.innerHTML = '<tr><td colspan="4" class="empty">Sin movimientos</td></tr>';
}

function renderSchedule(schedule) {
  const tb = el('schedTbody');
  tb.innerHTML = '';
  (schedule || []).slice().reverse().forEach(p => {
    const btn = ['programada','activa'].includes(p.status)
      ? '<button class="btn-cancel" onclick="cancelPromo(\''+p.id+'\',\''+p.sku+'\')">Cancelar</button>' : '';
    tb.innerHTML += '<tr><td><code>' + p.sku + '</code></td><td>' + (p.product||'—') +
      '</td><td>$' + Number(p.promoPrice).toLocaleString('es-CO') +
      '</td><td>' + p.start + '</td><td>' + p.end +
      '</td><td>' + (BADGE[p.status]||p.status) +
      (p.msg ? '<br><span style="font-size:.7rem;opacity:.5">'+p.msg+'</span>' : '') +
      '</td><td>' + btn + '</td></tr>';
  });
  if (!schedule || !schedule.length) tb.innerHTML = '<tr><td colspan="7" class="empty">Sin promociones</td></tr>';
}

// ── Plantilla ────────────────────────────────────────────────────
function dlTemplate(e) {
  e.preventDefault(); e.stopPropagation();
  const a = document.createElement('a');
  a.href = URL.createObjectURL(new Blob(['SKU,Precio Promo,Inicio,Fin\nFINHET-001,99000,2026-07-01,2026-07-15\nISOTRET-001,120000,2026-07-01,2026-07-31\n'], {type:'text/csv;charset=utf-8;'}));
  a.download = 'plantilla_promos.csv'; a.click();
}

// ── Helpers ──────────────────────────────────────────────────────
function api(body) {
  return fetch('api.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) }).then(r => r.json());
}
function cleanNum(v) { const n = parseFloat(String(v??'').replace(/[^0-9.,]/g,'').replace(',','.')); return isNaN(n)?0:n; }
function toISO(v) {
  if (!v) return '';
  if (v instanceof Date) return v.toISOString().slice(0,10);
  const s = String(v).trim();
  let m = s.match(/^(\d{4})-(\d{2})-(\d{2})/); if (m) return m[1]+'-'+m[2]+'-'+m[3];
  m = s.match(/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/); if (m) return m[3]+'-'+m[2].padStart(2,'0')+'-'+m[1].padStart(2,'0');
  return '';
}
function el(id) { return document.getElementById(id); }
function show(id, v) { const e = el(id); if (e) e.classList[v?'remove':'add']('hidden'); }
function setBtn(id, dis, txt) { const b = el(id); if (b) { b.disabled = dis; if(txt) b.textContent = txt; } }
function msg(type, text) { const e = el('schedMsg'); e.className = 'msg '+type; e.textContent = text; e.style.display = 'block'; }
</script>
</body>
</html>
