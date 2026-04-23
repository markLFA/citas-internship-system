import { UI } from '../assets/uiParts.js';
 function initializePage () {

    console.log("Initializing dashboard page...");
    navbar2();
    DashboardPage()
}
initializePage ()
function navbar2 () {
  // 1. Create the panel
  const nav = UI.sidebarPanel({
    brand:     'CITAS',
    brandIcon: '🎓',
    items: [
      { section: true, label: 'Main' },
      { label: 'Dashboard',  icon: '🏠', active: true, onClick: () => {
        DashboardPage () 
      } },
      { label: 'Attendance', icon: '🕐', onClick: () => {} },
      { label: 'Reports',    icon: '📄', badge: 3, onClick: () => {
       ReportsPage ()
      } },

      { section: true, label: 'Account' },
      { label: 'Profile',  icon: '👤', onClick: () => {} },
      { label: 'Sign Out', icon: '🚪', onClick: () => {} },
    ],
    footer: (() => {
      const f = document.createElement('div');
      f.style.cssText = 'display:flex;align-items:center;gap:.65rem';
      f.appendChild(UI.avatar('JD', 'sm'));
      f.innerHTML += '<div><div style="font-weight:700;font-size:.875rem">Juan dela Cruz</div>'
                  + '<div style="font-size:.75rem;opacity:.7">Intern</div></div>';
      return f;
    })()
  });

  // 2. Wire it to the navbar — hamburger auto-connects
  UI.navbar({
    brand:   'CITAS',
    sidebar: nav,   // ← same as before, same API
    right:   [ UI.button('Logout', { variant: 'ghost', size: 'sm' }) ],
  });
}
function ReportsPage () {
    const App = document.getElementById("app");
    App.innerHTML = "";
    const container = document.createElement("div");
    container.className = "dashboard-page";
     container.innerHTML = `
        <div id = "stats-panel" class="stats-grid"></div>
        <div class="dashboard-layout">
            <div id="announcement-panel" class="announcement">
            <h3>Reports </h3>
            </div>
            <div id="table-panel">
            <h3>📋Records</h3>
            </div>
        </div>
     `;
     App.appendChild(container)

}

function DashboardPage () {
    const App = document.getElementById("app");
    App.innerHTML = "";
    const container = document.createElement("div");
    container.className = "dashboard-page";
     container.innerHTML = `
        <div id = "stats-panel" class="stats-grid"></div>
        <div class="dashboard-layout">
            <div id="announcement-panel" class="announcement">
            <h3>📢 Announcements</h3>
            </div>
            <div id="table-panel">
            <h3>📋 Attendance Records</h3>
            </div>
        </div>
     `;
    const tableContainer = container.querySelector("#stats-panel");
    const statsDiv = container.querySelector("#stats-panel");
    statsPanel (statsDiv)
    anouncementPanel (container.querySelector("#announcement-panel"));
    attendanceTable (container.querySelector("#table-panel"));
    App.appendChild(container)

}
function statsPanel (container) {
  const hoursStat = UI.statCard('0.1', 'Total Hours', '🕐', { base:'#fdf2f8', text:'#9d174d' })
  container.appendChild(hoursStat);
  const daysStats = UI.statCard('1', 'Days Present', '📅', { base:'#fdf2f8', text:'#9d174d' })
  container.appendChild(daysStats  );
  const submisionStats = UI.statCard('2', 'Reports Submited', '📄', { base:'#fdf2f8', text:'#9d174d' })
  container.appendChild(submisionStats);
  const statusStats = UI.statCard('Out', 'Today\'s Status', '🔴', { base:'#fdf2f8', text:'#9d174d' })
  container.appendChild(statusStats);
}
function anouncementPanel (rootDiv) {
    const card = UI.card({
      title: 'jdnfjfj',
      body: '📭',
      subtitle: 'No announcements yet',
      color: { base:'#fef3c7', text:'#92400e' }
    });
    rootDiv.appendChild(card);
  }

function attendanceTable(container) { 
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

    const rows = [
      { date: 'april 32', timein: '9:00 am', timeout: '5: pm', hours: '8' },
      { date: 'april 32', timein: '9:00 am', timeout: '5: pm', hours: '8' },
      { date: 'april 32', timein: '9:00 am', timeout: '5: pm', hours: '8' },
    ];

    const table = UI.table(columns, rows, {
      hoverable:  true,
      emptyText:  'No interns found.',
      onRowClick: (row) => console.log('Row clicked:', row),
    });

    container.appendChild(table);
  }







function loadPage(page) {
  const app = document.getElementById("app");

  // CLEAR old page
  app.innerHTML = "";

  // ADD new page
  if (page === "profile") {
    app.appendChild(ProfilePage());
  }

  if (page === "dashboard") {
    app.appendChild(DashboardPage());
  }
}