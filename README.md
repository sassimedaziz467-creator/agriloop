# Agriloop — Parcelles

## Accès

Dans XAMPP, ouvrir `http://localhost/agriloop/index.php`.

## Base de données

Créer la base et un compte dédié dans MySQL/phpMyAdmin :

```sql
CREATE DATABASE agriloop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'agriloop_app'@'127.0.0.1' IDENTIFIED BY 'REMPLACER_PAR_UN_SECRET';
GRANT SELECT, INSERT, UPDATE, DELETE ON agriloop.* TO 'agriloop_app'@'127.0.0.1';
```

Importer `schema.sql` dans `agriloop`. Placer les variables dans la configuration Apache, puis redémarrer Apache (ne pas mettre le mot de passe dans le code du projet ou dans `.htaccess`) :

```apache
<Directory "C:/xampp/htdocs/agriloop">
    SetEnv AGRILOOP_DB_HOST 127.0.0.1
    SetEnv AGRILOOP_DB_NAME agriloop
    SetEnv AGRILOOP_DB_USER agriloop_app
    SetEnv AGRILOOP_DB_PASSWORD "REMPLACER_PAR_LE_MEME_SECRET"
</Directory>
```

Le module ne gère pas les comptes utilisateurs : les parcelles et les consommations forment un jeu de données commun. La session PHP reste utilisée pour les jetons CSRF.

## Organisation

- `controller/ParcelleController.php` et `controller/ConsommationEauController.php` : routes et validation des deux fonctionnalités.
- `model/ParcelleModel.php` et `model/ConsommationEauModel.php` : requêtes PDO correspondant aux tables du schéma.
- `views/frontoffice/parcelles.php` : liste des parcelles ; `views/frontoffice/ajouter-parcelle.html` et `views/frontoffice/saisir-consommation-eau.html` : formulaires HTML du front-office, alimentés par les contrôleurs.
- `database.php` : connexion PDO ; `bootstrap.php` : session, CSRF et rendu.
- `index.php` : point d’entrée.
- `schema.sql` : schéma MySQL.

## CRUD

- Parcelles : `parcelles` (consultation), `parcelle.create` (ajout), `parcelle.edit&id=...` (modification) et `parcelle.delete&id=...` (suppression par POST).
- Consommations d’eau : `consommations` (consultation et ajout), `consommation.edit&id=...` (modification) et `consommation.delete&id=...` (suppression par POST).

Dans le formulaire de consommation, le champ Parcelle est saisissable et propose les parcelles existantes. Les noms en double sont différenciés par la localisation et l’identifiant; une saisie libre n’est acceptée que si le nom correspond à une seule parcelle.

Les formulaires valident les données côté client et côté serveur et utilisent un jeton CSRF. Le CRUD des parcelles et des consommations opère sur les données communes. Supprimer une parcelle supprime aussi ses consommations associées.
