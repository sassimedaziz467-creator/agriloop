<?php
declare(strict_types=1);

final class ParcelleModel
{
    public function all(): array
    {
        $statement = agriloop_db()->prepare(
            'SELECT id, nom, culture, superficie_ha, localisation, mode_irrigation
             FROM parcelles ORDER BY id DESC'
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = agriloop_db()->prepare(
            'SELECT id, nom, culture, superficie_ha, localisation, mode_irrigation
             FROM parcelles WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $parcelle = $statement->fetch();

        return $parcelle === false ? null : $parcelle;
    }

    public function save(?int $id, array $data): void
    {
        if ($id === null) {
            $statement = agriloop_db()->prepare(
                'INSERT INTO parcelles (nom, culture, superficie_ha, localisation, mode_irrigation)
                 VALUES (:nom, :culture, :superficie_ha, :localisation, :mode_irrigation)'
            );
            $statement->execute($data);
            return;
        }

        $data['id'] = $id;
        $statement = agriloop_db()->prepare(
            'UPDATE parcelles
             SET nom = :nom, culture = :culture, superficie_ha = :superficie_ha,
                 localisation = :localisation, mode_irrigation = :mode_irrigation
             WHERE id = :id'
        );
        $statement->execute($data);
    }

    public function delete(int $id): void
    {
        $statement = agriloop_db()->prepare(
            'DELETE FROM parcelles WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
    }
}
