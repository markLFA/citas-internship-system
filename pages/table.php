<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CITAS UI — Component Reference</title>
  <link rel="stylesheet" href="../assets/uiParts.css">
</head>
<body>
<script src="../assets/uiParts.js"></script>
  <div id="meh"></div>

  <div id="my-table"></div>

  <script>

    // 1. Define your columns
    const columns = [
      { key: 'name',   label: 'Name' },
      { key: 'dept',   label: 'Department' },
      { key: 'hours',  label: 'Hours', align: 'center' },
      { key: 'status', label: 'Status', align: 'center',
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
      { name: 'Juan dela Cruz', dept: 'BSCS', hours: '128h', status: 'Approved' },
      { name: 'Maria Santos',   dept: 'BSIT', hours: '96h',  status: 'Pending'  },
      { name: 'Pedro Reyes',    dept: 'BSBA', hours: '64h',  status: 'Revision' },
    ];

    // 3. Build the table and insert it into the page
    const table = UI.table(columns, rows, {
      hoverable:  true,
      emptyText:  'No interns found.',
      onRowClick: (row) => console.log('Row clicked:', row),
    });

    document.getElementById('my-table').appendChild(table);
    const alert = UI.alert('Heads up!', 'info', { color: { base:'#fffbeb', text:'#78350f' } })
        document.getElementById('meh').appendChild(alert);
    const statCard = UI.statCard('42', 'Custom Stat', '', { base:'#fdf2f8', text:'#9d174d' })
        document.getElementById('meh').appendChild(statCard);

  </script>
</body>
</html>