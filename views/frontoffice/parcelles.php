<?php
if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}
$cultures = ['cereales' => 'Céréales', 'legumes' => 'Légumes', 'fruits' => 'Fruits', 'autre' => 'Autre'];
$irrigations = [
    'goutte-a-goutte' => 'Goutte-à-goutte',
    'aspersion' => 'Aspersion',
    'gravitaire' => 'Gravitaire',
    'autre' => 'Autre',
];
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= agriloop_escape($title) ?> | Agriloop</title>
  <link rel="stylesheet" href="/agriloop/views/frontoffice/template.css?v=20261122e">
</head>
<body>
  <div class="app-shell">
    <aside class="sidebar">
      <a class="brand" href="/agriloop/index.php?route=parcelles" aria-label="Agriloop, accueil">
        <span class="brand-mark" aria-hidden="true"></span><span>AgriLoop</span>
      </a>
      <nav class="main-nav" aria-label="Navigation principale">
        <a href="/agriloop/index.php?route=parcelles" aria-current="page"><span class="nav-icon" aria-hidden="true">▦</span>Mes parcelles</a>
        <a href="/agriloop/index.php?route=parcelle.create"><span class="nav-icon" aria-hidden="true">＋</span>Ajouter une parcelle</a>
        <a href="/agriloop/index.php?route=consommations"><span class="nav-icon" aria-hidden="true">◒</span>Consommation d’eau</a>
      </nav>
      <div class="sidebar-footer">Agriculture durable</div>
    </aside>
    <main class="app-main page-parcelles">
      <section class="hero">
        <div class="hero-copy">
          <p class="hero-kicker">Mon exploitation</p>
          <h1><?= agriloop_escape($title) ?></h1>
          <p class="hero-subtitle">Consultez, modifiez et suivez toutes vos parcelles depuis votre espace AgriLoop.</p>
        </div>
      </section>
      <div class="page-panel">
        <?php if (is_string($flashMessage)): ?>
          <div class="notice notice-success" role="status"><?= agriloop_escape($flashMessage) ?></div>
        <?php endif; ?>
        <div class="page-heading">
          <h2>Liste des parcelles</h2>
          <a class="button-link" href="/agriloop/index.php?route=parcelle.create">＋ Ajouter une parcelle</a>
        </div>
        <section class="content-card parcel-list">
          <?php if ($parcelles === []): ?>
            <p class="empty-state">Aucune parcelle enregistrée.</p>
          <?php else: ?>
          <div class="table-wrap">
            <table>
          <thead>
            <tr><th>Nom</th><th>Culture</th><th>Superficie (ha)</th><th>Localisation</th><th>Irrigation</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php foreach ($parcelles as $parcelle): ?>
              <tr>
                <td><?= agriloop_escape($parcelle['nom']) ?></td>
                <td><?= agriloop_escape($cultures[$parcelle['culture']] ?? $parcelle['culture']) ?></td>
                <td><?= agriloop_escape($parcelle['superficie_ha']) ?></td>
                <td><?= agriloop_escape($parcelle['localisation']) ?></td>
                <td><?= agriloop_escape($irrigations[$parcelle['mode_irrigation']] ?? $parcelle['mode_irrigation']) ?></td>
                <td class="table-actions">
                  <a href="/agriloop/index.php?route=parcelle.edit&amp;id=<?= (int) $parcelle['id'] ?>">Modifier</a>
                  <a href="/agriloop/index.php?route=consommations&amp;parcelle_id=<?= (int) $parcelle['id'] ?>">Saisir l’eau</a>
                  <form action="/agriloop/index.php?route=parcelle.delete&amp;id=<?= (int) $parcelle['id'] ?>" method="post">
                    <input type="hidden" name="_csrf_token" value="<?= agriloop_escape($csrfToken) ?>">
                    <button class="link-button" type="submit">Supprimer</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
            </table>
          </div>
          <p class="form-help">Supprimer une parcelle supprime également ses consommations d’eau.</p>
          <?php endif; ?>
        </section>
      </div>
    </main>
  </div>
</body>
</html>
