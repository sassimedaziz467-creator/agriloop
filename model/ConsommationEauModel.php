<?php
declare(strict_types=1);

final class ConsommationEauModel
{
    public function all(): array
    {
        $statement = agriloop_db()->prepare(
            'SELECT c.id, c.date_consommation, c.volume_m3, c.remarque, p.nom AS parcelle
             FROM consommations_eau c
             JOIN parcelles p ON p.id = c.parcelle_id
             ORDER BY c.date_consommation DESC, c.id DESC'
        );
        $statement->execute();

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = agriloop_db()->prepare(
            'SELECT id, parcelle_id, date_consommation, volume_m3, remarque
             FROM consommations_eau WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $consommation = $statement->fetch();

        return $consommation === false ? null : $consommation;
    }

    public function create(array $data): void
    {
        $statement = agriloop_db()->prepare(
            'INSERT INTO consommations_eau (parcelle_id, date_consommation, volume_m3, remarque)
             VALUES (:parcelle_id, :date_consommation, :volume_m3, :remarque)'
        );
        $statement->execute($data);
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $statement = agriloop_db()->prepare(
            'UPDATE consommations_eau
             SET parcelle_id = :parcelle_id, date_consommation = :date_consommation,
                 volume_m3 = :volume_m3, remarque = :remarque
             WHERE id = :id'
        );
        $statement->execute($data);
    }

    public function delete(int $id): void
    {
        $statement = agriloop_db()->prepare(
            'DELETE FROM consommations_eau WHERE id = :id'
        );
        $statement->execute(['id' => $id]);
    }
}
