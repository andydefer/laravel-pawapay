# Laravel Roster

![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-blue)
![Laravel Version](https://img.shields.io/badge/Laravel-12%2B-orange)
![License](https://img.shields.io/badge/license-MIT-green)
![Tests](https://img.shields.io/badge/tests-2300%20passing-brightgreen)
![Coverage](https://img.shields.io/badge/coverage-88%25-green)

**Roster** is a comprehensive Laravel package for advanced scheduling, availability, and booking management. Built with a robust architecture, it handles recurring availability, booked slots, and impediments with exhaustive business validation.

## 📦 Installation

```bash
composer require andydefer/laravel-roster
```

Publish package resources:

```bash
php artisan roster:install
```

Or manually:

```bash
# Configuration
php artisan vendor:publish --tag=roster-config

# Migrations
php artisan vendor:publish --tag=roster-migrations

# Run migrations
php artisan migrate
```

## 🚀 Quick Start

### 1. Add the trait to your models

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

### 2. Create recurring availabilities

```php
// Create an availability for a doctor
$availability = availability_for($doctor)->create([
    'type' => 'consultation',
    'daily_start' => '09:00:00',
    'daily_end' => '17:00:00',
    'days' => ['monday', 'wednesday', 'friday'],
    'validity_start' => '2038-01-01',
    'validity_end' => '2038-12-31',
]);
```

### 3. Schedule appointments

```php
// Book a slot in this availability
$schedule = schedule_for($availability)->create([
    'title' => 'Annual Checkup - Patient A',
    'start_datetime' => '2038-01-04 10:00:00',
    'end_datetime' => '2038-01-04 11:00:00',
    'status' => \Roster\Enums\ScheduleStatus::BOOKED,
    'metadata' => ['patient_id' => 123],
]);
```

### 4. Manage temporary unavailability

```php
// Block a slot for training
$impediment = impediment_for($availability)->create([
    'reason' => 'Mandatory medical training',
    'start_datetime' => '2038-01-04 09:00:00',
    'end_datetime' => '2038-01-04 12:00:00',
]);
```

### 5. Search for available slots

```php
// Find the next available slot
$nextSlot = schedule_for($availability)->findNextSlot(
    durationMinutes: 45,
    type: 'consultation',
    startFrom: now()->addDay()
);

// Check availability for a specific slot
$isAvailable = schedule_for($availability)->isTimeSlotAvailable(
    start: '2038-01-06 14:00:00',
    end: '2038-01-06 15:00:00',
    type: 'consultation'
);
```

## 🔗 Polymorphic Scheduling Link System

Roster includes an advanced system that allows any Eloquent model to be associated with schedules with customizable metadata.

### Attach resources to schedules

```php
use Roster\Traits\AttachableToSchedules;

// Add the trait to your models
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

// Usage: attach resources to a schedule
$schedule = schedule_for($availability)->create([
    'title' => 'Scheduled Surgery',
    'start_datetime' => '2038-01-04 08:00:00',
    'end_datetime' => '2038-01-04 12:00:00',
]);

// Attach resources with metadata
$room = Room::find(1);
$vehicle = Vehicle::find(1);
$doctor = Doctor::find(1);

$service = schedule_for($availability)->schedule($schedule);

$service->attach($room, ['role' => 'operating_room', 'equipment' => 'surgical']);
$service->attach($vehicle, ['role' => 'transport', 'urgent' => true]);
$service->attach($doctor, ['role' => 'surgeon', 'specialty' => 'orthopedics']);

// Attach multiple resources at once
$service->attachMany([$room, $vehicle, $doctor], ['operation_id' => 'OP123']);
```

### Manage attached resources

```php
// Check if a resource is attached
$service->hasAttached($room); // true

// Retrieve all attached resources
$attachedResources = $service->getAttached();
// Collection containing room, vehicle, doctor

// Filter by model type
$rooms = $service->getAttachedByType(Room::class);
$doctors = $service->getAttachedByType(Doctor::class);

// Detach resources
$service->detach($vehicle);
$service->detachMany([$room, $doctor]);

// Synchronize resources completely
$service->sync([$room, $doctor], ['session' => 'morning']);

// Detach all resources
$service->detachAll();
```

### Direct usage from models

```php
// From an attachable model
$room->isAttachedToSchedule($schedule); // true/false
$room->attachToSchedule($schedule, ['role' => 'consultation']);
$room->detachFromSchedule($schedule);

// Get all schedules with metadata
$schedulesWithMetadata = $room->attachedSchedulesWithLinkMetadata();

// Filter by metadata
$surgeries = $room->attachedSchedulesWithMetadata('role', 'operating_room');

// Synchronize schedules
$room->syncSchedules([$schedule1, $schedule2], ['default_room' => true]);
```

### Eloquent relationships

```php
// The polymorphic relationship is automatically available
$room->attachedSchedules; // Collection of schedules
$schedule->linkables; // Collection of attached models (via pivot)

// With link metadata
$room->attachedSchedules()->withPivot('metadata')->get();
```

### Advanced use cases

#### 1. Operating room management

```php
// Prepare surgery with all necessary resources
$surgerySchedule = schedule_for($availability)->create([
    'title' => 'Knee Arthroscopy',
    'start_datetime' => '2038-01-04 08:00:00',
    'end_datetime' => '2038-01-04 10:00:00',
]);

$service = schedule_for($availability)->schedule($surgerySchedule);

$service->attach($operatingRoom, [
    'role' => 'operating_room',
    'equipment' => ['arthroscope', 'monitor', 'instruments'],
    'sterilization' => 'level_2'
]);

$service->attach($surgeon, [
    'role' => 'primary_surgeon',
    'specialty' => 'orthopedics',
    'assistant_required' => true
]);

$service->attach($anesthesiologist, [
    'role' => 'anesthesiologist',
    'type_anesthesia' => 'general'
]);

$service->attach($nurse, [
    'role' => 'instrument_nurse',
    'experience' => 'senior'
]);
```

#### 2. Shared resource booking

```php
// Two different schedules sharing the same resources
$schedule1 = schedule_for($availability)->create([...]);
$schedule2 = schedule_for($availability)->create([...]);

$sharedRoom = Room::find(1);
$sharedEquipment = Equipment::find(1);

$service1 = schedule_for($availability)->schedule($schedule1);
$service2 = schedule_for($availability)->schedule($schedule2);

$service1->attach($sharedRoom, ['usage' => 'consultation']);
$service2->attach($sharedRoom, ['usage' => 'training']);

$service1->attach($sharedEquipment, ['reserved' => true]);
// The system tracks which resource is used where and when
```

#### 3. Complex metadata for tracking

```php
$service->attach($patient, [
    'medical_history' => ['hypertension', 'diabetes'],
    'insurance' => 'ABC Insurance',
    'priority' => 'high',
    'contact' => [
        'phone' => '555-0123',
        'email' => 'patient@example.com'
    ],
    'notes' => ['allergic to penicillin', 'needs interpreter']
]);
```

# 📋 Méthodes de Requête Modèle (Trait HasRoster)

Le trait `HasRoster` inclut des méthodes pour récupérer les impediments et schedules d'un modèle dans une période donnée.

## Méthodes Ajoutées

```php
// 1. Récupérer tous les items (impediments + schedules) dans une période
$items = $model->getRosterItemsInPeriod($start, $end);
// Retourne: ['impediments' => Collection, 'schedules' => Collection]

// 2. Récupérer seulement les impediments dans une période
$impediments = $model->getImpedimentsInPeriod($start, $end);

// 3. Récupérer seulement les schedules dans une période
$schedules = $model->getSchedulesInPeriod($start, $end);

// 4. Vérifier s'il y a des conflits
$hasConflicts = $model->hasConflictsInPeriod($start, $end);
// Retourne true si au moins un impediment ou schedule existe
```

## Exemple Simple

```php
// Un médecin avec le trait HasRoster
$doctor = Doctor::find(1);

// Vérifier la disponibilité pour demain 10h-11h
$start = Carbon::parse('2024-06-10 10:00:00');
$end = Carbon::parse('2024-06-10 11:00:00');

// Vérifier les conflits
if ($doctor->hasConflictsInPeriod($start, $end)) {
    // Récupérer les détails
    $conflicts = $doctor->getRosterItemsInPeriod($start, $end);

    echo "Schedules en conflit: " . $conflicts['schedules']->count();
    echo "Impediments en conflit: " . $conflicts['impediments']->count();
} else {
    echo "Créneau disponible";
}
```

## Cas d'Usage Pratique

```php
// Avant de créer un nouveau schedule
public function createSchedule(Doctor $doctor, array $data)
{
    $start = Carbon::parse($data['start_datetime']);
    $end = Carbon::parse($data['end_datetime']);

    // Vérifier si le créneau est libre
    if ($doctor->hasConflictsInPeriod($start, $end)) {
        return response()->json([
            'error' => 'Créneau non disponible',
            'conflicts' => $doctor->getRosterItemsInPeriod($start, $end)
        ], 422);
    }

    // Créer le schedule
    return schedule_for($doctor->availabilities()->first())
        ->create($data);
}
```


## 📖 Core Concepts

### Immutability Principle

Roster prevents direct model mutations to ensure data integrity. All operations must go through appropriate services:

```php
// ❌ FORBIDDEN: Direct modification
$availability->update(['daily_end' => '18:00:00']); // Throws exception

// ✅ ALLOWED: Via service
availability_for($doctor)->update($availability->id, [
    'daily_end' => '18:00:00'
]);
```

### Single-action context

Each service is designed for a single action with its own context:

```php
// ❌ FORBIDDEN: Service reuse
$service = availability_for($doctor);
$service->create([...]);
$service->update(1, [...]); // Corrupted context

// ✅ ALLOWED: New context for each action
availability_for($doctor)->create([...]);
availability_for($doctor)->update(1, [...]);
```

### The 3 main entities

1. **Availability**: Defines when a resource is available (days, times, period)
2. **Schedule**: Represents a booked slot in an availability
3. **Impediment**: Temporarily blocks an availability

## 🛡️ Secure Architecture

### Mutation access control

The system uses two contexts to control access:

```php
// 1. Mutation context (internal)
// Used by repositories to allow CRUD operations
RosterMutationContext::allow(function () {
    return Availability::create([...]); // Allowed in this context
});

// 2. Service context (public)
// Used by helpers to allow service usage
RosterServiceContext::allow(function () {
    return $service->create([...]); // Allowed via helper
});
```

### Secure helpers

The `availability_for()`, `schedule_for()`, and `impediment_for()` helpers automatically create the necessary context:

```php
// These helpers automatically handle:
// 1. Execution context creation
// 2. Schedulable entity validation
// 3. Reuse prevention
```

## 🔍 Advanced Search and Data Consistency

### `first()` method for targeted search

```php
// Retrieve the first availability matching criteria
$availability = availability_for($doctor)
    ->whereType('consultation')
    ->first();

// Retrieve the next upcoming appointment
$nextAppointment = schedule_for($availability)
    ->setFilter('start_datetime', '>', now())
    ->first();

// Retrieve the first scheduled impediment
$firstImpediment = impediment_for($availability)
    ->setFilter('reason', 'like', '%training%')
    ->first();
```

### Automatic days consistency

The system automatically ensures consistency between specified days and validity periods:

```php
// During an update, days outside the period are automatically reconciled
$availability = availability_for($doctor)->create([
    'validity_start' => '2024-01-01',
    'validity_end' => '2024-01-07', // Week from January 1-7
    'days' => ['monday', 'wednesday', 'friday'],
]);

// If you extend the period, days are automatically adjusted
availability_for($doctor)->update($availability->id, [
    'validity_end' => '2024-01-14', // Two weeks
    // Days remain consistent with the new period
]);

// Reconciliation behavior configuration
// In config/roster.php:
'reconciliation_warning' => env('ROSTER_RECONCILIATION_WARNING', false),
// If true: PHP warning when days are outside the period
// If false: silent reconciliation
```

### Standardized days sorting

Utility functions always return days in standard week order (Monday → Sunday):

```php
$days = roster_days_in_period('2024-01-01', '2024-01-07');
// Returns: ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']
// Automatically sorted in standard order
```

## 🎯 Exhaustive Business Validation

Roster includes **17 validation rules** that guarantee system consistency:

### Main rules:
- **SchedulableValidationRule** (110) - Checks for schedulable context presence
- **RequiredFieldsRule** (100) - Validates required fields per operation
- **AvailabilityTemporalCoherenceRule** (100) - Ensures temporal coherence
- **TemporalConflictRule** (80) - Prevents scheduling overlaps
- **AvailabilityOverlapRule** (80) - Prevents availability overlaps
- **TimeRangeRule** (85) - Validates time ranges (no multi-day spans)

### Rule visualization:

```bash
# List all available rules
php artisan roster:debug-rules

# See rules for a specific entity
php artisan roster:debug-rules availability --operation=create
```

## 📊 Real-world Usage Examples

### Medical clinic management

```php
// Create availabilities for different specialists
$cardiologist = Doctor::where('specialty', 'cardiology')->first();
$availability = availability_for($cardiologist)->create([
    'type' => 'consultation',
    'daily_start' => '08:30:00',
    'daily_end' => '12:30:00',
    'days' => ['monday', 'wednesday', 'friday'],
    'validity_start' => '2024-01-01',
    'validity_end' => '2024-12-31',
]);

// Patient booking
$appointment = schedule_for($availability)->create([
    'title' => 'Cardiac Consultation',
    'start_datetime' => '2024-06-10 10:00:00',
    'end_datetime' => '2024-06-10 11:00:00',
    'status' => ScheduleStatus::BOOKED,
    'metadata' => [
        'patient_id' => 'CARD001',
        'priority' => 'medium',
        'tests_required' => ['echocardiogram', 'stress_test']
    ],
]);

// Quick search for next availability
$nextAvailability = availability_for($cardiologist)
    ->setFilter('validity_start', '>', now())
    ->first();

// Manage unavailability (training)
impediment_for($availability)->create([
    'reason' => 'Continuing education',
    'start_datetime' => '2024-06-15 09:00:00',
    'end_datetime' => '2024-06-15 12:00:00',
    'metadata' => ['mandatory' => true, 'location' => 'Auditorium'],
]);
```

### Room booking system

```php
// Two doctors sharing a room
$room = Room::find(1);

// First doctor uses the room on Monday
$doctor1Availability = availability_for($doctor1)->create([
    'type' => 'room_a',
    'daily_start' => '09:00:00',
    'daily_end' => '17:00:00',
    'days' => ['monday', 'wednesday', 'friday'],
    'validity_start' => '2024-01-01',
    'validity_end' => '2024-12-31',
]);

// Second doctor uses the room on Tuesday
$doctor2Availability = availability_for($doctor2)->create([
    'type' => 'room_a',
    'daily_start' => '09:00:00',
    'daily_end' => '17:00:00',
    'days' => ['tuesday', 'thursday'],
    'validity_start' => '2024-01-01',
    'validity_end' => '2024-12-31',
]);

// Search for first availability for urgent slot
$urgentSlot = schedule_for($doctor1Availability)
    ->setFilter('status', ScheduleStatus::AVAILABLE)
    ->first();

// System automatically prevents conflicts
schedule_for($doctor1Availability)->create([
    'title' => 'Room A usage - Dr. Smith',
    'start_datetime' => '2024-06-10 10:00:00', // Monday
    'end_datetime' => '2024-06-10 12:00:00',
]);

// ❌ This booking will fail (inter-doctor conflict)
schedule_for($doctor2Availability)->create([
    'title' => 'Room A usage - Dr. Jones',
    'start_datetime' => '2024-06-10 11:00:00', // Same day as Dr. Smith
    'end_datetime' => '2024-06-10 13:00:00',
]);
```

### Recurrent impediment management

```php
// Create weekly availability
$weeklyAvailability = availability_for($doctor)->create([
    'type' => 'consultation',
    'daily_start' => '08:00:00',
    'daily_end' => '18:00:00',
    'days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
    'validity_start' => '2024-01-01',
    'validity_end' => '2024-12-31',
]);

// Recurrent impediments (lunch break)
$weekdays = ['2024-01-08', '2024-01-09', '2024-01-10', '2024-01-11', '2024-01-12'];

foreach ($weekdays as $weekday) {
    impediment_for($weeklyAvailability)->create([
        'reason' => 'Lunch break',
        'start_datetime' => Carbon::parse($weekday)->setTime(12, 0, 0),
        'end_datetime' => Carbon::parse($weekday)->setTime(13, 0, 0),
        'metadata' => ['type' => 'lunch', 'recurring' => true],
    ]);
}

// Find first available slot after impediments
$firstAvailableSlot = schedule_for($weeklyAvailability)
    ->setFilter('start_datetime', '>', now())
    ->first();

// Find available slots despite impediments
$availableSlots = schedule_for($weeklyAvailability)->findAvailableSlots(
    startDate: '2024-01-08',
    endDate: '2024-01-12',
    durationMinutes: 60,
    type: 'consultation'
);
```

## 🔧 Complete API

### Availability Service

```php
// CRUD
availability_for($schedulable)->create($data);
availability_for($schedulable)->find($id);
availability_for($schedulable)->update($id, $data);
availability_for($schedulable)->delete($id);

// Search
availability_for($schedulable)->all();
availability_for($schedulable)->setFilter('type', 'consultation')->all();
availability_for($schedulable)->first(); // New method

// Checks
availability_for($schedulable)->isAvailableOnDate($date, $type);
availability_for($schedulable)->getAvailabilityForTimeSlot($start, $end, $type);
```

### Schedule Service

```php
// Booking
schedule_for($availability)->create($data);
schedule_for($availability)->update($id, $data);
schedule_for($availability)->delete($id);

// Slot search
schedule_for($availability)->findNextSlot($durationMinutes, $type, $startFrom);
schedule_for($availability)->findAvailableSlots($startDate, $endDate, $durationMinutes, $type);
schedule_for($availability)->first(); // New method

// Checks
schedule_for($availability)->isTimeSlotAvailable($start, $end, $type);
schedule_for($availability)->isPeriodAvailable($start, $end, $type);

// Polymorphic link management
schedule_for($availability)->schedule($scheduleModel); // Set context
schedule_for($availability)->schedule($scheduleModel)->attach($model, $metadata);
schedule_for($availability)->schedule($scheduleModel)->detach($model);
schedule_for($availability)->schedule($scheduleModel)->getAttached();
schedule_for($availability)->schedule($scheduleModel)->sync($models, $metadata);
```

### Impediment Service

```php
// Impediment management
impediment_for($availability)->create($data);
impediment_for($availability)->update($id, $data);
impediment_for($availability)->delete($id);

// Search
impediment_for($availability)->first(); // New method

// Checks
impediment_for($availability)->isTimeSlotBlocked($start, $end);
impediment_for($availability)->getAvailableTimeSlots($start, $end, $type);
```

## ⚙️ Configuration

### Configuration file (`config/roster.php`)

```php
return [
    // Allowed activity types
    'allowed_types' => [
        'consultation',
        'surgery',
        'emergency',
        'training',
        'room_a',
        'echography',
        'scan',
    ],

    // Minimum durations (in minutes)
    'durations' => [
        'minimum_availability_minutes' => 15,
        'minimum_schedule_minutes' => 15,
        'minimum_impediment_minutes' => 5,
        'max_search_period_days' => 365,
        'max_availability_days' => 365,
    ],

    // Validation rule cache
    'cache' => [
        'enabled' => env('ROSTER_CACHE_ENABLED', true),
        'cache_file' => storage_path('framework/cache/roster_rules.php'),
        'cache_max_age_hours' => 24,
    ],

    // Days reconciliation
    'reconciliation_warning' => env('ROSTER_RECONCILIATION_WARNING', false),
    // Controls behavior during updates when days are
    // outside the validity period:
    // - true: triggers a PHP warning (E_USER_WARNING)
    // - false: silent reconciliation
];
```

### Environment variables

```env
ROSTER_TIMEZONE=Europe/Paris
ROSTER_CACHE_ENABLED=true
ROSTER_RECONCILIATION_WARNING=false
```

## 🧪 Comprehensive Tests

The package includes **2300 tests** covering all scenarios:

```bash
# Run all tests
php artisan test

# Integration tests
php artisan test --group=integration

# Performance tests
php artisan test --filter=test_performance_and_load_scenario

# Complex scenario tests
php artisan test --filter=test_real_world_complex_scenario
```

### Tested scenarios:
- ✅ Full availability lifecycle
- ✅ Impediment management with conflicts
- ✅ Intelligent booking system
- ✅ Complex interactions (availabilities + impediments + schedules)
- ✅ Multi-user conflicts with shared resources
- ✅ Error handling and edge cases
- ✅ Performance testing with massive data
- ✅ Recovery after errors
- ✅ Realistic complex scenario (hospital with multiple specialists)
- ✅ Data consistency with automatic reconciliation
- ✅ `first()` method for targeted search
- ✅ Polymorphic link system with metadata
- ✅ Attached resource management (rooms, vehicles, equipment)
- ✅ Synchronization and detachment tests

## 🚨 Error Handling

```php
use Roster\Validation\Exceptions\ValidationFailedException;

try {
    $schedule = schedule_for($availability)->create($data);
} catch (ValidationFailedException $e) {
    // Get detailed violations with rule information
    $violations = $e->getViolations();
    // Array of ViolationData objects containing:
    // - field name
    // - error message
    // - rule that triggered the violation
    // - rule description for context

    $detailedReport = $e->toDetailedArray();
    // Includes rule descriptions for better debugging

    return response()->json([
        'error' => 'validation_failed',
        'message' => $e->getFormattedMessage(),
        'violations' => $detailedReport['violations'],
    ], 422);
}
```

### Reconciliation warning handling

```php
// Configuration to enable warnings
config()->set('roster.reconciliation_warning', true);

// Capture warnings
set_error_handler(function ($errno, $errstr) {
    if ($errno === E_USER_WARNING && str_contains($errstr, 'outside the validity period')) {
        // Log or handle the warning
        Log::warning('Days reconciliation detected', ['message' => $errstr]);
        return true; // Prevents propagation
    }
    return false;
});

// During an update with days outside the period:
availability_for($doctor)->update($availability->id, [
    'validity_end' => '2024-01-10',
    'days' => ['monday', 'saturday'], // 'saturday' will be filtered with warning
]);

restore_error_handler();
```

## 📊 Development Tools

### Validation rule debugging

```bash
# Display all rules
php artisan roster:debug-rules

# Filter by entity
php artisan roster:debug-rules availability

# Filter by operation
php artisan roster:debug-rules availability --operation=create

# Display methods
php artisan roster:debug-rules availability --show-methods

# Display sources
php artisan roster:debug-rules availability --show-source
```

### Cache management

```bash
# Generate rule cache
php artisan roster:cache-rules

# Display cache statistics
php artisan roster:cache-rules --show

# Clear cache
php artisan roster:cache-rules --clear

# Force regeneration
php artisan roster:cache-rules --force
```

## 🤝 Contribution

1. **Fork** the repository
2. **Create a branch** (`git checkout -b feature/amazing-feature`)
3. **Commit your changes** (`git commit -m 'Add amazing feature'`)
4. **Push to the branch** (`git push origin feature/amazing-feature`)
5. **Open a Pull Request**

### Run tests

```bash
# All tests
composer test

# With code coverage
composer test-coverage

# Check code style
composer lint
```

## 📄 License

This package is open-source and available under the [MIT](LICENSE) license.

## 🔗 Useful Links

- [API Documentation](docs/api.md)
- [Migration Guide](docs/migration.md)
- [Changelog](CHANGELOG.md)
- [Issues](https://github.com/vendor/laravel-roster/issues)

---

**Roster** - A professional solution for advanced scheduling management, designed for critical applications where every minute counts. ⚕️⏰✨

With advanced search features, data consistency, exhaustive business validation, and a comprehensive polymorphic link system, Roster ensures the integrity of your scheduling systems in the most demanding environments.
