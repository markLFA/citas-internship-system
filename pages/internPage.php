<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CITAS UI — Component Reference</title>
  <link rel="stylesheet" href="../assets/uiParts.css">
</head>
<body>

<div id="app"></div>

<script src="../assets/uiParts.js"></script>
<script>


// open it
/*
const nav = UI.sidebar({
  brand: 'CITAS',
  brandIcon: '🎓',
  links: [
    { label: 'Dashboard', href: '/dashboard.php', icon: '🏠', active: true },
    { label: 'Interns', href: '/interns.php', icon: '👨‍🎓' },
    { label: 'Reports', href: '/reports.php', icon: '📊' }
  ]
});
//document.getElementById("app").appendChild(nav);
nav.open();
*/
// create sidebar first
// create sidebar first
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
const btn2 = UI.button('Soft', {
  color: {
    base: '#fef3c7',
    hover: '#fde68a',
    text: '#92400e'
  }
});
  document.getElementById("app").appendChild(btn2);



</script>

</body>
</html>