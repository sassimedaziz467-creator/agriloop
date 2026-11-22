<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/model/ParcelleModel.php';

final class ParcelleController
{
    private const CULTURES = ['cereales', 'legumes', 'fruits', 'autre'];
    private const IRRIGATIONS = ['goutte-a-goutte', 'aspersion', 'gravitaire', 'autre'];

    public function index(): void
    {
        $model = new ParcelleModel();
        if ($this->isBackoffice()) {
            $flash = agriloop_take_flash();
            extract([
                'parcelles' => $model->all(),
                'csrfToken' => agriloop_csrf_token(),
                'flashMessage' => $flash,
            ], EXTR_SKIP);
            require APP_ROOT . '/views/backoffice/parcelles.php';
            return;
        }
        agriloop_render('parcelles', [
            'title' => 'Parcelles',
            'parcelles' => $model->all(),
            'csrfToken' => agriloop_csrf_token(),
            'flashMessage' => agriloop_take_flash(),
        ]);
    }

    public function create(): void
    {
        $values = $this->emptyValues();
        $errors = [];

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            agriloop_require_post();
            if (!agriloop_valid_csrf()) {
                $errors['form'] = 'Formulaire expiré. Rechargez la page puis réessayez.';
            } else {
                [$values, $data, $errors] = $this->validateInput();
                if ($errors === []) {
                    (new ParcelleModel())->save(null, $data);
                    agriloop_flash('Parcelle ajoutée.');
                    agriloop_redirect($this->isBackoffice() ? 'backoffice.parcelles' : 'parcelles');
                }
            }
        }

        $this->showForm('Ajouter une parcelle', null, $values, $errors);
    }

    public function edit(int $id): void
    {
        $model = new ParcelleModel();
        $parcelle = $model->find($id);
        if ($parcelle === null) {
            agriloop_flash('Parcelle introuvable.');
            agriloop_redirect($this->isBackoffice() ? 'backoffice.parcelles' : 'parcelles');
            return;
        }

        $values = [
            'nom' => $parcelle['nom'],
            'culture' => $parcelle['culture'],
            'superficie_ha' => $parcelle['superficie_ha'],
            'localisation' => $parcelle['localisation'],
            'mode_irrigation' => $parcelle['mode_irrigation'],
        ];
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            agriloop_require_post();
            $errors = agriloop_valid_csrf() ? [] : ['form' => 'Formulaire expiré. Rechargez la page puis réessayez.'];
            if ($errors === []) {
                [$values, $data, $errors] = $this->validateInput();
            }
            if ($errors === []) {
                $model->save($id, $data);
                agriloop_flash('Parcelle modifiée.');
                agriloop_redirect($this->isBackoffice() ? 'backoffice.parcelles' : 'parcelles');
            }
            $this->showForm('Modifier une parcelle', $id, $values, $errors);
            return;
        }

        $this->showForm('Modifier une parcelle', $id, $values, []);
    }

    public function delete(int $id): void
    {
        agriloop_require_post();
        if (!agriloop_valid_csrf()) {
            agriloop_flash('Formulaire expiré. Rechargez la page puis réessayez.');
        } else {
            $model = new ParcelleModel();
            if ($model->find($id) === null) {
                agriloop_flash('Parcelle introuvable.');
            } else {
                $model->delete($id);
                agriloop_flash('Parcelle et consommations associées supprimées.');
            }
        }
        agriloop_redirect($this->isBackoffice() ? 'backoffice.parcelles' : 'parcelles');
    }

    private function showForm(string $title, ?int $id, array $values, array $errors): void
    {
        $backoffice = $this->isBackoffice();
        $view = $backoffice ? 'backoffice-ajouter-parcelle' : 'ajouter-parcelle';
        $createRoute = $backoffice ? 'backoffice.parcelle.create' : 'parcelle.create';
        $editRoute = $backoffice ? 'backoffice.parcelle.edit' : 'parcelle.edit';
        agriloop_render_html($view, [
            '{{TITLE}}' => agriloop_escape($title),
            'action="http://localhost/agriloop/index.php?route=parcelle.create"' => $id === null
                ? 'action="http://localhost/agriloop/index.php?route=' . $createRoute . '"'
                : 'action="http://localhost/agriloop/index.php?route=' . $editRoute . '&amp;id=' . $id . '"',
            '<!--{{ID_FIELD}}-->' => $id === null ? '' : '<input type="hidden" name="id" value="' . $id . '">',
            '{{CSRF_TOKEN}}' => agriloop_escape(agriloop_csrf_token()),
            '<!--{{FORM_ERROR}}-->' => $this->errorMarkup($errors, 'form'),
            '{{NOM}}' => agriloop_escape($values['nom']),
            '{{CULTURE}}' => agriloop_escape($values['culture']),
            '{{SUPERFICIE}}' => agriloop_escape($values['superficie_ha']),
            '{{LOCALISATION}}' => agriloop_escape($values['localisation']),
            '{{IRRIGATION}}' => agriloop_escape($values['mode_irrigation']),
            '<!--{{NOM_ERROR}}-->' => $this->errorMarkup($errors, 'nom'),
            '<!--{{CULTURE_ERROR}}-->' => $this->errorMarkup($errors, 'culture'),
            '<!--{{SUPERFICIE_ERROR}}-->' => $this->errorMarkup($errors, 'superficie_ha'),
            '<!--{{LOCALISATION_ERROR}}-->' => $this->errorMarkup($errors, 'localisation'),
            '<!--{{IRRIGATION_ERROR}}-->' => $this->errorMarkup($errors, 'mode_irrigation'),
        ]);
    }

    private function isBackoffice(): bool
    {
        $route = $_GET['route'] ?? '';
        return is_string($route) && str_starts_with($route, 'backoffice.');
    }

    private function errorMarkup(array $errors, string $field): string
    {
        if (!isset($errors[$field])) {
            return '';
        }

        $class = $this->isBackoffice() ? 'bo-form-error' : 'form-error';
        return '<span class="' . $class . '">' . agriloop_escape($errors[$field]) . '</span>';
    }

    private function validateInput(): array
    {
        $values = $this->emptyValues();
        foreach (array_keys($values) as $key) {
            $values[$key] = is_string($_POST[$key] ?? null) ? trim($_POST[$key]) : '';
        }

        $nom = $this->validText($values['nom'], 2, 100);
        $culture = in_array($values['culture'], self::CULTURES, true) ? $values['culture'] : null;
        $superficie = $this->validDecimal($values['superficie_ha'], 1000000);
        $localisation = $this->validText($values['localisation'], 2, 180);
        $irrigation = in_array($values['mode_irrigation'], self::IRRIGATIONS, true)
            ? $values['mode_irrigation']
            : null;
        $errors = [];

        if ($nom === null) {
            $errors['nom'] = 'Le nom doit contenir entre 2 et 100 caractères.';
        }
        if ($culture === null) {
            $errors['culture'] = 'Sélectionnez une culture valide.';
        }
        if ($superficie === null) {
            $errors['superficie_ha'] = 'La superficie doit être positive, avec 2 décimales maximum.';
        }
        if ($localisation === null) {
            $errors['localisation'] = 'La localisation doit contenir entre 2 et 180 caractères.';
        }
        if ($irrigation === null) {
            $errors['mode_irrigation'] = 'Sélectionnez un mode d’irrigation valide.';
        }

        $data = $errors === [] ? [
            'nom' => $nom,
            'culture' => $culture,
            'superficie_ha' => $superficie,
            'localisation' => $localisation,
            'mode_irrigation' => $irrigation,
        ] : [];

        return [$values, $data, $errors];
    }

    private function emptyValues(): array
    {
        return [
            'nom' => '',
            'culture' => '',
            'superficie_ha' => '',
            'localisation' => '',
            'mode_irrigation' => '',
        ];
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
}
