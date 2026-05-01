const Dashboard = {

  build() {
    const sec  = document.getElementById('sec-dashboard');
    const hour = new Date().getHours();
    const greeting = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening';

    sec.appendChild(Helpers.pageHeader(
      `${greeting}, ${INTERN.name.split(' ')[0]}! 👋`,
      new Date().toLocaleDateString('en-US', { weekday:'long', year:'numeric', month:'long', day:'numeric' })
    ));

    sec.appendChild( this.statStrip()  );
    sec.appendChild( this.progressBar());

    // Top row: announcements + progress insights
    const row1 = document.createElement('div');
    row1.className = 'pg-grid-2 mt-lg';
    row1.appendChild( this.announcementsCard()      );
    row1.appendChild( this.progressInsightsCard()   );
    sec.appendChild(row1);

    // Bottom row: profile preview + recent attendance
    const row2 = document.createElement('div');
    row2.className = 'pg-grid-2 mt-lg';
    row2.appendChild( this.profilePreviewCard()   );
    row2.appendChild( this.recentAttendanceCard() );
    sec.appendChild(row2);

    // Draw chart after DOM is attached
    setTimeout(() => this.drawHoursTrendChart(), 50);
  },

  // Horizontal 4-number stat strip
  statStrip() {
    const pct = Math.round(InternData.internships[0].total_hours / INTERN.reqHours * 100);
    return UI.statRow([
      { value: InternData.internships[0].total_hours + 'h',   label: 'Hours Rendered',    color: '#EA580C' },
      { value: InternData.internships[0].days_present,          label: 'Days Present',      color: '#10B981' },
      { value: InternData.internships[0].reports_submitted,     label: 'Reports Submitted', color: '#6366F1' },
      { value: pct + '%',                   label: 'Completion',        color: '#EA580C' },
    ]);
  },

  // Slim progress bar with label underneath
  progressBar() {
    const pct  = Math.round(InternData.internships[0].total_hours / INTERN.reqHours * 100);
    const wrap = document.createElement('div');
    wrap.style.marginBottom = '1rem';
    wrap.appendChild(UI.progress(pct, { color: { base: '#EA580C' }, label: true }));
    const sub = document.createElement('p');
    sub.style.cssText = 'font-size:.75rem;color:#9A6647;margin-top:.35rem';
    sub.textContent   = `${InternData.internships[0].total_hours}h of ${INTERN.reqHours}h required · ${INTERN.reqHours - INTERN.totalHours}h remaining`;
    wrap.appendChild(sub);
    return wrap;
  },

  // 3-column row: announcements · profile preview · recent attendance
  bottomRow() {
    const row = document.createElement('div');
    row.className = 'pg-grid-3 mt-lg';
    row.appendChild( this.announcementsCard()   );
    row.appendChild( this.profilePreviewCard()  );
    row.appendChild( this.recentAttendanceCard());
    return row;
  },
  // Progress insights card with projected finish + hours trend chart
  progressInsightsCard() {
    const pct        = Math.round(INTERN.totalHours / INTERN.reqHours * 100);
    const remaining  = INTERN.reqHours - INTERN.totalHours;
    const daysIn     = INTERN.daysPresent;
    const avgPerDay  = daysIn > 0 ? (INTERN.totalHours / daysIn) : 8;
    const daysNeeded = Math.ceil(remaining / avgPerDay);

    // Projected finish date from today
    const projDate = new Date();
    let workDaysAdded = 0;
    while (workDaysAdded < daysNeeded) {
      projDate.setDate(projDate.getDate() + 1);
      const day = projDate.getDay();
      if (day !== 0 && day !== 6) workDaysAdded++; // skip weekends
    }
    const projStr = projDate.toLocaleDateString('en-US', { month:'long', day:'numeric', year:'numeric' });

    // On pace check: expected ~70% by week 14 of 20-week internship
    const expectedPct = 70;
    const onPace      = pct >= expectedPct;

    const body = document.createElement('div');

    // On-pace badge
    const paceBadge = document.createElement('div');
    paceBadge.style.cssText = `display:inline-flex;align-items:center;gap:.4rem;padding:.4rem .9rem;border-radius:20px;font-size:.82rem;font-weight:700;margin-bottom:1rem;background:${onPace?'#D1FAE5':'#FEE2E2'};color:${onPace?'#065F46':'#991B1B'}`;
    paceBadge.innerHTML = onPace
      ? '✅ You are on schedule'
      : `⚠️ ${expectedPct - pct}% behind the expected pace`;
    body.appendChild(paceBadge);

    // Key insights
    [
      ['📅', 'Projected finish',    projStr],
      ['⚡', 'Avg hours / day',     avgPerDay.toFixed(1) + 'h'],
      ['📆', 'Work days remaining', daysNeeded + ' days at current pace'],
      ['📄', 'Reports submitted',   INTERN.reportsSubmitted + ' of ~14 expected'],
    ].forEach(([icon, label, value]) => {
      const row = document.createElement('div');
      row.style.cssText = 'display:flex;align-items:flex-start;gap:.5rem;padding:.45rem 0;border-bottom:1px solid #FFF0E0;font-size:.82rem';
      row.innerHTML = `
        <span style="opacity:.55;flex-shrink:0;margin-top:.1rem">${icon}</span>
        <span style="color:#9A6647;min-width:130px;flex-shrink:0">${label}</span>
        <span style="font-weight:600;color:#1A0A00">${value}</span>`;
      body.appendChild(row);
    });

    // Mini hours trend chart
    const chartLabel = document.createElement('p');
    chartLabel.style.cssText = 'font-size:.75rem;font-weight:700;color:#9A6647;text-transform:uppercase;letter-spacing:.05em;margin:1rem 0 .4rem';
    chartLabel.textContent   = 'Your Daily Hours (Last 6 Days)';
    body.appendChild(chartLabel);

    const canvas = document.createElement('canvas');
    canvas.id     = 'chart-intern-trend';
    canvas.height = 80;
    body.appendChild(canvas);

    return UI.card({ title: '📈 My Progress Insights', body });
  },

  // Mini sparkline-style chart for intern's recent daily hours
  drawHoursTrendChart() {
    const ctx = document.getElementById('chart-intern-trend');
    if (!ctx || typeof Chart === 'undefined') return;

    const labels = ATTENDANCE_LOG.slice().reverse().map(l => l.date.split(',')[0]);
    const hours  = ATTENDANCE_LOG.slice().reverse().map(l => l.hours);

    new Chart(ctx, {
      type: 'line',
      data: {
        labels,
        datasets: [{
          data:                hours,
          borderColor:         '#EA580C',
          backgroundColor:     'rgba(234,88,12,.1)',
          pointBackgroundColor:'#EA580C',
          pointRadius:         3,
          pointHoverRadius:    5,
          tension:             0.3,
          fill:                true,
        }],
      },
      options: {
        responsive: true,
        plugins:    { legend: { display: false }, tooltip: {
          callbacks: { label: c => ` ${c.parsed.y}h` },
        }},
        scales: {
          y: {
            min:   7, max: 10,
            ticks: { callback: v => v+'h', font:{ size:10 } },
            grid:  { color: '#FFF0E0' },
          },
          x: { grid: { display:false }, ticks: { font:{ size:9 }, maxRotation:30 } },
        },
      },
    });
  },

  // Pinned-first announcement list
  announcementsCard() {
    const body = document.createElement('div');
    [...ANNOUNCEMENTS]
      .sort((a, b) => b.pinned - a.pinned)
      .forEach(a => body.appendChild(Helpers.annItem(a)));
    if (!ANNOUNCEMENTS.length)
      body.appendChild(UI.empty('📭', 'No announcements', 'Check back later.'));

    return UI.card({
      title: '📢 Announcements',
      headerActions: UI.badge(
        ANNOUNCEMENTS.filter(a => a.pinned).length + ' pinned', 'warning'
      ),
      body,
    });
  },

  // Quick company + supervisor snapshot with "View Profile" button
  profilePreviewCard() {
    const body = document.createElement('div');
    [
      ['🏢', INTERN.company],
      ['📍', INTERN.address.split(',').slice(-2).join(',').trim()],
      ['👤', 'Supervisor: ' + INTERN.supervisor],
      ['📅', INTERN.startDate + ' → ' + INTERN.endDate],
    ].forEach(([icon, text]) => {
      const row = document.createElement('div');
      row.style.cssText = 'display:flex;gap:.5rem;align-items:flex-start;padding:.45rem 0;border-bottom:1px solid #FFF0E0;font-size:.82rem;color:#6B3A1F';
      row.innerHTML = `<span style="opacity:.55;flex-shrink:0">${icon}</span><span>${text}</span>`;
      body.appendChild(row);
    });

    const btn = UI.button('View Full Profile →', {
      block: true,
      color: { base:'#FFF7ED', hover:'#FFEDD5', text:'#EA580C', border:'#FED7AA' },
      onClick: () => navigate('profile'),
    });
    btn.style.marginTop = '.85rem';
    body.appendChild(btn);

    return UI.card({ title: '🏢 My Internship', body });
  },

  // Last 4 attendance rows
  recentAttendanceCard() {
    return UI.card({
      title: '🕐 Recent Attendance',
      padding: false,
      headerActions: UI.button('View all', {
        variant: 'ghost', size: 'sm',
        color: { base:'#FFF7ED', hover:'#FFEDD5', text:'#EA580C', border:'#FED7AA' },
        onClick: () => navigate('attendance'),
      }),
      body: UI.table([
        { key:'date',    label:'Date',  render: v => `<strong>${v}</strong>` },
        { key:'timeIn',  label:'In'   },
        { key:'timeOut', label:'Out'  },
        { key:'hours',   label:'Hrs', align:'center',
          render: v => `<span style="color:#EA580C;font-weight:700">${v}h</span>` },
      ], ATTENDANCE_LOG.slice(0, 4), { hoverable: true }),
    });
  },
};

