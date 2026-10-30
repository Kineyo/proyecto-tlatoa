let ALL_DATA = [];
let currentCat = 'todos';
let currentState = 'todos';
let debounceTimer = null;
let lastQuery = '';

const resultsGrid = document.getElementById('resultsGrid');
const searchInput = document.getElementById('searchInput');
const resultCount = document.getElementById('resultCount');
const latencyStat = document.getElementById('latencyStat');
const statusBadge = document.getElementById('statusBadge');
const stateFilter = document.getElementById('stateFilter');
const detailModal = document.getElementById('detailModal');
const btnTop = document.getElementById('btnTop');

window.addEventListener('scroll', () => {
    btnTop.style.display = window.scrollY > 400 ? 'flex' : 'none';
});

function closeModal() {
    detailModal.classList.remove('open');
    if (window.location.hash.startsWith('#/politico/')) {
        history.replaceState(null, '', window.location.pathname + window.location.search);
    }
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeModal(); });

async function loadData() {
    try {
        const res = await fetch('./data/search_index.json');
        ALL_DATA = await res.json();
        
        statusBadge.innerText = `Catálogo Activo • ${ALL_DATA.length.toLocaleString()} Registros`;

        // Poblar select de estados
        const states = [...new Set(ALL_DATA.map(d => d.e).filter(Boolean))].sort();
        states.forEach(st => {
            const opt = document.createElement('option');
            opt.value = st;
            opt.textContent = st;
            stateFilter.appendChild(opt);
        });

        // Soporte para parámetros de URL (?q=...&cat=...&estado=...)
        const params = new URLSearchParams(window.location.search);
        if (params.has('q')) {
            searchInput.value = params.get('q');
            lastQuery = params.get('q').toLowerCase();
        }
        if (params.has('cat')) {
            currentCat = params.get('cat');
            document.querySelectorAll('.filter-pill').forEach(b => {
                b.classList.toggle('active', b.dataset.cat === currentCat);
            });
        }
        if (params.has('estado')) {
            currentState = params.get('estado');
            stateFilter.value = currentState;
        }

        filterAndRender();

        // Soporte para deep link en hash (#/politico/slug o #slug)
        const hash = window.location.hash.replace(/^#\/?(politico\/)?/, '');
        if (hash) {
            openDetails(decodeURIComponent(hash));
        }
    } catch (err) {
        resultsGrid.innerHTML = `
            <div style="grid-column:1/-1;text-align:center;padding:48px 16px;color:#DC2626;">
                <div style="font-size:3rem;margin-bottom:8px;">🦉</div>
                <strong>No se pudo cargar el catálogo de datos</strong>
                <p style="font-size:0.9rem;margin-top:4px;">Verifica que el archivo <code>data/search_index.json</code> esté disponible.</p>
            </div>`;
    }
}

// Filtros por Categoría
document.querySelectorAll('.filter-pill').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-pill').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentCat = btn.dataset.cat;
        filterAndRender();
    });
});

// Filtro por Estado
stateFilter.addEventListener('change', (e) => {
    currentState = e.target.value;
    filterAndRender();
});

// Búsqueda con debounce
searchInput.addEventListener('input', (e) => {
    clearTimeout(debounceTimer);
    lastQuery = e.target.value.trim().toLowerCase();
    debounceTimer = setTimeout(filterAndRender, 80);
});

function filterAndRender() {
    const t0 = performance.now();
    let filtered = ALL_DATA;

    if (currentCat !== 'todos') {
        filtered = filtered.filter(item => item.k === currentCat);
    }

    if (currentState !== 'todos') {
        filtered = filtered.filter(item => item.e === currentState);
    }

    if (lastQuery.length > 0) {
        filtered = filtered.filter(item => 
            item.n.toLowerCase().includes(lastQuery) ||
            item.e.toLowerCase().includes(lastQuery) ||
            item.p.toLowerCase().includes(lastQuery) ||
            item.r.toLowerCase().includes(lastQuery)
        );
    }

    const elapsed = (performance.now() - t0).toFixed(1);
    latencyStat.innerHTML = `Latencia: <strong style="color:#059669;">${elapsed} ms</strong>`;
    resultCount.innerText = `Mostrando ${filtered.length.toLocaleString()} resultados de ${ALL_DATA.length.toLocaleString()}`;

    const toRender = filtered.slice(0, 120);
    renderGrid(toRender, filtered.length);
}

function renderGrid(items, totalCount) {
    if (items.length === 0) {
        resultsGrid.innerHTML = `
            <div style="grid-column:1/-1;text-align:center;padding:48px 16px;color:var(--text-muted);">
                <div style="font-size:3rem;margin-bottom:8px;">🦉</div>
                <strong style="color:var(--text-main);">Sin coincidencias</strong>
                <p style="font-size:0.9rem;margin-top:4px;">No se encontraron registros para tu búsqueda o filtros actuales.</p>
            </div>`;
        return;
    }

    let html = items.map(p => {
        const color = p.c || '#4B5563';
        const fotoHtml = p.f 
            ? `<img src="${encodeURI(p.f)}" style="width:42px;height:42px;border-radius:50%;object-fit:cover;margin-right:12px;flex-shrink:0;background:#E5E7EB;" onerror="this.style.display='none'">` 
            : '';
        return `
        <div class="card-pol" onclick="openDetails('${escapeHtml(p.s)}')">
            <div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
                    <span class="badge-party" style="background-color:${color}18;color:${color};">${escapeHtml(p.p)}</span>
                    <span style="color:var(--text-muted);font-size:0.8rem;">${escapeHtml(p.e)}</span>
                </div>
                <div style="display:flex;align-items:center;">
                    ${fotoHtml}
                    <div style="overflow:hidden;">
                        <div style="font-weight:700;font-size:0.95rem;margin-bottom:2px;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;">${escapeHtml(p.n)}</div>
                        <div style="color:var(--text-muted);font-size:0.82rem;white-space:nowrap;text-overflow:ellipsis;overflow:hidden;">${escapeHtml(p.r)}</div>
                    </div>
                </div>
            </div>
        </div>`;
    }).join('');

    if (totalCount > 120) {
        html += `
        <div style="grid-column:1/-1;text-align:center;padding:16px;color:var(--text-muted);font-size:0.85rem;">
            <em>(Mostrando los primeros 120 de ${totalCount.toLocaleString()} resultados. Escribe más letras en el buscador para filtrar con precisión).</em>
        </div>`;
    }

    resultsGrid.innerHTML = html;
}

function openDetails(slug) {
    const p = ALL_DATA.find(item => item.s === slug);
    if (!p) return;

    window.location.hash = '#/politico/' + encodeURIComponent(slug);

    const color = p.c || '#2563EB';
    const fotoHtml = p.f 
        ? `<img src="${encodeURI(p.f)}" style="width:68px;height:68px;border-radius:50%;object-fit:cover;margin-bottom:12px;background:#E5E7EB;" onerror="this.style.display='none'">`
        : '';

    document.getElementById('modalPolHeader').innerHTML = `
        <div style="text-align:center;border-bottom:1px solid var(--border);padding-bottom:16px;">
            ${fotoHtml}
            <h3 style="font-size:1.25rem;font-weight:700;margin-bottom:4px;">${escapeHtml(p.n)}</h3>
            <span class="badge-party" style="background:${color}20;color:${color};font-size:0.85rem;padding:4px 12px;">${escapeHtml(p.p)}</span>
        </div>
    `;

    document.getElementById('modalPolBody').innerHTML = `
        <div style="display:flex;flex-direction:column;gap:10px;font-size:0.9rem;">
            <div><strong>Cargo Oficial:</strong> ${escapeHtml(p.r)}</div>
            <div><strong>Entidad Federativa:</strong> ${escapeHtml(p.e)}</div>
            <div><strong>Identificador Único (Slug):</strong> <code>${escapeHtml(p.s)}</code></div>
            <div><strong>Estatus:</strong> ${p.a ? '✅ Activo / Vigente' : '📜 Histórico'}</div>
            <div style="background:#F9FAFB;border:1px solid var(--border);border-radius:10px;padding:12px;margin-top:8px;">
                <strong style="display:block;margin-bottom:4px;color:var(--text-main);">Fuente Gubernamental Oficial:</strong>
                <a href="${escapeHtml(p.u)}" target="_blank" rel="noopener noreferrer" style="color:var(--accent);word-break:break-all;font-size:0.85rem;text-decoration:underline;">
                    ${escapeHtml(p.u)} &rarr;
                </a>
            </div>
        </div>
    `;

    detailModal.classList.add('open');
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

loadData();
