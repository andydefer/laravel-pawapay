# Laravel Roster

![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-blue)
![Laravel Version](https://img.shields.io/badge/Laravel-12%2B-orange)
![License](https://img.shields.io/badge/license-MIT-green)
![Tests](https://img.shields.io/badge/tests-2300%20passing-brightgreen)
![Coverage](https://img.shields.io/badge/coverage-88%25-green)

**Roster** est un package Laravel complet pour la gestion avancée d'emplois du temps, de disponibilités et de réservations. Construit avec une architecture robuste, il gère les disponibilités récurrentes, les créneaux réservés et les empêchements avec une validation métier exhaustive.

## 📦 Installation

```bash
composer require andydefer/laravel-roster
```

Publier les ressources du package :

```bash
php artisan roster:install
```

Ou manuellement :

```bash
# Configuration
php artisan vendor:publish --tag=roster-config

# Migrations
php artisan vendor:publish --tag=roster-migrations

# Exécuter les migrations
php artisan migrate
```

## 🚀 Démarrage rapide

### 1. Ajouter le trait à vos modèles

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Roster\Traits\HasRoster;

class Doctor extends Model
{
    use HasRoster;
}
```

### 2. Créer des disponibilités récurrentes

```php
// Créer une disponibilité pour un docteur
$availability = availability_for($doctor)->create([
    'type' => 'consultation',
    'daily_start' => '09:00:00',
    'daily_end' => '17:00:00',
    'days' => ['monday', 'wednesday', 'friday'],
    'validity_start' => '2038-01-01',
    'validity_end' => '2038-12-31',
]);
```

### 3. Planifier des rendez-vous

```php
// Réserver un créneau dans cette disponibilité
$schedule = schedule_for($availability)->create([
    'title' => 'Consultation annuelle - Patient A',
    'start_datetime' => '2038-01-04 10:00:00',
    'end_datetime' => '2038-01-04 11:00:00',
    'status' => \Roster\Enums\ScheduleStatus::BOOKED,
    'metadata' => ['patient_id' => 123],
]);
```

### 4. Gérer les indisponibilités temporaires

```php
// Bloquer un créneau pour une formation
$impediment = impediment_for($availability)->create([
    'reason' => 'Formation médicale obligatoire',
    'start_datetime' => '2038-01-04 09:00:00',
    'end_datetime' => '2038-01-04 12:00:00',
]);
```

### 5. Rechercher des créneaux disponibles

```php
// Trouver le prochain créneau disponible
$nextSlot = schedule_for($availability)->findNextSlot(
    durationMinutes: 45,
    type: 'consultation',
    startFrom: now()->addDay()
);

// Vérifier la disponibilité pour un créneau spécifique
$isAvailable = schedule_for($availability)->isTimeSlotAvailable(
    start: '2038-01-06 14:00:00',
    end: '2038-01-06 15:00:00',
    type: 'consultation'
);
```

## 🔗 Système de liens polymorphiques pour horaires

Roster inclut un système avancé permettant d'associer n'importe quel modèle Eloquent à des horaires avec des métadonnées personnalisables.

### Attacher des ressources aux horaires

```php
use Roster\Traits\AttachableToSchedules;

// Ajouter le trait à vos modèles
class Room extends Model
{
    use AttachableToSchedules;
}

class Vehicle extends Model
{
    use AttachableToSchedules;
}

class Equipment extends Model
{
    use AttachableToSchedules;
}

// Utilisation : attacher des ressources à un horaire
$schedule = schedule_for($availability)->create([
    'title' => 'Chirurgie programmée',
    'start_datetime' => '2038-01-04 08:00:00',
    'end_datetime' => '2038-01-04 12:00:00',
]);

// Attacher des ressources avec métadonnées
$room = Room::find(1);
$vehicle = Vehicle::find(1);
$doctor = Doctor::find(1);

$service = schedule_for($availability)->schedule($schedule);

$service->attach($room, ['role' => 'salle_opératoire', 'equipement' => 'chirurgical']);
$service->attach($vehicle, ['role' => 'transport', 'urgent' => true]);
$service->attach($doctor, ['role' => 'chirurgien', 'specialite' => 'orthopédie']);

// Attacher plusieurs ressources en une fois
$service->attachMany([$room, $vehicle, $doctor], ['operation_id' => 'OP123']);
```

### Gérer les ressources attachées

```php
// Vérifier si une ressource est attachée
$service->hasAttached($room); // true

// Récupérer toutes les ressources attachées
$attachedResources = $service->getAttached();
// Collection contenant room, vehicle, doctor

// Filtrer par type de modèle
$rooms = $service->getAttachedByType(Room::class);
$doctors = $service->getAttachedByType(Doctor::class);

// Détacher des ressources
$service->detach($vehicle);
$service->detachMany([$room, $doctor]);

// Synchroniser complètement les ressources
$service->sync([$room, $doctor], ['session' => 'matin']);

// Détacher toutes les ressources
$service->detachAll();
```

### Utilisation directe depuis les modèles

```php
// Depuis un modèle attachable
$room->isAttachedToSchedule($schedule); // true/false
$room->attachToSchedule($schedule, ['role' => 'consultation']);
$room->detachFromSchedule($schedule);

// Récupérer tous les horaires avec métadonnées
$schedulesWithMetadata = $room->attachedSchedulesWithLinkMetadata();

// Filtrer par métadonnées
$surgeries = $room->attachedSchedulesWithMetadata('role', 'salle_opératoire');

// Synchroniser les horaires
$room->syncSchedules([$schedule1, $schedule2], ['default_room' => true]);
```

### Relations Eloquent

```php
// La relation polymorphique est automatiquement disponible
$room->attachedSchedules; // Collection d'horaires
$schedule->linkables; // Collection de modèles attachés (via pivot)

// Avec métadonnées du lien
$room->attachedSchedules()->withPivot('metadata')->get();
```

### Cas d'utilisation avancés

#### 1. Gestion de bloc opératoire

```php
// Préparer une chirurgie avec toutes les ressources nécessaires
$surgerySchedule = schedule_for($availability)->create([
    'title' => 'Arthroscopie du genou',
    'start_datetime' => '2038-01-04 08:00:00',
    'end_datetime' => '2038-01-04 10:00:00',
]);

$service = schedule_for($availability)->schedule($surgerySchedule);

$service->attach($operatingRoom, [
    'role' => 'salle_operation',
    'equipment' => ['arthroscope', 'moniteur', 'instruments'],
    'sterilization' => 'niveau_2'
]);

$service->attach($surgeon, [
    'role' => 'chirurgien_principal',
    'specialty' => 'orthopedie',
    'assistant_required' => true
]);

$service->attach($anesthesiologist, [
    'role' => 'anesthesiste',
    'type_anesthesia' => 'generale'
]);

$service->attach($nurse, [
    'role' => 'infirmiere_instrumentiste',
    'experience' => 'senior'
]);
```

#### 2. Réservation de ressources partagées

```php
// Deux horaires différents partageant les mêmes ressources
$schedule1 = schedule_for($availability)->create([...]);
$schedule2 = schedule_for($availability)->create([...]);

$sharedRoom = Room::find(1);
$sharedEquipment = Equipment::find(1);

$service1 = schedule_for($availability)->schedule($schedule1);
$service2 = schedule_for($availability)->schedule($schedule2);

$service1->attach($sharedRoom, ['usage' => 'consultation']);
$service2->attach($sharedRoom, ['usage' => 'formation']);

$service1->attach($sharedEquipment, ['reserved' => true]);
// Le système permet de suivre quelle ressource est utilisée où et quand
```

#### 3. Métadonnées complexes pour le suivi

```php
$service->attach($patient, [
    'medical_history' => ['hypertension', 'diabetes'],
    'insurance' => 'ABC Assurance',
    'priority' => 'high',
    'contact' => [
        'phone' => '555-0123',
        'email' => 'patient@example.com'
    ],
    'notes' => ['allergic to penicillin', 'needs interpreter']
]);
```

## 📖 Concepts de base

### Le principe d'immuabilité

Roster empêche les mutations directes des modèles pour garantir l'intégrité des données. Toutes les opérations doivent passer par les services appropriés :

```php
// ❌ INTERDIT : Modification directe
$availability->update(['daily_end' => '18:00:00']); // Lance une exception

// ✅ AUTORISÉ : Via le service
availability_for($doctor)->update($availability->id, [
    'daily_end' => '18:00:00'
]);
```

### Contexte unique par action

Chaque service est conçu pour une seule action avec son propre contexte :

```php
// ❌ INTERDIT : Réutilisation du service
$service = availability_for($doctor);
$service->create([...]);
$service->update(1, [...]); // Contexte corrompu

// ✅ AUTORISÉ : Nouveau contexte pour chaque action
availability_for($doctor)->create([...]);
availability_for($doctor)->update(1, [...]);
```

### Les 3 entités principales

1. **Availability** : Définit quand une ressource est disponible (jours, heures, période)
2. **Schedule** : Représente un créneau réservé dans une disponibilité
3. **Impediment** : Bloque temporairement une disponibilité

## 🛡️ Architecture sécurisée

### Contrôle d'accès aux mutations

Le système utilise deux contextes pour contrôler l'accès :

```php
// 1. Contexte de mutation (interne)
// Utilisé par les repositories pour autoriser les opérations CRUD
RosterMutationContext::allow(function () {
    return Availability::create([...]); // Autorisé dans ce contexte
});

// 2. Contexte de service (public)
// Utilisé par les helpers pour autoriser l'utilisation des services
RosterServiceContext::allow(function () {
    return $service->create([...]); // Autorisé via le helper
});
```

### Helpers sécurisés

Les helpers `availability_for()`, `schedule_for()`, et `impediment_for()` créent automatiquement le contexte nécessaire :

```php
// Ces helpers gèrent automatiquement :
// 1. La création du contexte d'exécution
// 2. La validation de l'entité planifiable
// 3. La prévention de la réutilisation
```

## 🔍 Recherche avancée et cohérence des données

### Méthode `first()` pour une recherche ciblée

```php
// Récupérer la première disponibilité correspondant à des critères
$availability = availability_for($doctor)
    ->whereType('consultation')
    ->first();

// Récupérer le premier rendez-vous à venir
$nextAppointment = schedule_for($availability)
    ->setFilter('start_datetime', '>', now())
    ->first();

// Récupérer le premier empêchement programmé
$firstImpediment = impediment_for($availability)
    ->setFilter('reason', 'like', '%training%')
    ->first();
```

### Cohérence automatique des jours

Le système assure automatiquement la cohérence entre les jours spécifiés et les périodes de validité :

```php
// Lors d'une mise à jour, les jours hors période sont automatiquement réconciliés
$availability = availability_for($doctor)->create([
    'validity_start' => '2024-01-01',
    'validity_end' => '2024-01-07', // Semaine du 1 au 7 janvier
    'days' => ['monday', 'wednesday', 'friday'],
]);

// Si vous étendez la période, les jours sont automatiquement ajustés
availability_for($doctor)->update($availability->id, [
    'validity_end' => '2024-01-14', // Deux semaines
    // Les jours restent cohérents avec la nouvelle période
]);

// Configuration du comportement de réconciliation
// Dans config/roster.php :
'reconciliation_warning' => env('ROSTER_RECONCILIATION_WARNING', false),
// Si true : avertissement PHP lorsque des jours sont hors période
// Si false : réconciliation silencieuse
```

### Tri standardisé des jours

Les fonctions utilitaires retournent toujours les jours dans l'ordre standard de la semaine (lundi → dimanche) :

```php
$days = roster_days_in_period('2024-01-01', '2024-01-07');
// Retourne: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']
// Trié automatiquement dans l'ordre standard
```

## 🎯 Validation métier exhaustive

Roster inclut **17 règles de validation** qui garantissent la cohérence du système :

### Règles principales :
- **SchedulableValidationRule** (110) - Vérifie la présence du contexte planifiable
- **RequiredFieldsRule** (100) - Valide les champs requis par opération
- **AvailabilityTemporalCoherenceRule** (100) - Assure la cohérence temporelle
- **TemporalConflictRule** (80) - Empêche les chevauchements de planning
- **AvailabilityOverlapRule** (80) - Empêche les chevauchements de disponibilités
- **TimeRangeRule** (85) - Valide les plages horaires (pas de spans multi-jours)

### Visualisation des règles :

```bash
# Lister toutes les règles disponibles
php artisan roster:debug-rules

# Voir les règles pour une entité spécifique
php artisan roster:debug-rules availability --operation=create
```

## 📊 Exemples d'utilisation réels

### Gestion de clinique médicale

```php
// Création de disponibilités pour différents spécialistes
$cardiologist = Doctor::where('specialty', 'cardiology')->first();
$availability = availability_for($cardiologist)->create([
    'type' => 'consultation',
    'daily_start' => '08:30:00',
    'daily_end' => '12:30:00',
    'days' => ['monday', 'wednesday', 'friday'],
    'validity_start' => '2024-01-01',
    'validity_end' => '2024-12-31',
]);

// Réservation patient
$appointment = schedule_for($availability)->create([
    'title' => 'Consultation cardiaque',
    'start_datetime' => '2024-06-10 10:00:00',
    'end_datetime' => '2024-06-10 11:00:00',
    'status' => ScheduleStatus::BOOKED,
    'metadata' => [
        'patient_id' => 'CARD001',
        'priority' => 'medium',
        'tests_required' => ['echocardiogram', 'stress_test']
    ],
]);

// Recherche rapide de la prochaine disponibilité
$nextAvailability = availability_for($cardiologist)
    ->setFilter('validity_start', '>', now())
    ->first();

// Gestion d'indisponibilité (formation)
impediment_for($availability)->create([
    'reason' => 'Formation continue',
    'start_datetime' => '2024-06-15 09:00:00',
    'end_datetime' => '2024-06-15 12:00:00',
    'metadata' => ['mandatory' => true, 'location' => 'Auditorium'],
]);
```

### Système de réservation de salle

```php
// Deux docteurs partageant une salle
$room = Room::find(1);

// Premier docteur utilise la salle le lundi
$doctor1Availability = availability_for($doctor1)->create([
    'type' => 'room_a',
    'daily_start' => '09:00:00',
    'daily_end' => '17:00:00',
    'days' => ['monday', 'wednesday', 'friday'],
    'validity_start' => '2024-01-01',
    'validity_end' => '2024-12-31',
]);

// Second docteur utilise la salle le mardi
$doctor2Availability = availability_for($doctor2)->create([
    'type' => 'room_a',
    'daily_start' => '09:00:00',
    'daily_end' => '17:00:00',
    'days' => ['tuesday', 'thursday'],
    'validity_start' => '2024-01-01',
    'validity_end' => '2024-12-31',
]);

// Recherche de la première disponibilité pour un créneau urgent
$urgentSlot = schedule_for($doctor1Availability)
    ->setFilter('status', ScheduleStatus::AVAILABLE)
    ->first();

// Le système empêche automatiquement les conflits
schedule_for($doctor1Availability)->create([
    'title' => 'Utilisation salle A - Dr. Smith',
    'start_datetime' => '2024-06-10 10:00:00', // Lundi
    'end_datetime' => '2024-06-10 12:00:00',
]);

// ❌ Cette réservation échouera (conflit inter-docteurs)
schedule_for($doctor2Availability)->create([
    'title' => 'Utilisation salle A - Dr. Jones',
    'start_datetime' => '2024-06-10 11:00:00', // Même jour que Dr. Smith
    'end_datetime' => '2024-06-10 13:00:00',
]);
```

### Gestion des empêchements récurrents

```php
// Création d'une disponibilité hebdomadaire
$weeklyAvailability = availability_for($doctor)->create([
    'type' => 'consultation',
    'daily_start' => '08:00:00',
    'daily_end' => '18:00:00',
    'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
    'validity_start' => '2024-01-01',
    'validity_end' => '2024-12-31',
]);

// Empêchements récurrents (pause déjeuner)
$weekdays = ['2024-01-08', '2024-01-09', '2024-01-10', '2024-01-11', '2024-01-12'];

foreach ($weekdays as $weekday) {
    impediment_for($weeklyAvailability)->create([
        'reason' => 'Pause déjeuner',
        'start_datetime' => Carbon::parse($weekday)->setTime(12, 0, 0),
        'end_datetime' => Carbon::parse($weekday)->setTime(13, 0, 0),
        'metadata' => ['type' => 'lunch', 'recurring' => true],
    ]);
}

// Trouver le premier créneau disponible après les empêchements
$firstAvailableSlot = schedule_for($weeklyAvailability)
    ->setFilter('start_datetime', '>', now())
    ->first();

// Trouver des créneaux disponibles malgré les empêchements
$availableSlots = schedule_for($weeklyAvailability)->findAvailableSlots(
    startDate: '2024-01-08',
    endDate: '2024-01-12',
    durationMinutes: 60,
    type: 'consultation'
);
```

## 🔧 API complète

### Service Availability

```php
// CRUD
availability_for($schedulable)->create($data);
availability_for($schedulable)->find($id);
availability_for($schedulable)->update($id, $data);
availability_for($schedulable)->delete($id);

// Recherche
availability_for($schedulable)->all();
availability_for($schedulable)->setFilter('type', 'consultation')->all();
availability_for($schedulable)->first(); // Nouvelle méthode

// Vérifications
availability_for($schedulable)->isAvailableOnDate($date, $type);
availability_for($schedulable)->getAvailabilityForTimeSlot($start, $end, $type);
```

### Service Schedule

```php
// Réservation
schedule_for($availability)->create($data);
schedule_for($availability)->update($id, $data);
schedule_for($availability)->delete($id);

// Recherche de créneaux
schedule_for($availability)->findNextSlot($durationMinutes, $type, $startFrom);
schedule_for($availability)->findAvailableSlots($startDate, $endDate, $durationMinutes, $type);
schedule_for($availability)->first(); // Nouvelle méthode

// Vérifications
schedule_for($availability)->isTimeSlotAvailable($start, $end, $type);
schedule_for($availability)->isPeriodAvailable($start, $end, $type);

// Gestion des liens polymorphiques
schedule_for($availability)->schedule($scheduleModel); // Définir le contexte
schedule_for($availability)->schedule($scheduleModel)->attach($model, $metadata);
schedule_for($availability)->schedule($scheduleModel)->detach($model);
schedule_for($availability)->schedule($scheduleModel)->getAttached();
schedule_for($availability)->schedule($scheduleModel)->sync($models, $metadata);
```

### Service Impediment

```php
// Gestion des empêchements
impediment_for($availability)->create($data);
impediment_for($availability)->update($id, $data);
impediment_for($availability)->delete($id);

// Recherche
impediment_for($availability)->first(); // Nouvelle méthode

// Vérifications
impediment_for($availability)->isTimeSlotBlocked($start, $end);
impediment_for($availability)->getAvailableTimeSlots($start, $end, $type);
```

## ⚙️ Configuration

### Fichier de configuration (`config/roster.php`)

```php
return [
    // Types d'activité autorisés
    'allowed_types' => [
        'consultation',
        'surgery',
        'emergency',
        'training',
        'room_a',
        'echography',
        'scan',
    ],

    // Durées minimales (en minutes)
    'durations' => [
        'minimum_availability_minutes' => 15,
        'minimum_schedule_minutes' => 15,
        'minimum_impediment_minutes' => 5,
        'max_search_period_days' => 365,
        'max_availability_days' => 365,
    ],

    // Cache des règles de validation
    'cache' => [
        'enabled' => env('ROSTER_CACHE_ENABLED', true),
        'cache_file' => storage_path('framework/cache/roster_rules.php'),
        'cache_max_age_hours' => 24,
    ],

    // Réconciliation des jours
    'reconciliation_warning' => env('ROSTER_RECONCILIATION_WARNING', false),
    // Contrôle le comportement lors des mises à jour lorsque des jours sont
    // en dehors de la période de validité :
    // - true : déclenche un avertissement PHP (E_USER_WARNING)
    // - false : réconciliation silencieuse
];
```

### Variables d'environnement

```env
ROSTER_TIMEZONE=Europe/Paris
ROSTER_CACHE_ENABLED=true
ROSTER_RECONCILIATION_WARNING=false
```

## 🧪 Tests complets

Le package inclut **2300 tests** couvrant tous les scénarios :

```bash
# Exécuter tous les tests
php artisan test

# Tests d'intégration
php artisan test --group=integration

# Tests de performance
php artisan test --filter=test_performance_and_load_scenario

# Tests de scénarios complexes
php artisan test --filter=test_real_world_complex_scenario
```

### Scénarios testés :
- ✅ Cycle de vie complet d'une disponibilité
- ✅ Gestion d'empêchement avec conflits
- ✅ Système de réservation intelligent
- ✅ Interactions complexes (disponibilités + empêchements + plannings)
- ✅ Conflits multi-utilisateurs avec ressources partagées
- ✅ Gestion des erreurs et cas limites
- ✅ Tests de performance avec données massives
- ✅ Récupération après erreurs
- ✅ Scénario complexe réaliste (hôpital avec multiples spécialistes)
- ✅ Cohérence des données avec réconciliation automatique
- ✅ Méthode `first()` pour la recherche ciblée
- ✅ Système de liens polymorphiques avec métadonnées
- ✅ Gestion des ressources attachées (salles, véhicules, équipements)
- ✅ Tests de synchronisation et de détachement

## 🚨 Gestion des erreurs

```php
use Roster\Validation\Exceptions\ValidationFailedException;

try {
    $schedule = schedule_for($availability)->create($data);
} catch (ValidationFailedException $e) {
    // Obtenir les violations détaillées avec information des règles
    $violations = $e->getViolations();
    // Tableau d'objets ViolationData contenant :
    // - nom du champ
    // - message d'erreur
    // - règle ayant déclenché la violation
    // - description de la règle pour le contexte

    $detailedReport = $e->toDetailedArray();
    // Inclut les descriptions des règles pour un meilleur débogage

    return response()->json([
        'error' => 'validation_failed',
        'message' => $e->getFormattedMessage(),
        'violations' => $detailedReport['violations'],
    ], 422);
}
```

### Gestion des avertissements de réconciliation

```php
// Configuration pour activer les avertissements
config()->set('roster.reconciliation_warning', true);

// Capturer les avertissements
set_error_handler(function ($errno, $errstr) {
    if ($errno === E_USER_WARNING && str_contains($errstr, 'outside the validity period')) {
        // Journaliser ou traiter l'avertissement
        Log::warning('Réconciliation de jours détectée', ['message' => $errstr]);
        return true; // Empêche la propagation
    }
    return false;
});

// Lors d'une mise à jour avec des jours hors période :
availability_for($doctor)->update($availability->id, [
    'validity_end' => '2024-01-10',
    'days' => ['monday', 'saturday'], // 'saturday' sera filtré avec avertissement
]);

restore_error_handler();
```

## 📊 Outils de développement

### Débogage des règles de validation

```bash
# Afficher toutes les règles
php artisan roster:debug-rules

# Filtrer par entité
php artisan roster:debug-rules availability

# Filtrer par opération
php artisan roster:debug-rules availability --operation=create

# Afficher les méthodes
php artisan roster:debug-rules availability --show-methods

# Afficher les sources
php artisan roster:debug-rules availability --show-source
```

### Gestion du cache

```bash
# Générer le cache des règles
php artisan roster:cache-rules

# Afficher les statistiques du cache
php artisan roster:cache-rules --show

# Effacer le cache
php artisan roster:cache-rules --clear

# Forcer la régénération
php artisan roster:cache-rules --force
```

## 🤝 Contribution

1. **Fork** le dépôt
2. **Créez une branche** (`git checkout -b feature/amazing-feature`)
3. **Commitez vos changements** (`git commit -m 'Add amazing feature'`)
4. **Poussez sur la branche** (`git push origin feature/amazing-feature`)
5. **Ouvrez une Pull Request**

### Exécuter les tests

```bash
# Tous les tests
composer test

# Avec couverture de code
composer test-coverage

# Vérifier le style de code
composer lint
```

## 📄 Licence

Ce package est open-source et disponible sous licence [MIT](LICENSE).

## 🔗 Liens utiles

- [Documentation API](docs/api.md)
- [Guide de migration](docs/migration.md)
- [Changelog](CHANGELOG.md)
- [Issues](https://github.com/vendor/laravel-roster/issues)

---

**Roster** - Une solution professionnelle pour la gestion avancée d'emplois du temps, conçue pour les applications critiques où chaque minute compte. ⚕️⏰✨

Avec des fonctionnalités avancées de recherche, de cohérence des données, de validation métier exhaustive et un système complet de liens polymorphiques, Roster assure l'intégrité de vos systèmes de planification dans les environnements les plus exigeants.