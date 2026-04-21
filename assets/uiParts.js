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

  // ════════════════════════════════════════════════════════
  //  BUTTON
  //  UI.button('Save', { variant, size, icon, block, disabled, onClick })
  //
  //  variant: 'primary' | 'success' | 'danger' | 'warning' |
  //           'outline' | 'ghost' | 'link'         (default: 'primary')
  //  size:    'xs' | 'sm' | 'md' | 'lg' | 'xl'    (default: 'md')
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
  color    = null, // 👈 NEW
  onClick  = null,
} = {}) {

  const btn = el(
    'button',
    `cui-btn cui-btn-${variant} cui-btn-${size}${block ? ' cui-btn-block' : ''}`,
    { type }
  );

  if (id) btn.id = id;

  if (icon) {
    const iconSpan = el('span', '', { text: icon });
    btn.appendChild(iconSpan);
  }

  btn.appendChild(document.createTextNode(text));

  if (disabled) btn.disabled = true;
  if (onClick) btn.addEventListener('click', onClick);

  // 🔥 ADVANCED COLOR SYSTEM
  if (color) {
    btn.classList.add('cui-btn-custom');

    // support string OR object
    if (typeof color === 'string') {
      btn.style.setProperty('--btn-bg', color);
      btn.style.setProperty('--btn-hover', color);
      btn.style.setProperty('--btn-text', '#fff');
    } else {
      if (color.base)  btn.style.setProperty('--btn-bg', color.base);
      if (color.hover) btn.style.setProperty('--btn-hover', color.hover);
      if (color.text)  btn.style.setProperty('--btn-text', color.text);
    }
  }

  return btn;
}

  // ════════════════════════════════════════════════════════
  //  CARD
  //  UI.card({ title, subtitle, body, footer, hoverable, id })
  //
  //  title:    string or element for card header
  //  subtitle: small text under title
  //  body:     string, HTML, or DOM element for the card body
  //  footer:   array of elements appended to card footer
  //  headerActions: element(s) placed right side of header
  //  hoverable: true → hover lift effect
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
  } = {}) {
    const wrap = el('div', `cui-card${hoverable ? ' cui-card-hover' : ''}`);
    if (id) wrap.id = id;

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
  //
  //  color: 'primary' | 'success' | 'danger' | 'warning' | 'info'
  // ════════════════════════════════════════════════════════
  function statCard(value, label, icon = '📊', color = 'primary') {
    const wrap = el('div', 'cui-stat-card');
    const iconEl = el('div', `cui-stat-icon cui-stat-icon-${color}`, { text: icon });
    const info = el('div');
    info.appendChild(el('div', 'cui-stat-value', { text: value }));
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
  //  UI.badge('Active', 'success', { dot: true })
  //
  //  color: 'primary' | 'success' | 'danger' | 'warning' | 'info' | 'gray'
  // ════════════════════════════════════════════════════════
  function badge(text, color = 'primary', { dot = false } = {}) {
    const b = el('span', `cui-badge cui-badge-${color}${dot ? ' cui-badge-dot' : ''}`, { text });
    return b;
  }

  // ════════════════════════════════════════════════════════
  //  ALERT
  //  UI.alert('Saved!', 'success', { title, icon, closeable })
  //
  //  type:     'success' | 'danger' | 'warning' | 'info'
  //  closeable: true → adds an X button that removes the alert
  // ════════════════════════════════════════════════════════
  function alert(message, type = 'info', {
    title     = '',
    icon      = '',
    closeable = true,
    onClose   = null,
  } = {}) {
    const icons = { success: '✅', danger: '❌', warning: '⚠️', info: 'ℹ️' };
    const wrap = el('div', `cui-alert cui-alert-${type}`);

    wrap.appendChild(el('span', 'cui-alert-icon', { text: icon || icons[type] }));

    const body = el('div', 'cui-alert-body');
    if (title) body.appendChild(el('div', 'cui-alert-title', { text: title }));
    body.appendChild(document.createTextNode(message));
    wrap.appendChild(body);

    if (closeable) {
      const closeBtn = el('button', 'cui-alert-close', { text: '×' });
      closeBtn.addEventListener('click', () => {
        wrap.remove();
        if (onClose) onClose();
      });
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
  //  UI.input({ type, placeholder, value, id, name, required, disabled })
  //
  //  type: 'text' | 'email' | 'password' | 'number' | 'date' | etc.
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
    onChange    = null,
  } = {}) {
    const inp = el('input', `cui-input${error ? ' cui-input-error' : ''}`);
    inp.type = type;
    if (placeholder) inp.placeholder = placeholder;
    if (value)       inp.value       = value;
    if (id)          inp.id          = id;
    if (name)        inp.name        = name;
    if (required)    inp.required    = true;
    if (disabled)    inp.disabled    = true;
    if (onChange)    inp.addEventListener('input', onChange);
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
  //
  //  initials: 1–2 characters
  //  size:  'xs' | 'sm' | 'md' | 'lg' | 'xl'
  //  color: 'primary' | 'success' | 'danger' | 'warning' | 'gray'
  // ════════════════════════════════════════════════════════
  function avatar(initials = '?', size = 'md', color = '') {
    return el('div', `cui-avatar cui-avatar-${size}${color ? ' cui-avatar-' + color : ''}`, {
      text: initials.toUpperCase().slice(0, 2)
    });
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
  //
  //  value: 0–100
  //  color: 'primary' | 'success' | 'danger' | 'warning'
  //  size:  'sm' | '' | 'lg'
  // ════════════════════════════════════════════════════════
  function progress(value = 0, {
    color   = 'primary',
    size    = '',
    striped = false,
    label   = false,
  } = {}) {
    const pct = Math.min(100, Math.max(0, value));
    const wrap = el('div', `cui-progress${size ? ' cui-progress-' + size : ''}${striped ? ' cui-progress-striped' : ''}`);
    const bar  = el('div', `cui-progress-bar${color !== 'primary' ? ' cui-progress-bar-' + color : ''}`);
    bar.style.width = pct + '%';
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
  //  UI.chip('BSCS')                   → static chip
  //  UI.chip('Math', () => remove())   → dismissible chip
  // ════════════════════════════════════════════════════════
  function chip(text, onClose = null) {
    const c = el('span', 'cui-chip', { text });
    if (onClose) {
      const x = el('button', 'cui-chip-close', { text: '×' });
      x.addEventListener('click', () => { c.remove(); onClose(); });
      c.appendChild(x);
    }
    return c;
  }

  // ── Expose public API ──────────────────────────────────────
  return {
    button, card, statCard,
    modal, sidebar, topbar,
    badge, alert, toast,
    input, textarea, select, formGroup,
    table, avatar, dropdown,
    tabs, progress, divider,
    empty, spinner, skeleton, chip,
  };

})();
//window.UI = UI;