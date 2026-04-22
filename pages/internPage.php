<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CITAS UI — Component Reference</title>
  <link rel="stylesheet" href="../assets/uiParts.css">
  <style> 
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
  </style>
</head>
<body>
<div id = "stats-panel" class="stats-grid"></div>
<div id = "main" class="stats-grid">
  <div id="announcement" >
    <h2>📢 Announcements</h2></br>
  </div>
</div>

<div id="app"></div>
<div id="my-table" style="width: 70%;"></div>
</div>
<script src="../assets/uiParts.js"></script>
<script>
  function statsPanel () {
    const hoursStat = UI.statCard('0.1', 'Total Hours Rendered', '🕐', { base:'#fdf2f8', text:'#9d174d' })
    document.getElementById('stats-panel').appendChild(hoursStat);
    const daysStats = UI.statCard('1', 'Days Present', '📅', { base:'#fdf2f8', text:'#9d174d' })
    document.getElementById('stats-panel').appendChild(daysStats  );
    const submisionStats = UI.statCard('2', 'Reports Submited', '📄', { base:'#fdf2f8', text:'#9d174d' })
    document.getElementById('stats-panel').appendChild(submisionStats);
    const statusStats = UI.statCard('Out', 'Today\'s Status', '🔴', { base:'#fdf2f8', text:'#9d174d' })
    document.getElementById('stats-panel').appendChild(statusStats);
  }
  statsPanel();
  function anouncementPanel () {

    card = UI.card({
      title: 'jdnfjfj',
      body: '📭',
      subtitle: 'No announcements yet',
      color: { base:'#fef3c7', text:'#92400e' }
    });
    document.getElementById('announcement').appendChild(card);
  }
  anouncementPanel();


const nav = UI.sidebar({
  brand: 'CITAS',
  links: [
    { label: 'Dashboard', icon: '🏠', active: true },
    { label: 'Reports', icon: '📊' }
  ]
});


// 2. Pass it into navbar — hamburger is auto-wired
UI.navbar({
  brand: 'CITAS Internship',
  sidebar: nav,          // ← this connects the button
  right: [UI.button('Profile', { variant: 'ghost', size: 'sm' }), UI.button('Logout', { variant: 'ghost', size: 'sm' })],
});



</script>

<script>
function renderTable() { 
    // 1. Define your columns
    const columns = [
      { key: 'date',   label: 'Date' },
      { key: 'timein',   label: 'Time In' },
      { key: 'timeout',  label: 'Time Out', align: 'center' },
      { key: 'hours', label: 'Hours', align: 'center',
        // render() lets you customize how a cell looks
        render: (value) => UI.badge(value, {
          Approved: 'success',
          Pending:  'warning',
          Revision: 'danger',
        }[value] || 'gray')
      },
      { key: 'action', label: '', align: 'center',
        render: (value, row) => UI.button('View', {
          variant: 'ghost',
          size: 'sm',
          onClick: () => alert('Clicked: ' + row.name)
        })
      },
    ];

    // 2. Define your data rows
    const rows = [
      { date: 'april 32', timein: '9:00 am', timeout: '5: pm', hours: '8' },
      { date: 'april 32', timein: '9:00 am', timeout: '5: pm', hours: '8' },
      { date: 'april 32', timein: '9:00 am', timeout: '5: pm', hours: '8' },
    ];

    // 3. Build the table and insert it into the page
    const table = UI.table(columns, rows, {
      hoverable:  true,
      emptyText:  'No interns found.',
      onRowClick: (row) => console.log('Row clicked:', row),
    });

    document.getElementById('main').appendChild(table);
  }
  renderTable();
</script>


</body>
</html>