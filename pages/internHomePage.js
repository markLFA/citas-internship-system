// intern/intern.js
// ============================================================
// CITAS Intern Portal
// Built with CITAS UI Component Library
// ============================================================

document.addEventListener('DOMContentLoaded', initInternPortal);

function initInternPortal() {
    const app = document.getElementById('app');

    const layout = document.createElement('div');
    layout.className = 'intern-layout';

    layout.appendChild(createSidebar());

    const main = document.createElement('main');
    main.className = 'intern-main';

    main.appendChild(createHeader());

    const content = document.createElement('section');
    content.className = 'intern-content';
    content.id = 'page-container';

    main.appendChild(content);
    layout.appendChild(main);

    app.appendChild(layout);

    initializeNavigation();
    navigate('dashboard');
}
initInternPortal()
/* ============================================================
   Layout
============================================================ */

function createSidebar() {
    const sidebar = document.createElement('aside');
    sidebar.className = 'intern-sidebar';

    sidebar.innerHTML = `
        <div class="brand">
            <div class="brand-logo">C</div>
            <div class="brand-text">
                <h1>CITAS</h1>
                <p>Intern Portal</p>
            </div>
        </div>
    `;

    const nav = document.createElement('nav');
    nav.className = 'nav-menu';

    const items = [
        ['dashboard', 'Dashboard', '📊'],
        ['timelogs', 'Time Logs', '🕒'],
        ['reports', 'Weekly Reports', '📝'],
        ['announcements', 'Announcements', '📢'],
        ['profile', 'Profile', '👤']
    ];

    items.forEach(([page, label, icon]) => {
        const link = document.createElement('a');
        link.href = '#';
        link.className = 'nav-link';
        link.dataset.page = page;
        link.innerHTML = `
            <span>${icon}</span>
            <span>${label}</span>
        `;
        nav.appendChild(link);
    });

    sidebar.appendChild(nav);
    return sidebar;
}

function createHeader() {
    const header = document.createElement('header');
    header.className = 'intern-header';

    header.innerHTML = `
        <div class="page-title">
            <h2 id="header-title">Dashboard</h2>
            <p id="header-subtitle">
                Welcome back to your internship portal.
            </p>
        </div>

        <div class="header-profile">
            <div>
                <strong>Juan Dela Cruz</strong>
                <br>
                <small>BS Information Technology</small>
            </div>
        </div>
    `;

    header.querySelector('.header-profile')
        .appendChild(UI.avatar('Juan Dela Cruz'));

    return header;
}

/* ============================================================
   Navigation
============================================================ */

function initializeNavigation() {
    document.addEventListener('click', (e) => {
        const link = e.target.closest('.nav-link');
        if (!link) return;

        e.preventDefault();
        navigate(link.dataset.page);
    });
}

function navigate(page) {
    document.querySelectorAll('.nav-link').forEach(link => {
        link.classList.toggle(
            'active',
            link.dataset.page === page
        );
    });

    renderPage(page);
    updateHeader(page);
}

/* ============================================================
   Pages
============================================================ */

function renderPage(page) {
    const container = document.getElementById('page-container');
    container.innerHTML = '';

    const pages = {
        dashboard: createDashboardPage,
        timelogs: createTimeLogsPage,
        reports: createReportsPage,
        announcements: createAnnouncementsPage,
        profile: createProfilePage
    };

    container.appendChild(pages[page]());
}

function updateHeader(page) {
    const titles = {
        dashboard: ['Dashboard', 'Overview of your internship'],
        timelogs: ['Time Logs', 'Monitor your attendance'],
        reports: ['Weekly Reports', 'Submit accomplishments'],
        announcements: ['Announcements', 'Latest updates'],
        profile: ['Profile', 'Manage your account']
    };

    document.getElementById('header-title').textContent =
        titles[page][0];

    document.getElementById('header-subtitle').textContent =
        titles[page][1];
}

/* ============================================================
   Dashboard
============================================================ */

function createDashboardPage() {
    const page = document.createElement('div');

    page.appendChild(createProfileCard());
    page.appendChild(createStatsGrid());

    const grid = document.createElement('div');
    grid.className = 'dashboard-grid';

    grid.appendChild(createRecentLogsCard());
    grid.appendChild(createAnnouncementsCard());

    page.appendChild(grid);

    return page;
}

function createProfileCard() {
    return UI.card({
        title: 'Intern Information',
        body: `
            <div class="profile-summary">
                <div class="profile-avatar">JD</div>
                <div class="profile-details">
                    <h2>Juan Dela Cruz</h2>
                    <p>BS Information Technology</p>
                    <p>ABC Technology Solutions</p>
                    <p>Required Hours: 486</p>
                </div>
            </div>
        `
    });
}

function createStatsGrid() {
    const grid = document.createElement('div');
    grid.className = 'stats-grid';

    grid.append(
        UI.statCard('320', 'Hours Rendered', '⏱️'),
        UI.statCard('486', 'Required Hours', '🎯'),
        UI.statCard('12', 'Reports Submitted', '📝'),
        UI.statCard('98%', 'Attendance Rate', '📈')
    );

    return grid;
}

function createRecentLogsCard() {
    return UI.card({
        title: 'Recent Time Logs',
        body: createTimeLogsTable()
    });
}

function createAnnouncementsCard() {
    return UI.card({
        title: 'Latest Announcements',
        body: `
            <div class="announcement-list">
                ${announcementHTML(
                    'Weekly Report Deadline',
                    'Submit before Friday at 5:00 PM.'
                )}
                ${announcementHTML(
                    'Coordinator Visit',
                    'Company visit this Friday.'
                )}
            </div>
        `
    });
}

/* ============================================================
   Time Logs
============================================================ */

function createTimeLogsPage() {
    const page = document.createElement('div');

    const stats = document.createElement('div');
    stats.className = 'stats-grid';

    stats.append(
        UI.statCard('320', 'Total Hours', '⏱️'),
        UI.statCard('40', 'This Week', '📅'),
        UI.statCard('8', 'Daily Average', '⚡')
    );

    page.appendChild(stats);

    page.appendChild(UI.card({
        title: 'Attendance Records',
        body: createTimeLogsTable(true)
    }));

    return page;
}

function createTimeLogsTable() {
    const columns = [
        'Date',
        'Time In',
        'Time Out',
        'Hours'
    ];

    const rows = [
        ['Apr 21, 2026', '8:00 AM', '5:00 PM', '8.0'],
        ['Apr 22, 2026', '8:05 AM', '5:02 PM', '8.0'],
        ['Apr 23, 2026', '7:58 AM', '5:01 PM', '8.1']
    ];

    return UI.table(columns, rows);
}

/* ============================================================
   Weekly Reports
============================================================ */

function createReportsPage() {
    const page = document.createElement('div');

    const uploadCard = UI.card({
        title: 'Upload Weekly Report',
        body: `
            <div class="upload-zone">
                <h2>Drag & Drop Files Here</h2>
                <p>or click below to browse</p>
            </div>
        `
    });

    const buttonWrap = document.createElement('div');
    buttonWrap.style.marginTop = '1.5rem';
    buttonWrap.appendChild(
        UI.button('Select File', {
            variant: 'primary',
            icon: '📤'
        })
    );

    uploadCard.querySelector('.cui-card-body')
        .appendChild(buttonWrap);

    page.appendChild(uploadCard);

    page.appendChild(UI.card({
        title: 'Submission History',
        body: createReportsTable()
    }));

    return page;
}

function createReportsTable() {
    return UI.table(
        ['Week', 'Date', 'Status', 'Review'],
        [
            ['Week 10', 'Apr 15, 2026', 'Submitted', 'Approved'],
            ['Week 11', 'Apr 22, 2026', 'Submitted', 'Pending'],
            ['Week 12', 'Apr 29, 2026', 'Not Submitted', '-']
        ]
    );
}

/* ============================================================
   Announcements
============================================================ */

function createAnnouncementsPage() {
    return UI.card({
        title: 'All Announcements',
        body: `
            <div class="announcement-list">
                ${announcementHTML(
                    'Weekly Report Deadline',
                    'Submit your report before Friday.'
                )}
                ${announcementHTML(
                    'Coordinator Visit',
                    'Scheduled this Friday at 2:00 PM.'
                )}
                ${announcementHTML(
                    'System Maintenance',
                    'Saturday, 8:00 PM to 10:00 PM.'
                )}
            </div>
        `
    });
}

/* ============================================================
   Profile
============================================================ */

function createProfilePage() {
    const form = document.createElement('div');
    form.className = 'profile-form';

    form.append(
        UI.input({
            value: 'Juan',
            placeholder: 'First Name'
        }),
        UI.input({
            value: 'Dela Cruz',
            placeholder: 'Last Name'
        }),
        UI.input({
            value: 'juan@example.com',
            type: 'email'
        }),
        UI.input({
            value: 'BS Information Technology'
        })
    );

    const saveButton = UI.button('Save Changes', {
        variant: 'primary',
        icon: '💾'
    });

    form.appendChild(saveButton);

    return UI.card({
        title: 'Profile Information',
        body: form
    });
}

/* ============================================================
   Helpers
============================================================ */

function announcementHTML(title, text) {
    return `
        <div class="announcement">
            <h4>${title}</h4>
            <p>${text}</p>
            <small>Posted recently</small>
        </div>
    `;
}