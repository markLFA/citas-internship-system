/**
 * ============================================================
 *  citas-ui.js  —  CITAS Component Library
 * ============================================================
 *
 *  HOW TO USE:
 *    1. Add <link rel="stylesheet" href="citas-ui.css"> in <head>
 *    2. Add <script src="citas-ui.js"></script> before </body>
 *    3. Call any function below to create a component.
 *
 *  QUICK REFERENCE:
 *    UI.button(text, options)
 *    UI.card(options)
 *    UI.statCard(value, label, icon, color)
 *    UI.modal(options)        → returns { el, open(), close() }
 *    UI.sidebar(options)      → returns { el, open(), close(), toggle() }
 *    UI.topbar(options)
 *    UI.badge(text, color)
 *    UI.alert(message, type, options)
 *    UI.toast(message, type, duration)
 *    UI.input(options)
 *    UI.textarea(options)
 *    UI.select(options)
 *    UI.formGroup(label, inputEl, hint)
 *    UI.table(columns, rows, options)
 *    UI.avatar(initials, size, color)
 *    UI.dropdown(triggerEl, items)
 *    UI.tabs(tabsArray, onChange)
 *    UI.progress(value, options)
 *    UI.divider(label)
 *    UI.empty(icon, title, message, actionEl)
 *    UI.spinner(size)
 *    UI.skeleton(width, height)
 *    UI.chip(text, onClose)
 * ============================================================
 */

const UI = (() => {

  // ── Internal helpers ───────────────────────────────────────

  function el(tag, cls, attrs = {}) {
    const e = document.createElement(tag);
    if (cls) e.className = cls;
    Object.entries(attrs).forEach(([k, v]) => {
      if (k === 'html')    e.innerHTML = v;
      else if (k === 'text') e.textContent = v;
      else if (k.startsWith('on')) e.addEventListener(k.slice(2), v);
      else e.setAttribute(k, v);
    });
    return e;
  }

  function append(parent, ...children) {
    children.forEach(c => c && parent.appendChild(c));
    return parent;
  }

  // ── applyColor ─────────────────────────────────────────────
  // Used by every component that accepts a color option.
  // color can be:
  //   'primary'  → uses the CSS class (default behaviour, unchanged)
  //   { base, hover, text, border }  → applies inline styles + hover swap
  //     base:   background color
  //     hover:  background color on mouse over  (optional)
  //     text:   text / foreground color         (optional)
  //     border: border color                    (optional)
  //
  // Returns true when a custom object was applied so the caller
  // can skip adding a variant class that would conflict.
  function applyColor(element, color) {
    if (!color || typeof color === 'string') return false; // let CSS handle it

    const { base, hover, text, border } = color;
    if (base)   element.style.background = base;
    if (text)   element.style.color      = text;
    if (border) element.style.border     = `1.5px solid ${border}`;

    if (hover) {
      element.addEventListener('mouseenter', () => element.style.background = hover);
      element.addEventListener('mouseleave', () => element.style.background = base || '');
    }
    return true;
  }

  // ════════════════════════════════════════════════════════
  //  BUTTON
  //  UI.button('Save', { variant, size, icon, block, disabled, onClick, color })
  //
  //  variant: 'primary' | 'success' | 'danger' | 'warning' |
  //           'outline' | 'ghost' | 'link'         (default: 'primary')
  //  size:    'xs' | 'sm' | 'md' | 'lg' | 'xl'    (default: 'md')
  //  color:   'primary' | { base, hover, text, border }
  //  icon:    emoji or HTML string prepended to text
  //  block:   true → full width
  //  onClick: function
  // ════════════════════════════════════════════════════════
  function button(text, {
    variant  = 'primary',
    size     = 'md',
    icon     = '',
    block    = false,
    disabled = false,
    type     = 'button',
    id       = '',
    color    = null,
    onClick  = null,
  } = {}) {
    // When a custom color object is passed, skip the variant CSS class
    // so there's no background conflict with the preset styles.
    const isCustom  = color && typeof color === 'object';
    const variantCls = isCustom ? '' : `cui-btn-${variant}`;
    const btn = el('button', `cui-btn ${variantCls} cui-btn-${size}${block ? ' cui-btn-block' : ''}`.trim(), { type });

    if (id)       btn.id       = id;
    if (icon)     btn.appendChild(el('span', '', { text: icon }));
    btn.appendChild(document.createTextNode(text));
    if (disabled) btn.disabled = true;
    if (onClick)  btn.addEventListener('click', onClick);
    if (isCustom) applyColor(btn, color);

    return btn;
  }

  // ════════════════════════════════════════════════════════
  //  CARD
  //  UI.card({ title, subtitle, body, footer, hoverable, id, color })
  //
  //  color: { base, text, border }
  //    base   → card background
  //    text   → card text color
  //    border → left border accent (e.g. a colored stripe)
  // ════════════════════════════════════════════════════════
  function card({
    title         = '',
    subtitle      = '',
    body          = '',
    footer        = [],
    headerActions = null,
    hoverable     = false,
    id            = '',
    padding       = true,
    color         = null,
  } = {}) {
    const wrap = el('div', `cui-card${hoverable ? ' cui-card-hover' : ''}`);
    if (id) wrap.id = id;

    if (color && typeof color === 'object') {
      if (color.base)   wrap.style.background  = color.base;
      if (color.text)   wrap.style.color        = color.text;
      if (color.border) wrap.style.borderLeft   = `4px solid ${color.border}`;
    }

    // Header
    if (title) {
      const header = el('div', 'cui-card-header');
      const titleWrap = el('div');
      titleWrap.appendChild(el('div', 'cui-card-title', { text: title }));
      if (subtitle) titleWrap.appendChild(el('div', 'cui-card-subtitle', { text: subtitle }));
      header.appendChild(titleWrap);
      if (headerActions) header.appendChild(headerActions);
      wrap.appendChild(header);
    }

    // Body
    const bodyEl = el('div', padding ? 'cui-card-body' : '');
    if (typeof body === 'string') bodyEl.innerHTML = body;
    else bodyEl.appendChild(body);
    wrap.appendChild(bodyEl);

    // Footer
    if (footer.length) {
      const footerEl = el('div', 'cui-card-footer');
      footer.forEach(f => footerEl.appendChild(f));
      wrap.appendChild(footerEl);
    }

    return wrap;
  }

  // ════════════════════════════════════════════════════════
  //  STAT CARD
  //  UI.statCard('128', 'Total Hours', '🕐', 'primary')
  //  UI.statCard('128', 'Total Hours', '🕐', { base:'#fce7f3', text:'#9d174d' })
  //
  //  color: preset string OR { base, text } for icon background
  // ════════════════════════════════════════════════════════
  function statCard(value, label, icon = '📊', color = 'primary') {
    const wrap   = el('div', 'cui-stat-card');
    const isCustom = color && typeof color === 'object';
    const iconEl = el('div', isCustom ? 'cui-stat-icon' : `cui-stat-icon cui-stat-icon-${color}`, { text: icon });
    if (isCustom) {
      if (color.base) iconEl.style.background = color.base;
      if (color.text) iconEl.style.color      = color.text;
    }
    const info = el('div');
    info.appendChild(el('div', 'cui-stat-value', { text: String(value) }));
    info.appendChild(el('div', 'cui-stat-label', { text: label }));
    append(wrap, iconEl, info);
    return wrap;
  }

  // ════════════════════════════════════════════════════════
  //  MODAL
  //  const m = UI.modal({ title, body, footer, size, onClose })
  //  m.open()   → shows the modal
  //  m.close()  → hides it
  //  m.el       → the overlay DOM element
  //
  //  size: 'sm' | '' | 'lg' | 'xl'   (default: '')
  // ════════════════════════════════════════════════════════
  function modal({
    title   = '',
    body    = '',
    footer  = [],
    size    = '',
    id      = '',
    onClose = null,
  } = {}) {
    // Overlay
    const overlay = el('div', 'cui-overlay');
    if (id) overlay.id = id;

    // Modal box
    const box = el('div', `cui-modal${size ? ' cui-modal-' + size : ''}`);

    // Header
    if (title) {
      const header = el('div', 'cui-modal-header');
      header.appendChild(el('div', 'cui-modal-title', { text: title }));
      const closeBtn = el('button', 'cui-modal-close', { html: '✕' });
      closeBtn.addEventListener('click', close);
      header.appendChild(closeBtn);
      box.appendChild(header);
    }

    // Body
    const bodyEl = el('div', 'cui-modal-body');
    if (typeof body === 'string') bodyEl.innerHTML = body;
    else if (body instanceof Node) bodyEl.appendChild(body);
    box.appendChild(bodyEl);

    // Footer
    if (footer.length) {
      const footerEl = el('div', 'cui-modal-footer');
      footer.forEach(f => footerEl.appendChild(f));
      box.appendChild(footerEl);
    }

    overlay.appendChild(box);
    document.body.appendChild(overlay);

    // Close on overlay click
    overlay.addEventListener('click', e => {
      if (e.target === overlay) close();
    });

    function open() {
      overlay.classList.add('cui-open');
      document.body.style.overflow = 'hidden';
    }
    function close() {
      overlay.classList.remove('cui-open');
      document.body.style.overflow = '';
      if (onClose) onClose();
    }

    // Close on Escape
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && overlay.classList.contains('cui-open')) close();
    });

    return { el: overlay, open, close, bodyEl };
  }

  // ════════════════════════════════════════════════════════
  //  SIDEBAR  (popup slide-in navigation)
  //  const nav = UI.sidebar({ brand, links, footer })
  //  nav.open()    → slides in the sidebar
  //  nav.close()   → slides it out
  //  nav.toggle()  → toggles open/closed
  //  nav.el        → sidebar DOM element
  //
  //  links: [{ label, href, icon, active, section }]
  //    → section: if true, renders as a section label divider
  // ════════════════════════════════════════════════════════
  function sidebar({
    brand    = 'CITAS',
    brandIcon = '🎓',
    links    = [],
    footer   = null,
    id       = 'cui-sidebar',
  } = {}) {
    // Overlay behind sidebar
    const bgOverlay = el('div', 'cui-sidebar-overlay');
    bgOverlay.addEventListener('click', close);
    document.body.appendChild(bgOverlay);

    // Sidebar panel
    const panel = el('aside', 'cui-sidebar cui-sidebar-hidden');
    if (id) panel.id = id;

    // Brand
    const brandEl = el('div', 'cui-sidebar-brand');
    brandEl.appendChild(el('span', '', { text: brandIcon }));
    brandEl.appendChild(el('span', '', { text: brand }));
    panel.appendChild(brandEl);

    // Nav links
    const nav = el('nav', '', { style: 'padding: .75rem 0; flex:1;' });
    links.forEach(link => {
      if (link.section) {
        nav.appendChild(el('div', 'cui-nav-section-label', { text: link.label }));
        return;
      }
      const a = el('a', `cui-nav-link${link.active ? ' active' : ''}`, {
        href: link.href || '#'
      });
      if (link.icon) a.appendChild(el('span', 'cui-nav-icon', { text: link.icon }));
      a.appendChild(el('span', '', { text: link.label }));
      if (link.onClick) a.addEventListener('click', e => { e.preventDefault(); link.onClick(e); });
      nav.appendChild(a);
    });
    panel.appendChild(nav);

    // Footer slot
    if (footer) {
      const footerWrap = el('div', 'cui-sidebar-footer');
      if (typeof footer === 'string') footerWrap.innerHTML = footer;
      else footerWrap.appendChild(footer);
      panel.appendChild(footerWrap);
    }

    document.body.appendChild(panel);

    function open() {
      panel.classList.remove('cui-sidebar-hidden');
      bgOverlay.classList.add('cui-open');
    }
    function close() {
      panel.classList.add('cui-sidebar-hidden');
      bgOverlay.classList.remove('cui-open');
    }
    function toggle() {
      panel.classList.contains('cui-sidebar-hidden') ? open() : close();
    }

    return { el: panel, open, close, toggle };
  }

  // ════════════════════════════════════════════════════════
  //  TOPBAR  (horizontal navigation bar)
  //  UI.topbar({ brand, brandIcon, actions, onMenuClick })
  //
  //  actions: array of elements placed on the right side
  //  onMenuClick: called when hamburger button is clicked
  // ════════════════════════════════════════════════════════
  function topbar({
    brand       = 'CITAS',
    brandIcon   = '🎓',
    actions     = [],
    onMenuClick = null,
  } = {}) {
    const bar = el('header', 'cui-topbar');

    // Hamburger
    const ham = el('button', 'cui-hamburger', { 'aria-label': 'Toggle menu' });
    ['', '', ''].forEach(() => ham.appendChild(el('span')));
    if (onMenuClick) {
      ham.addEventListener('click', () => {
        ham.classList.toggle('open');
        onMenuClick(ham.classList.contains('open'));
      });
    }
    bar.appendChild(ham);

    // Brand
    const brandEl = el('a', 'cui-topbar-brand', { href: '#' });
    brandEl.appendChild(el('span', '', { text: brandIcon }));
    brandEl.appendChild(el('span', '', { text: brand }));
    bar.appendChild(brandEl);

    // Spacer
    bar.appendChild(el('div', 'cui-topbar-spacer'));

    // Actions
    if (actions.length) {
      const actWrap = el('div', 'cui-topbar-actions');
      actions.forEach(a => actWrap.appendChild(a));
      bar.appendChild(actWrap);
    }

    return bar;
  }

  // ════════════════════════════════════════════════════════
  //  BADGE
  //  UI.badge('Pending', 'warning')
  //  UI.badge('Custom', { base:'#fce7f3', text:'#9d174d' })
  //  UI.badge('Active', 'success', { dot: true })
  //
  //  color: preset string OR { base, text, border }
  // ════════════════════════════════════════════════════════
  function badge(text, color = 'primary', { dot = false } = {}) {
    const isCustom = color && typeof color === 'object';
    const cls = `cui-badge${isCustom ? '' : ' cui-badge-' + color}${dot ? ' cui-badge-dot' : ''}`;
    const b = el('span', cls, { text });
    if (isCustom) {
      if (color.base)   b.style.background = color.base;
      if (color.text)   b.style.color      = color.text;
      if (color.border) b.style.border     = `1px solid ${color.border}`;
    }
    return b;
  }

  // ════════════════════════════════════════════════════════
  //  ALERT
  //  UI.alert('Saved!', 'success', { title, icon, closeable })
  //  UI.alert('Note', 'info', { color: { base:'#f0fdf4', text:'#166534' } })
  //
  //  type:     'success' | 'danger' | 'warning' | 'info'
  //  color:    { base, text } — overrides the type color
  //  closeable: true → adds an X button that removes the alert
  // ════════════════════════════════════════════════════════
  function alert(message, type = 'info', {
    title     = '',
    icon      = '',
    closeable = true,
    color     = null,
    onClose   = null,
  } = {}) {
    const icons   = { success: '✅', danger: '❌', warning: '⚠️', info: 'ℹ️' };
    const isCustom = color && typeof color === 'object';
    const wrap  = el('div', `cui-alert${isCustom ? '' : ' cui-alert-' + type}`);
    if (isCustom) {
      if (color.base) wrap.style.background = color.base;
      if (color.text) wrap.style.color      = color.text;
    }

    wrap.appendChild(el('span', 'cui-alert-icon', { text: icon || icons[type] }));

    const body = el('div', 'cui-alert-body');
    if (title) body.appendChild(el('div', 'cui-alert-title', { text: title }));
    body.appendChild(document.createTextNode(message));
    wrap.appendChild(body);

    if (closeable) {
      const closeBtn = el('button', 'cui-alert-close', { text: '×' });
      closeBtn.addEventListener('click', () => { wrap.remove(); if (onClose) onClose(); });
      wrap.appendChild(closeBtn);
    }

    return wrap;
  }

  // ════════════════════════════════════════════════════════
  //  TOAST  (auto-dismiss notification)
  //  UI.toast('Saved!', 'success', 3000)
  //
  //  type:     'success' | 'danger' | 'warning' | 'info'
  //  duration: milliseconds before auto-dismiss (default: 3500)
  // ════════════════════════════════════════════════════════
  function toast(message, type = 'info', duration = 3500) {
    // Get or create container
    let container = document.querySelector('.cui-toast-container');
    if (!container) {
      container = el('div', 'cui-toast-container');
      document.body.appendChild(container);
    }

    const colors = {
      success: { bg: '#D1FAE5', color: '#065F46', icon: '✅' },
      danger:  { bg: '#FEE2E2', color: '#991B1B', icon: '❌' },
      warning: { bg: '#FEF3C7', color: '#92400E', icon: '⚠️' },
      info:    { bg: '#DBEAFE', color: '#1E40AF', icon: 'ℹ️' },
    };
    const c = colors[type] || colors.info;

    const t = el('div', 'cui-toast');
    t.style.background = c.bg;
    t.style.color = c.color;
    t.appendChild(el('span', '', { text: c.icon }));
    t.appendChild(document.createTextNode(message));

    // Dismiss button
    const x = el('button', '', {
      text: '×',
      style: `background:none;border:none;cursor:pointer;color:inherit;font-size:1.1rem;margin-left:auto;padding:0;line-height:1;opacity:.7;`,
    });
    x.addEventListener('click', () => { t.remove(); if (!container.children.length) container.remove(); });
    t.appendChild(x);

    container.appendChild(t);
    if (duration > 0) setTimeout(() => { t.remove(); if (!container.children.length) container.remove(); }, duration);
    return t;
  }

  // ════════════════════════════════════════════════════════
  //  INPUT
  //  UI.input({ type, placeholder, value, id, name, required,
  //             disabled, error, color, onChange })
  //
  //  type: 'text' | 'email' | 'password' | 'number' | 'date'
  //        'tel'  | 'url'   | 'search'   | etc.
  //        'select' → renders a <select> instead of <input>
  //
  //  When type = 'select':
  //    options:     required. Array of strings or { value, label } objects.
  //    placeholder: optional first disabled option e.g. 'Choose one…'
  //    value:       the option value to pre-select
  //
  //  Examples:
  //    UI.input({ type:'select', options:['BSIT','BSCS','BSBA'] })
  //    UI.input({ type:'select', placeholder:'Pick role…',
  //               options:[{ value:'intern', label:'Student Intern' },
  //                        { value:'coordinator', label:'Coordinator' }],
  //               value:'intern' })
  // ════════════════════════════════════════════════════════
  function input({
    type        = 'text',
    placeholder = '',
    value       = '',
    id          = '',
    name        = '',
    required    = false,
    disabled    = false,
    error       = false,
    color       = null,
    options     = [],    // only used when type === 'select'
    onChange    = null,
  } = {}) {

    // ── SELECT variant ──────────────────────────────────────
    if (type === 'select') {
      const sel = el('select', `cui-input${error ? ' cui-input-error' : ''}`);
      if (id)       sel.id       = id;
      if (name)     sel.name     = name;
      if (required) sel.required = true;
      if (disabled) sel.disabled = true;

      // Optional blank/placeholder first option
      if (placeholder) {
        const blank = el('option', '', { value: '', text: placeholder });
        blank.disabled = true;
        blank.selected = (value === '' || value == null);
        sel.appendChild(blank);
      }

      // Build option elements
      options.forEach(o => {
        const v   = typeof o === 'string' ? o       : (o.value ?? '');
        const lbl = typeof o === 'string' ? o       : (o.label ?? o.value ?? '');
        const opt = el('option', '', { value: v, text: lbl });
        if (String(v) === String(value)) opt.selected = true;
        sel.appendChild(opt);
      });

      if (onChange) sel.addEventListener('change', onChange);
      if (color && typeof color === 'object') applyColor(sel, color);
      return sel;
    }

    // ── Regular <input> variant ─────────────────────────────
    const inp = el('input', `cui-input${error ? ' cui-input-error' : ''}`);
    inp.type = type;
    if (placeholder) inp.placeholder = placeholder;
    if (value)       inp.value       = value;
    if (id)          inp.id          = id;
    if (name)        inp.name        = name;
    if (required)    inp.required    = true;
    if (disabled)    inp.disabled    = true;
    if (onChange)    inp.addEventListener('input', onChange);
    if (color && typeof color === 'object') applyColor(inp, color);
    return inp;
  }

  // ════════════════════════════════════════════════════════
  //  TEXTAREA
  //  UI.textarea({ placeholder, value, rows, id, name })
  // ════════════════════════════════════════════════════════
  function textarea({
    placeholder = '',
    value       = '',
    rows        = 4,
    id          = '',
    name        = '',
    required    = false,
    onChange    = null,
  } = {}) {
    const t = el('textarea', 'cui-input cui-textarea');
    t.placeholder = placeholder;
    t.value       = value;
    t.rows        = rows;
    if (id)       t.id       = id;
    if (name)     t.name     = name;
    if (required) t.required = true;
    if (onChange) t.addEventListener('input', onChange);
    return t;
  }

  // ════════════════════════════════════════════════════════
  //  SELECT
  //  UI.select({ options, value, id, name, placeholder, onChange })
  //
  //  options: [{ value, label }]  or  ['string1', 'string2']
  // ════════════════════════════════════════════════════════
  function select({
    options     = [],
    value       = '',
    id          = '',
    name        = '',
    placeholder = '',
    required    = false,
    onChange    = null,
  } = {}) {
    const sel = el('select', 'cui-input');
    if (id)       sel.id       = id;
    if (name)     sel.name     = name;
    if (required) sel.required = true;

    if (placeholder) {
      const opt = el('option', '', { value: '', text: placeholder });
      opt.disabled = true;
      opt.selected = !value;
      sel.appendChild(opt);
    }
    options.forEach(o => {
      const v = typeof o === 'string' ? o : o.value;
      const l = typeof o === 'string' ? o : o.label;
      const opt = el('option', '', { value: v, text: l });
      if (v === value) opt.selected = true;
      sel.appendChild(opt);
    });
    if (onChange) sel.addEventListener('change', onChange);
    return sel;
  }

  // ════════════════════════════════════════════════════════
  //  FORM GROUP  (label + input + hint)
  //  UI.formGroup('Email', UI.input({...}), 'We never share it.')
  // ════════════════════════════════════════════════════════
  function formGroup(labelText, inputEl, hint = '', { required = false, error = '' } = {}) {
    const group = el('div', 'cui-form-group');

    if (labelText) {
      const lbl = el('label', `cui-label${required ? ' cui-label-required' : ''}`, { text: labelText });
      if (inputEl.id) lbl.setAttribute('for', inputEl.id);
      group.appendChild(lbl);
    }
    group.appendChild(inputEl);
    if (error) group.appendChild(el('div', 'cui-error-msg', { text: error }));
    else if (hint) group.appendChild(el('div', 'cui-hint', { text: hint }));
    return group;
  }

  // ════════════════════════════════════════════════════════
  //  TABLE
  //  UI.table(columns, rows, options)
  //
  //  columns: [{ key, label, render, align }]
  //    → render(value, row): optional custom cell renderer
  //  rows:    array of objects
  //  options: { hoverable, emptyText, onRowClick }
  // ════════════════════════════════════════════════════════
  function table(columns = [], rows = [], {
    hoverable  = true,
    emptyText  = 'No records found.',
    onRowClick = null,
  } = {}) {
    const wrap  = el('div', 'cui-table-wrap');
    const tbl   = el('table', `cui-table${hoverable ? ' cui-table-hover' : ''}`);

    // Head
    const thead = el('thead');
    const headRow = el('tr');
    columns.forEach(col => {
      const th = el('th', '', { text: col.label || col.key });
      if (col.align) th.style.textAlign = col.align;
      headRow.appendChild(th);
    });
    thead.appendChild(headRow);
    tbl.appendChild(thead);

    // Body
    const tbody = el('tbody');
    if (!rows.length) {
      const emptyRow = el('tr');
      const emptyTd  = el('td', '', {
        text: emptyText,
        style: `text-align:center;padding:2.5rem;color:var(--c-gray-400);`,
        colspan: columns.length,
      });
      emptyRow.appendChild(emptyTd);
      tbody.appendChild(emptyRow);
    } else {
      rows.forEach(row => {
        const tr = el('tr');
        if (onRowClick) { tr.style.cursor = 'pointer'; tr.addEventListener('click', () => onRowClick(row)); }
        columns.forEach(col => {
          const td = el('td');
          if (col.render) {
            const rendered = col.render(row[col.key], row);
            if (typeof rendered === 'string') td.innerHTML = rendered;
            else td.appendChild(rendered);
          } else {
            td.textContent = row[col.key] ?? '—';
          }
          if (col.align) td.style.textAlign = col.align;
          tr.appendChild(td);
        });
        tbody.appendChild(tr);
      });
    }
    tbl.appendChild(tbody);
    wrap.appendChild(tbl);
    return wrap;
  }

  // ════════════════════════════════════════════════════════
  //  AVATAR
  //  UI.avatar('JD', 'md', 'primary')
  //  UI.avatar('JD', 'md', { base:'#fce7f3', text:'#9d174d' })
  //
  //  size:  'xs' | 'sm' | 'md' | 'lg' | 'xl'
  //  color: preset string OR { base, text }
  // ════════════════════════════════════════════════════════
  function avatar(initials = '?', size = 'md', color = '') {
    const isCustom = color && typeof color === 'object';
    const cls = `cui-avatar cui-avatar-${size}${!isCustom && color ? ' cui-avatar-' + color : ''}`;
    const a = el('div', cls, { text: initials.toUpperCase().slice(0, 2) });
    if (isCustom) {
      if (color.base) a.style.background = color.base;
      if (color.text) a.style.color      = color.text;
    }
    return a;
  }

  // ════════════════════════════════════════════════════════
  //  DROPDOWN MENU
  //  const dd = UI.dropdown(triggerElement, items)
  //  Items: [{ label, icon, onClick, danger, divider }]
  //    → divider: true → renders a separator line
  // ════════════════════════════════════════════════════════
  function dropdown(triggerEl, items = []) {
    const wrap = el('div', 'cui-dropdown');
    wrap.appendChild(triggerEl);

    const menu = el('div', 'cui-dropdown-menu');
    items.forEach(item => {
      if (item.divider) { menu.appendChild(el('div', 'cui-dropdown-divider')); return; }
      const btn = el('button', `cui-dropdown-item${item.danger ? ' danger' : ''}`);
      if (item.icon) btn.appendChild(el('span', '', { text: item.icon }));
      btn.appendChild(document.createTextNode(item.label || ''));
      if (item.onClick) btn.addEventListener('click', () => { menu.classList.remove('cui-open'); item.onClick(); });
      menu.appendChild(btn);
    });

    wrap.appendChild(menu);

    triggerEl.addEventListener('click', e => {
      e.stopPropagation();
      menu.classList.toggle('cui-open');
    });
    document.addEventListener('click', () => menu.classList.remove('cui-open'));

    return wrap;
  }

  // ════════════════════════════════════════════════════════
  //  TABS
  //  UI.tabs([{ label, content, active }], onChange)
  //
  //  onChange(index, label) — called when tab switches
  //  style: 'line' (default) | 'pills'
  // ════════════════════════════════════════════════════════
  function tabs(tabsArray = [], onChange = null, style = 'line') {
    const wrap    = el('div');
    const tabBar  = el('div', `cui-tabs${style === 'pills' ? ' cui-tabs-pills' : ''}`);
    const content = el('div', 'cui-tabs-content');

    tabsArray.forEach((tab, i) => {
      // Tab button
      const btn = el('button', `cui-tab${tab.active || i === 0 ? ' active' : ''}`, { text: tab.label });
      btn.addEventListener('click', () => {
        tabBar.querySelectorAll('.cui-tab').forEach(t => t.classList.remove('active'));
        content.querySelectorAll('.cui-tab-pane').forEach(p => p.style.display = 'none');
        btn.classList.add('active');
        pane.style.display = '';
        if (onChange) onChange(i, tab.label);
      });
      tabBar.appendChild(btn);

      // Pane
      const pane = el('div', 'cui-tab-pane', { style: tab.active || i === 0 ? '' : 'display:none' });
      if (typeof tab.content === 'string') pane.innerHTML = tab.content;
      else if (tab.content instanceof Node) pane.appendChild(tab.content);
      content.appendChild(pane);
    });

    append(wrap, tabBar, content);
    return wrap;
  }

  // ════════════════════════════════════════════════════════
  //  PROGRESS BAR
  //  UI.progress(75, { color, size, striped, label })
  //  UI.progress(75, { color: { base:'#fce7f3', text:'#9d174d' } })
  //
  //  color: preset string OR { base } for bar fill color
  //  size:  'sm' | '' | 'lg'
  // ════════════════════════════════════════════════════════
  function progress(value = 0, {
    color   = 'primary',
    size    = '',
    striped = false,
    label   = false,
  } = {}) {
    const pct      = Math.min(100, Math.max(0, value));
    const isCustom = color && typeof color === 'object';
    const wrap = el('div', `cui-progress${size ? ' cui-progress-' + size : ''}${striped ? ' cui-progress-striped' : ''}`);
    const barCls = isCustom ? 'cui-progress-bar' : `cui-progress-bar${color !== 'primary' ? ' cui-progress-bar-' + color : ''}`;
    const bar = el('div', barCls);
    bar.style.width = pct + '%';
    if (isCustom && color.base) bar.style.background = color.base;
    if (label) bar.textContent = pct + '%';
    wrap.appendChild(bar);
    return wrap;
  }

  // ════════════════════════════════════════════════════════
  //  DIVIDER
  //  UI.divider()            → plain line
  //  UI.divider('OR')        → line with centered label
  // ════════════════════════════════════════════════════════
  function divider(label = '') {
    if (!label) return el('hr', 'cui-divider');
    const wrap = el('div', 'cui-divider-label');
    wrap.textContent = label;
    return wrap;
  }

  // ════════════════════════════════════════════════════════
  //  EMPTY STATE
  //  UI.empty('📭', 'No results', 'Try a different search.', actionButton)
  // ════════════════════════════════════════════════════════
  function empty(icon = '📭', title = 'Nothing here', message = '', actionEl = null) {
    const wrap = el('div', 'cui-empty');
    wrap.appendChild(el('div', 'cui-empty-icon', { text: icon }));
    wrap.appendChild(el('div', 'cui-empty-title', { text: title }));
    if (message) wrap.appendChild(el('div', 'cui-empty-msg', { text: message }));
    if (actionEl) { wrap.style.paddingBottom = '2rem'; wrap.appendChild(el('div', '', { style: 'margin-top:1rem' })).appendChild(actionEl); }
    return wrap;
  }

  // ════════════════════════════════════════════════════════
  //  SPINNER
  //  UI.spinner()          → default size
  //  UI.spinner('sm')      → small
  //  UI.spinner('lg')      → large
  // ════════════════════════════════════════════════════════
  function spinner(size = '') {
    return el('div', `cui-spinner${size ? ' cui-spinner-' + size : ''}`);
  }

  // ════════════════════════════════════════════════════════
  //  SKELETON LOADER
  //  UI.skeleton('100%', '1rem')    → text-line skeleton
  //  UI.skeleton('48px', '48px')    → square/circle skeleton
  // ════════════════════════════════════════════════════════
  function skeleton(width = '100%', height = '1rem') {
    const s = el('div', 'cui-skeleton');
    s.style.width  = width;
    s.style.height = height;
    return s;
  }

  // ════════════════════════════════════════════════════════
  //  CHIP / TAG
  //  UI.chip('BSCS')
  //  UI.chip('Math', () => remove())
  //  UI.chip('Custom', null, { base:'#fce7f3', text:'#9d174d', border:'#fbcfe8' })
  // ════════════════════════════════════════════════════════
  function chip(text, onClose = null, color = null) {
    const c = el('span', 'cui-chip', { text });
    if (color && typeof color === 'object') {
      if (color.base)   c.style.background = color.base;
      if (color.text)   c.style.color      = color.text;
      if (color.border) c.style.border     = `1px solid ${color.border}`;
    }
    if (onClose) {
      const x = el('button', 'cui-chip-close', { text: '×' });
      x.addEventListener('click', () => { c.remove(); onClose(); });
      c.appendChild(x);
    }
    return c;
  }

  // ════════════════════════════════════════════════════════
  //  NAVBAR  (sticky top bar with hamburger + brand + right actions)
  //  UI.navbar({ brand, brandIcon, sidebar, right, id })
  //
  //  sidebar: pass the object returned by UI.sidebar() so the
  //           hamburger button can call sidebar.toggle()
  //  right:   array of DOM elements placed on the right side
  //           e.g. [UI.button('Logout', { variant:'ghost' })]
  //
  //  Returns { el }  — the <header> element (already in the DOM)
  // ════════════════════════════════════════════════════════
  function navbar({
    brand     = 'CITAS',
    brandIcon = '🎓',
    sidebar   = null,
    pageTitle = '',
    right     = [],
    id        = 'cui-navbar',
  } = {}) {

    const bar = el('header', 'cui-navbar');
    if (id) bar.id = id;

    // ── Left: hamburger + CITAS brand ────────────────────────
    const left = el('div', 'cui-navbar-left');

    // Hamburger
    if (sidebar && typeof sidebar.toggle === 'function') {
      const menuBtn = el('button', 'cui-navbar-menu', {
        'aria-label': 'Toggle navigation',
        'type': 'button',
      });
      for (let i = 0; i < 3; i++) menuBtn.appendChild(el('span', 'cui-navbar-bar'));
      menuBtn.addEventListener('click', () => {
        sidebar.toggle();
        menuBtn.classList.toggle('open',
          sidebar.el.classList.contains('cui-ps-open'));
      });
      sidebar.el.addEventListener('transitionend', () => {
        menuBtn.classList.toggle('open',
          sidebar.el.classList.contains('cui-ps-open'));
      });
      left.appendChild(menuBtn);
    }

    // Brand
    const brandWrap = el('div', 'cui-navbar-brand');
    brandWrap.appendChild(el('span', 'cui-navbar-brand-icon', { text: brandIcon }));
    brandWrap.appendChild(document.createTextNode(brand));
    left.appendChild(brandWrap);

    // Divider
    left.appendChild(el('div', 'cui-navbar-divider'));

    // ── Center: current page name ────────────────────────────
    const center = el('div', 'cui-navbar-center');
    if (pageTitle) center.textContent = pageTitle;

    // ── Right: actions ───────────────────────────────────────
    const rightWrap = el('div', 'cui-navbar-right');
    right.forEach(item => {
      if (item instanceof Node) rightWrap.appendChild(item);
    });

    bar.appendChild(left);
    bar.appendChild(center);
    bar.appendChild(rightWrap);

    return { el: bar };
  }

  // ════════════════════════════════════════════════════════
  //  SIDEBAR PANEL  (button-based, orange gradient — CITAS theme)
  //  const nav = UI.sidebarPanel({ brand, brandIcon, items, footer, id })
  //  nav.open()    → slides in
  //  nav.close()   → slides out
  //  nav.toggle()  → toggles — pass this instance to UI.navbar({ sidebar: nav })
  //  nav.el        → the <aside> DOM element
  //
  //  items: [{ label, icon, onClick, active, section, badge }]
  //    section:  true  → renders as a section divider label
  //    active:   true  → highlights the button as the current page
  //    badge:    string/number shown as a small pill on the right
  //    onClick:  function called when button is clicked
  // ════════════════════════════════════════════════════════
  function sidebarPanel({
    brand     = 'CITAS',
    brandIcon = '🎓',
    items     = [],
    footer    = null,
    id        = 'cui-sidebar-panel',
  } = {}) {

    // ── dim overlay (closes on click) ──────────────────────
    const bgOverlay = el('div', 'cui-sidebar-overlay');
    bgOverlay.addEventListener('click', close);
    document.body.appendChild(bgOverlay);

    // ── panel ──────────────────────────────────────────────
    const panel = el('aside', 'cui-sp cui-sp-hidden');
    if (id) panel.id = id;

    // ── brand header ───────────────────────────────────────
    const brandWrap = el('div', 'cui-sp-brand');
    const brandIconEl = el('span', 'cui-sp-brand-icon', { text: brandIcon });
    const brandNameEl = el('span', 'cui-sp-brand-name', { text: brand });
    brandWrap.appendChild(brandIconEl);
    brandWrap.appendChild(brandNameEl);
    panel.appendChild(brandWrap);

    // ── nav buttons ────────────────────────────────────────
    const nav = el('nav', 'cui-sp-nav');
    const btnMap = {};   // label → button element, for setActive()

    items.forEach(item => {
      // Section divider label
      if (item.section) {
        nav.appendChild(el('div', 'cui-sp-section', { text: item.label }));
        return;
      }

      const btn = el('button', `cui-sp-btn${item.active ? ' active' : ''}`, {
        type: 'button',
      });

      // Icon
      if (item.icon) btn.appendChild(el('span', 'cui-sp-btn-icon', { text: item.icon }));

      // Label
      btn.appendChild(el('span', 'cui-sp-btn-label', { text: item.label }));

      // Optional badge pill on the right
      if (item.badge !== undefined && item.badge !== null) {
        btn.appendChild(el('span', 'cui-sp-btn-badge', { text: String(item.badge) }));
      }

      // Auto-highlight the clicked button
      btn.addEventListener('click', () => {
        setActive(item.label);
        if (item.onClick) item.onClick(btn);
      });

      btnMap[item.label] = btn;
      nav.appendChild(btn);
    });

    panel.appendChild(nav);

    // ── footer slot ────────────────────────────────────────
    if (footer) {
      const footerWrap = el('div', 'cui-sp-footer');
      if (footer instanceof Node) footerWrap.appendChild(footer);
      else footerWrap.innerHTML = String(footer);
      panel.appendChild(footerWrap);
    }

    document.body.appendChild(panel);

    // ── open / close / toggle ──────────────────────────────
    function open() {
      panel.classList.remove('cui-sp-hidden');
      bgOverlay.classList.add('cui-open');
    }
    function close() {
      panel.classList.add('cui-sp-hidden');
      bgOverlay.classList.remove('cui-open');
    }
    function toggle() {
      panel.classList.contains('cui-sp-hidden') ? open() : close();
    }

    // ── setActive(label) ───────────────────────────────────
    // Moves the highlight to whichever button matches the label.
    // Called automatically on click, or manually whenever you need
    // to change the selection from outside the sidebar.
    function setActive(label) {
      Object.values(btnMap).forEach(b => b.classList.remove('active'));
      if (btnMap[label]) btnMap[label].classList.add('active');
    }

    return { el: panel, open, close, toggle, setActive };
  }

  // ════════════════════════════════════════════════════════
  //  PAGE SIDEBAR  (persistent on desktop, drawer on mobile)
  //  const nav = UI.pageSidebar({ brand, brandIcon, items, footer })
  //  nav.toggle()   → use with UI.navbar({ sidebar: nav })
  //  nav.setActive(label) → highlight a nav button
  //
  //  items: [{ label, icon, onClick, active, section, badge }]
  //
  //  On desktop (>= 900px): always visible, content shifts right.
  //  On mobile  (<  900px): hidden drawer, hamburger opens it.
  // ════════════════════════════════════════════════════════
  function pageSidebar({
    brand     = 'CITAS',
    brandIcon = '🎓',
    items     = [],
    footer    = null,
    id        = 'cui-page-sidebar',
  } = {}) {

    // Mobile dim overlay — appended to body so it covers everything
    const overlay = el('div', 'cui-ps-overlay');
    overlay.addEventListener('click', close);
    document.body.appendChild(overlay);

    // Sidebar panel
    const panel = el('aside', 'cui-ps');
    if (id) panel.id = id;

    // Brand
    const brandWrap = el('div', 'cui-ps-brand');
    brandWrap.appendChild(el('span', 'cui-ps-brand-icon', { text: brandIcon }));
    brandWrap.appendChild(el('span', 'cui-ps-brand-name', { text: brand }));
    panel.appendChild(brandWrap);

    // Nav
    const nav    = el('nav', 'cui-ps-nav');
    const btnMap = {};

    items.forEach(item => {
      if (item.section) {
        nav.appendChild(el('div', 'cui-ps-section', { text: item.label }));
        return;
      }
      const btn = el('button', `cui-ps-btn${item.active ? ' active' : ''}`, { type: 'button' });
      if (item.icon)  btn.appendChild(el('span', 'cui-ps-btn-icon',  { text: item.icon }));
      btn.appendChild(el('span', 'cui-ps-btn-label', { text: item.label }));
      if (item.badge != null) btn.appendChild(el('span', 'cui-ps-btn-badge', { text: String(item.badge) }));

      btn.addEventListener('click', () => {
        setActive(item.label);
        if (window.innerWidth < 900) close();
        if (item.onClick) item.onClick(btn);
      });

      btnMap[item.label] = btn;
      nav.appendChild(btn);
    });

    panel.appendChild(nav);

    if (footer) {
      const footerWrap = el('div', 'cui-ps-footer');
      if (footer instanceof Node) footerWrap.appendChild(footer);
      else footerWrap.innerHTML = String(footer);
      panel.appendChild(footerWrap);
    }

    // ── Key fix: insert sidebar as first child of .cui-page-layout ──
    // This keeps it inside the flex row so desktop layout works.
    // Falls back to body prepend if the layout div isn't found yet.
    function mountPanel() {
      const layout = document.querySelector('.cui-page-layout');
      if (layout) {
        layout.insertBefore(panel, layout.firstChild);
      } else {
        document.body.prepend(panel);
      }
    }

    // Mount immediately if DOM is ready, else wait
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', mountPanel);
    } else {
      mountPanel();
    }

    function open() {
      panel.classList.add('cui-ps-open');
      overlay.classList.add('cui-ps-overlay-open');
    }
    function close() {
      panel.classList.remove('cui-ps-open');
      overlay.classList.remove('cui-ps-overlay-open');
    }
    function toggle() {
      panel.classList.contains('cui-ps-open') ? close() : open();
    }
    function setActive(label) {
      Object.values(btnMap).forEach(b => b.classList.remove('active'));
      if (btnMap[label]) btnMap[label].classList.add('active');
    }

    return { el: panel, open, close, toggle, setActive };
  }

  // ════════════════════════════════════════════════════════
  //  PROFILE CARD
  //  UI.profileCard({ name, role, email, dept, school, joined, avatar })
  //
  //  Renders a horizontal card with avatar + user details.
  //  avatar: emoji or initials string (shown as circle if 1–2 chars)
  // ════════════════════════════════════════════════════════
  function profileCard({
    name   = 'User',
    role   = '',
    email  = '',
    dept   = '',
    school = '',
    joined = '',
    avatar = '',
  } = {}) {
    const wrap = el('div', 'cui-profile-card');

    // Avatar
    const avi = el('div', 'cui-profile-avatar');
    avi.textContent = avatar || name.slice(0, 2).toUpperCase();
    wrap.appendChild(avi);

    // Info
    const info = el('div', 'cui-profile-info');
    info.appendChild(el('div', 'cui-profile-name',  { text: name }));
    if (role)   info.appendChild(el('span', 'cui-profile-role-badge', { text: role }));

    const details = el('div', 'cui-profile-details');
    if (email)  details.appendChild(_profileDetail('✉️', email));
    if (dept)   details.appendChild(_profileDetail('🏫', dept));
    if (school) details.appendChild(_profileDetail('🎓', school));
    if (joined) details.appendChild(_profileDetail('📅', 'Joined ' + joined));
    info.appendChild(details);

    wrap.appendChild(info);
    return wrap;
  }
  function _profileDetail(icon, text) {
    const row = el('div', 'cui-profile-detail-row');
    row.appendChild(el('span', 'cui-profile-detail-icon', { text: icon }));
    row.appendChild(el('span', '', { text }));
    return row;
  }

  // ════════════════════════════════════════════════════════
  //  UPLOAD ZONE  (drag-and-drop file input)
  //  UI.uploadZone({ label, hint, accept, onFile })
  //
  //  onFile(file, input) → called when user picks or drops a file
  //  Returns the wrapper element; access the <input> via .querySelector('input')
  // ════════════════════════════════════════════════════════
  function uploadZone({
    label  = 'Click to browse or drag & drop',
    hint   = 'PDF, DOC, DOCX, PNG, JPG — max 10 MB',
    accept = '.pdf,.doc,.docx,.png,.jpg,.jpeg',
    id     = '',
    name   = 'file',
    onFile = null,
  } = {}) {
    const wrap = el('div', 'cui-upload-zone');

    const inp = el('input', 'cui-upload-input');
    inp.type   = 'file';
    inp.accept = accept;
    inp.name   = name;
    if (id) inp.id = id;

    const icon    = el('div', 'cui-upload-icon',  { text: '📁' });
    const labelEl = el('div', 'cui-upload-label', { text: label });
    const hintEl  = el('div', 'cui-upload-hint',  { text: hint });

    append(wrap, inp, icon, labelEl, hintEl);

    // Click to open file picker
    wrap.addEventListener('click', () => inp.click());

    // Drag events
    wrap.addEventListener('dragover',  e => { e.preventDefault(); wrap.classList.add('cui-upload-zone-over'); });
    wrap.addEventListener('dragleave', ()  => wrap.classList.remove('cui-upload-zone-over'));
    wrap.addEventListener('drop', e => {
      e.preventDefault();
      wrap.classList.remove('cui-upload-zone-over');
      const file = e.dataTransfer.files[0];
      if (file) {
        _updateUploadLabel(labelEl, file.name);
        if (onFile) onFile(file, inp);
      }
    });

    // File picker change
    inp.addEventListener('change', () => {
      if (inp.files[0]) {
        _updateUploadLabel(labelEl, inp.files[0].name);
        if (onFile) onFile(inp.files[0], inp);
      }
    });

    return wrap;
  }
  function _updateUploadLabel(labelEl, filename) {
    labelEl.textContent = '📎 ' + filename;
    labelEl.style.color = '#EA580C';
    labelEl.style.fontWeight = '600';
  }

  // ════════════════════════════════════════════════════════
  //  STAT ROW  (horizontal mini-stats strip, fits inside a card)
  //  UI.statRow([ { value, label, color } ])
  //
  //  Lighter alternative to a grid of statCards — sits inline.
  //  color: 'orange' | 'green' | 'blue' | 'red' | hex string
  // ════════════════════════════════════════════════════════
  function statRow(stats = []) {
    const wrap = el('div', 'cui-stat-row');
    stats.forEach((s, i) => {
      if (i > 0) wrap.appendChild(el('div', 'cui-stat-row-div'));
      const item = el('div', 'cui-stat-row-item');
      const val  = el('div', 'cui-stat-row-value', { text: String(s.value) });
      if (s.color) val.style.color = s.color;
      item.appendChild(val);
      item.appendChild(el('div', 'cui-stat-row-label', { text: s.label }));
      wrap.appendChild(item);
    });
    return wrap;
  }

  // ════════════════════════════════════════════════════════
  //  INFO ROW  (icon + label + value — one detail line)
  //  UI.infoRow('🏢', 'Company', 'Acme Corp')
  // ════════════════════════════════════════════════════════
  function infoRow(icon, label, value) {
    const row = el('div', 'cui-info-row');
    row.appendChild(el('span', 'cui-info-row-icon', { text: icon }));
    const body = el('div', 'cui-info-row-body');
    body.appendChild(el('div', 'cui-info-row-label', { text: label }));
    body.appendChild(el('div', 'cui-info-row-value', { text: value || '—' }));
    row.appendChild(body);
    return row;
  }

  // ════════════════════════════════════════════════════════
  //  COMPANY INFO CARD
  //  UI.companyInfoCard({ company, address, supervisor, phone,
  //                       email, startDate, endDate, position })
  //
  //  Displays an internship host company's details in a card.
  // ════════════════════════════════════════════════════════
  function companyInfoCard({
    company    = '',
    address    = '',
    supervisor = '',
    phone      = '',
    email      = '',
    startDate  = '',
    endDate    = '',
    position   = '',
  } = {}) {
    const wrap = el('div', 'cui-company-card');

    // Header strip
    const header = el('div', 'cui-company-header');
    const logoCircle = el('div', 'cui-company-logo', { text: company.charAt(0).toUpperCase() || '🏢' });
    const headerInfo = el('div');
    headerInfo.appendChild(el('div', 'cui-company-name',     { text: company  || 'Company Name' }));
    headerInfo.appendChild(el('div', 'cui-company-position', { text: position || 'Intern' }));
    append(header, logoCircle, headerInfo);
    wrap.appendChild(header);

    // Info rows
    const grid = el('div', 'cui-company-grid');
    if (address)    grid.appendChild(infoRow('📍', 'Address',    address));
    if (supervisor) grid.appendChild(infoRow('👤', 'Supervisor', supervisor));
    if (phone)      grid.appendChild(infoRow('📞', 'Phone',      phone));
    if (email)      grid.appendChild(infoRow('✉️', 'Email',      email));
    if (startDate)  grid.appendChild(infoRow('📅', 'Start Date', startDate));
    if (endDate)    grid.appendChild(infoRow('🏁', 'End Date',   endDate));
    wrap.appendChild(grid);

    return wrap;
  }

  // ── Expose public API ──────────────────────────────────────
  return {
    button, card, statCard, statRow,
    modal, sidebar, topbar, navbar, sidebarPanel, pageSidebar,
    badge, alert, toast,
    input, textarea, select, formGroup,
    table, avatar, dropdown,
    tabs, progress, divider,
    profileCard, uploadZone, companyInfoCard, infoRow,
    empty, spinner, skeleton, chip,
  };

})();
