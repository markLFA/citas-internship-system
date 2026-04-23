<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CITAS UI — Component Reference</title>
  <link rel="stylesheet" href="../assets/uiParts.css">
  <style> 
    .app {
      padding: 0.2rem;
    }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
      gap: 1rem;
      margin-bottom: 1.5rem;
    }
    .dashboard-layout {
      display: grid;
      grid-template-columns: 1fr; /* mobile default */
      gap: 1rem;
    }

    /* PC layout */
    @media (min-width: 768px) {
      .dashboard-layout {
        grid-template-columns: 300px 1fr;
      }
    }
  </style>
</head>
<body>

<div id="app" class="app">

</div>

<script type="module" src="pages.js"></script>




</body>
</html>