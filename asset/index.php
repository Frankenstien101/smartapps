<?php
$appConfig = ['appName' => 'BSPI IT Asset Manager'];
function include_partial($name)
{
  $candidates = [
    __DIR__ . '/partials/' . $name . '.html',
    __DIR__ . '/partials/' . strtolower($name) . '/' . $name . '.html',
    __DIR__ . '/partials/' . strtolower($name) . '.html',
  ];
  foreach ($candidates as $file) {
    if (is_file($file)) {
      include $file;
      return;
    }
  }
}
?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($appConfig['appName']) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/app.css">
</head>

<body>
  <div class="app-shell">
    <?php include_partial('Sidebar'); ?>
    <main class="app-main">
      <?php include_partial('Header'); ?>
      <section id="app-content" class="app-content">
        <?php include_partial('Workstations'); ?>
      </section>
    </main>
    <aside id="detailsDrawer" class="details-drawer">
      <div class="drawer-backdrop" data-action="close-drawer"></div>
      <div class="drawer-panel">
        <div class="drawer-resize-handle" id="drawerResizeHandle" title="Resize details panel"></div>
        <div id="drawerContent" class="drawer-content"></div>
      </div>
    </aside>
    <?php include_partial('AddAssetModal'); ?>
    <?php include_partial('AddWorkstationModal'); ?>
  </div>
  <?php include_partial('Dashboard'); ?>
  <?php include_partial('Assets'); ?>
  <?php include_partial('Employees'); ?>
  <?php include_partial('Reports'); ?>
  <?php include_partial('Operations'); ?>
  <?php include_partial('Admin'); ?>
  <?php include_partial('WorkstationDetails'); ?>
  <?php include_partial('AssetDetails'); ?>
  <?php include_partial('Loading'); ?>
  <?php include_partial('EmptyState'); ?>
  <script defer src="assets/js/app.js"></script>
</body>

</html>