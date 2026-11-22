<?php
if (!defined('APP_BOOTSTRAPPED')) {
    http_response_code(404);
    exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Back-office - Parcelles</title>
  <link rel="stylesheet" href="/agriloop/views/backoffice/backoffice.css?v=20261122b">
</head>
<body>
  <div class="bo-shell">
    <aside class="bo-sidebar">
      <a class="bo-brand" href="/agriloop/index.php?route=backoffice.parcelles">
        <span class="bo-brand-mark" aria-hidden="true">A</span><span>AgriLoop</span>
      </a>
      <nav class="bo-nav" aria-label="Navigation back-office">
        <a href="/agriloop/index.php?route=backoffice.parcelles" aria-current="page">▦ Parcelles</a>
        <a href="/agriloop/index.php?route=backoffice.parcelle.create">＋ Nouvelle parcelle</a>
        <a href="/agriloop/index.php?route=backoffice.consommations">◒ Consommations d’eau</a>
      </nav>
    </aside>
    <main class="bo-main">
      <header class="bo-topbar">
        <h1 class="bo-title">Parcelles</h1>
        <span class="bo-breadcrumb">Back-office / Parcelles</span>
      </header>
      <?php if (is_string($flashMessage)): ?>
        <p class="bo-notice"><?= agriloop_escape($flashMessage) ?></p>
      <?php endif; ?>
      <section class="bo-card">
        <div class="bo-card-header">
          <div>
            <h2>Liste des parcelles</h2>
            <p>Gérez les parcelles enregistrées.</p>
          </div>
          <a class="bo-primary" href="/agriloop/index.php?route=backoffice.parcelle.create">＋ Ajouter</a>
        </div>
        <div class="bo-table-wrap">
          <?php if ($parcelles === []): ?>
            <p class="bo-form-help">Aucune parcelle enregistrée.</p>
          <?php else: ?>
            <table>
              <thead>
                <tr><th>Nom</th><th>Culture</th><th>Superficie (ha)</th><th>Localisation</th><th>Irrigation</th><th>Actions</th></tr>
              </thead>
              <tbody>
                <?php foreach ($parcelles as $parcelle): ?>
                  <tr>
                    <td><?= agriloop_escape($parcelle['nom']) ?></td>
                    <td><?= agriloop_escape($parcelle['culture']) ?></td>
                    <td><?= agriloop_escape($parcelle['superficie_ha']) ?></td>
                    <td><?= agriloop_escape($parcelle['localisation']) ?></td>
                    <td><?= agriloop_escape($parcelle['mode_irrigation']) ?></td>
                    <td>
                      <div class="bo-table-actions">
                        <a href="/agriloop/index.php?route=backoffice.parcelle.edit&amp;id=<?= (int) $parcelle['id'] ?>">Modifier</a>
                        <form action="/agriloop/index.php?route=backoffice.parcelle.delete&amp;id=<?= (int) $parcelle['id'] ?>" method="post">
                          <input type="hidden" name="_csrf_token" value="<?= agriloop_escape($csrfToken) ?>">
                          <button type="submit">Supprimer</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
