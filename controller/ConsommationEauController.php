<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/model/ConsommationEauModel.php';
require_once dirname(__DIR__) . '/model/ParcelleModel.php';

final class ConsommationEauController
{
    public function index(): void
    {
        $parcelleModel = new ParcelleModel();
        $parcelles = $parcelleModel->all();
        $model = new ConsommationEauModel();
        $values = $this->emptyValues();
        $requestedParcelleId = filter_var($_GET['parcelle_id'] ?? null, FILTER_VALIDATE_INT);
        foreach ($parcelles as $parcelle) {
            if ($requestedParcelleId !== false && (int) $parcelle['id'] === $requestedParcelleId) {
                $values['parcelle'] = $this->parcelReference($parcelle);
                break;
            }
        }
        $errors = [];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            agriloop_require_post();
            $values = $this->postedValues();
            if (!agriloop_valid_csrf()) {
                $errors['form'] = 'Formulaire expiré. Rechargez la page puis réessayez.';
            } else {
                $this->save($model, $parcelles, $values, $errors);
            }
        }

        $this->renderPage($model, $parcelles, $values, $errors, null);
    }

    public function edit(int $id): void
    {
        $model = new ConsommationEauModel();
        $parcelleModel = new ParcelleModel();
        $consommation = $model->find($id);
        if ($consommation === null) {
            agriloop_flash('Consommation introuvable.');
            agriloop_redirect($this->isBackoffice() ? 'backoffice.consommations' : 'consommations');
        }

        $parcelles = $parcelleModel->all();
        $values = [
            'parcelle' => $this->parcelReferenceForId((int) $consommation['parcelle_id'], $parcelles),
            'date_consommation' => $consommation['date_consommation'],
            'volume_m3' => $consommation['volume_m3'],
            'remarque' => $consommation['remarque'] ?? '',
        ];
        $errors = [];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            agriloop_require_post();
            $values = $this->postedValues();
            if (!agriloop_valid_csrf()) {
                $errors['form'] = 'Formulaire expiré. Rechargez la page puis réessayez.';
            } else {
                $this->save($model, $parcelles, $values, $errors, $id);
            }
        }

        $this->renderPage($model, $parcelles, $values, $errors, $id);
    }

    public function delete(int $id): void
    {
        agriloop_require_post();
        $model = new ConsommationEauModel();
        if (!agriloop_valid_csrf()) {
            agriloop_flash('Formulaire expiré. Rechargez la page puis réessayez.');
        } elseif ($model->find($id) === null) {
            agriloop_flash('Consommation introuvable.');
        } else {
            $model->delete($id);
            agriloop_flash('Consommation supprimée.');
        }
        agriloop_redirect($this->isBackoffice() ? 'backoffice.consommations' : 'consommations');
    }

    private function renderPage(
        ConsommationEauModel $model,
        array $parcelles,
        array $values,
        array $errors,
        ?int $editingId
    ): void {
        $consommations = $model->all();
        $options = '';
        foreach ($parcelles as $parcelle) {
            $options .= '<option value="' . agriloop_escape($this->parcelReference($parcelle)) . '"></option>';
        }

        $rows = '';
        $backoffice = $this->isBackoffice();
        $editRoute = $backoffice ? 'backoffice.consommation.edit' : 'consommation.edit';
        $deleteRoute = $backoffice ? 'backoffice.consommation.delete' : 'consommation.delete';
        foreach ($consommations as $consommation) {
            $consommationId = (int) $consommation['id'];
            $rows .= '<tr><td>' . agriloop_escape($consommation['parcelle']) . '</td><td>'
                . agriloop_escape($consommation['date_consommation']) . '</td><td>'
                . agriloop_escape($consommation['volume_m3']) . '</td><td>'
                . agriloop_escape($consommation['remarque'] ?? '') . '</td><td class="' . ($backoffice ? 'bo-table-actions' : 'table-actions') . '">'
                . '<a href="http://localhost/agriloop/index.php?route=' . $editRoute . '&amp;id='
                . $consommationId . '">Modifier</a>'
                . '<form action="http://localhost/agriloop/index.php?route=' . $deleteRoute . '&amp;id='
                . $consommationId . '" method="post">'
                . '<input type="hidden" name="_csrf_token" value="' . agriloop_escape(agriloop_csrf_token()) . '">'
                . '<button class="link-button" type="submit">Supprimer</button></form></td></tr>';
        }

        $view = $backoffice ? 'backoffice-consommation-eau' : 'saisir-consommation-eau';
        $indexRoute = $backoffice ? 'backoffice.consommations' : 'consommations';
        $editRoute = $backoffice ? 'backoffice.consommation.edit' : 'consommation.edit';
        $deleteRoute = $backoffice ? 'backoffice.consommation.delete' : 'consommation.delete';
        agriloop_render_html($view, [
            '{{TITLE}}' => agriloop_escape($editingId === null
                ? 'Consommations d’eau'
                : 'Modifier une consommation'),
            'action="http://localhost/agriloop/index.php?route=consommations"' => $editingId === null
                ? 'action="http://localhost/agriloop/index.php?route=' . $indexRoute . '"'
                : 'action="http://localhost/agriloop/index.php?route=' . $editRoute . '&amp;id=' . $editingId . '"',
            '<!--{{ID_FIELD}}-->' => $editingId === null
                ? ''
                : '<input type="hidden" name="id" value="' . $editingId . '">',
            '{{CSRF_TOKEN}}' => agriloop_escape(agriloop_csrf_token()),
            '<!--{{PARCELLE_OPTIONS}}-->' => $options,
            '{{PARCELLE}}' => agriloop_escape($values['parcelle']),
            '{{DATE}}' => agriloop_escape($values['date_consommation']),
            '{{VOLUME}}' => agriloop_escape($values['volume_m3']),
            '{{REMARQUE}}' => agriloop_escape($values['remarque']),
            '<!--{{PARCELLE_ERROR}}-->' => $this->errorMarkup($errors, 'parcelle'),
            '<!--{{DATE_ERROR}}-->' => $this->errorMarkup($errors, 'date_consommation'),
            '<!--{{VOLUME_ERROR}}-->' => $this->errorMarkup($errors, 'volume_m3'),
            '<!--{{REMARQUE_ERROR}}-->' => $this->errorMarkup($errors, 'remarque'),
            '<!--{{FORM_ERROR}}-->' => $this->errorMarkup($errors, 'form'),
            '<!--{{NO_PARCELLES}}-->' => $parcelles === []
                ? '<p class="form-help">Ajoutez d’abord une parcelle pour pouvoir saisir une consommation.</p>'
                : '',
            '{{DISABLED}}' => $parcelles === [] ? ' disabled' : '',
            '<!--{{CONSUMPTION_ROWS}}-->' => $rows,
            '<!--{{EMPTY_CONSUMMATIONS}}--><tr><td colspan="5">Aucune consommation enregistrée.</td></tr>'
                => $rows === '' ? '<tr><td colspan="5">Aucune consommation enregistrée.</td></tr>' : '',
            '<!--{{EMPTY_CONSUMMATIONS}}-->' => $rows === '' ? '<tr><td colspan="5">Aucune consommation enregistrée.</td></tr>' : '',
            '<!--{{FLASH_MESSAGE}}-->' => $this->flashMarkup(agriloop_take_flash()),
        ]);
    }

    private function save(
        ConsommationEauModel $model,
        array $parcelles,
        array $values,
        array &$errors,
        ?int $editingId = null
    ): void {
        $parcelleId = $this->resolveParcelId($values['parcelle'], $parcelles);
        $date = $this->validDate($values['date_consommation']);
        $volume = $this->validDecimal($values['volume_m3'], 1000000000);
        $remarque = $values['remarque'] === '' ? null : $this->validText($values['remarque'], 1, 500);

        if ($parcelleId === null) {
            $errors['parcelle'] = 'Saisissez une parcelle existante ou choisissez-la dans les suggestions.';
        }
        if ($date === null) {
            $errors['date_consommation'] = 'Saisissez une date valide qui ne se situe pas dans le futur.';
        }
        if ($volume === null) {
            $errors['volume_m3'] = 'Le volume doit être positif, avec 2 décimales maximum.';
        }
        if ($values['remarque'] !== '' && $remarque === null) {
            $errors['remarque'] = 'La remarque ne doit pas dépasser 500 caractères.';
        }

        if ($errors === []) {
            $data = [
                'parcelle_id' => $parcelleId,
                'date_consommation' => $date,
                'volume_m3' => $volume,
                'remarque' => $remarque,
            ];
            if ($editingId === null) {
                $model->create($data);
                agriloop_flash('Consommation d’eau enregistrée.');
            } else {
                $model->update($editingId, $data);
                agriloop_flash('Consommation modifiée.');
            }
            agriloop_redirect($this->isBackoffice() ? 'backoffice.consommations' : 'consommations');
        }
    }

    private function isBackoffice(): bool
    {
        $route = $_GET['route'] ?? '';
        return is_string($route) && str_starts_with($route, 'backoffice.');
    }

    private function emptyValues(): array
    {
        return [
            'parcelle' => '',
            'date_consommation' => date('Y-m-d'),
            'volume_m3' => '',
            'remarque' => '',
        ];
    }

    private function postedValues(): array
    {
        return [
            'parcelle' => is_string($_POST['parcelle'] ?? null) ? trim($_POST['parcelle']) : '',
            'date_consommation' => is_string($_POST['date_consommation'] ?? null)
                ? trim($_POST['date_consommation'])
                : '',
            'volume_m3' => is_string($_POST['volume_m3'] ?? null) ? trim($_POST['volume_m3']) : '',
            'remarque' => is_string($_POST['remarque'] ?? null) ? trim($_POST['remarque']) : '',
        ];
    }

    private function parcelReference(array $parcelle): string
    {
        return $parcelle['nom'] . ' — ' . $parcelle['localisation'] . ' (#' . (int) $parcelle['id'] . ')';
    }

    private function parcelReferenceForId(int $id, array $parcelles): string
    {
        foreach ($parcelles as $parcelle) {
            if ((int) $parcelle['id'] === $id) {
                return $this->parcelReference($parcelle);
            }
        }

        return '';
    }

    private function resolveParcelId(string $value, array $parcelles): ?int
    {
        foreach ($parcelles as $parcelle) {
            if ($this->parcelReference($parcelle) === $value) {
                return (int) $parcelle['id'];
            }
        }

        $matchingIds = [];
        $normalizedValue = mb_strtolower($value, 'UTF-8');
        foreach ($parcelles as $parcelle) {
            if (mb_strtolower($parcelle['nom'], 'UTF-8') === $normalizedValue) {
                $matchingIds[] = (int) $parcelle['id'];
            }
        }

        return count($matchingIds) === 1 ? $matchingIds[0] : null;
    }

    private function validText(string $value, int $min, int $max): ?string
    {
        $value = trim($value);
        $length = mb_strlen($value, 'UTF-8');
        return $length >= $min && $length <= $max && !str_contains($value, "\0") ? $value : null;
    }

    private function validDecimal(string $value, float $maximum): ?string
    {
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $value)) {
            return null;
        }
        $number = (float) $value;
        return is_finite($number) && $number > 0 && $number <= $maximum
            ? number_format($number, 2, '.', '')
            : null;
    }

    private function validDate(string $value): ?string
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        $errors = DateTimeImmutable::getLastErrors();
        if (
            $date === false
            || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
            || $date->format('Y-m-d') !== $value
            || $date > new DateTimeImmutable('today')
        ) {
            return null;
        }

        return $value;
    }

    private function errorMarkup(array $errors, string $field): string
    {
        if (!isset($errors[$field])) {
            return '';
        }

        $class = $this->isBackoffice() ? 'bo-form-error' : 'form-error';
        return '<span class="' . $class . '">' . agriloop_escape($errors[$field]) . '</span>';
    }

    private function flashMarkup(?string $message): string
    {
        if ($message === null) {
            return '';
        }
        $class = $this->isBackoffice() ? 'bo-notice' : 'notice notice-success';
        return '<p class="' . $class . '">' . agriloop_escape($message) . '</p>';
    }
}
