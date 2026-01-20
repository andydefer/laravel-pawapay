**Table des Matières - Roster Package**

## Introduction
- Présentation du package
- Pourquoi utiliser Roster ?
- Cas d'utilisation principaux
- Fonctionnalités clés

## Installation & Configuration
### Installation
- Installation via Composer
- Enregistrement du provider
- Configuration automatique
- Configuration manuelle

### Configuration initiale
- Fichier de configuration
- Paramètres essentiels
- Personnalisation basique
- Validation initiale

## Guide de démarrage rapide
### Premier pas
- Création d'une disponibilité
- Création d'un schedule
- Gestion d'un impediment
- Exemple complet

### Concepts de base
- Entités schedulables
- Disponibilités (Availabilities)
- Schedules (créneaux)
- Impediments (blocages)

## Système de validation
### Introduction
- Architecture de validation
- Cache des règles
- Contexte de validation

### Règles automatiques
- Détection automatique
- Attributs de validation
- Priorité des règles
- Exécution ordonnée

### Règles principales

#### Validation temporelle
1. **TemporalConflictRule**
2. **AvailabilityOverlapRule**
3. **AvailabilityTemporalCoherenceRule**
4. **FutureDateRule**
5. **TimeRangeRule**
6. **DurationRule**
7. **AvailabilityDateRangeRule**
8. **TimeSlotDateTimeRule**
9. **TimezoneValidationRule**

#### Validation de cohérence
10. **SchedulableValidationRule**
11. **SchedulableConsistencyRule**
12. **AvailabilityOwnershipRule**
13. **ImpedimentScheduleDaysCoherenceRule**
14. **AvailabilityDaysCoherenceRule**
15. **DaysValidationRule**

#### Validation de format
16. **RequiredFieldsRule**
17. **AvailabilityTypeRule**
18. **TimezoneValidationRule**

### Gestion des erreurs
- **Gestion des violations**
- **Formats de sortie**

## Services & Helpers
### Services contextuels
- **AvailabilityService** - `availability_for()`
- **ScheduleService** - `schedule_for()`
- **ImpedimentService** - `impediment_for()`

## Concepts clés
### No Direct Mutation (RosterMutationContext)
- **Protection absolue des modèles**
- **Contexte obligatoire**
- **Mécanisme de sécurité**
- **Exceptions de sécurité**
- **Intégrité garantie**

### Un call = Une action (RosterServiceContext)
- **Contexte d'exécution des services**
- **Simplication via helpers**
- **Modèles en lecture seule**
- **Prévention d'utilisation directe**

## Système de cache
### Cache des règles
- Génération du cache
- Régénération automatique
- Nettoyage du cache
- Performance du cache

### Commandes de cache
- Génération manuelle
- Affichage des statistiques
- Liste des règles
- Force regeneration

## DTOs (Data Transfer Objects)
### Création de DTOs
- Structure de base
- Conversion depuis un modèle
- Conversion depuis un tableau
- Validation des données

## Base de données & Modèles
### Migrations
- Structure de base
- Index optimisés
- Relations polymorphes (HasRoster)

### Modèles Eloquent
#### Availability
- Relations principales
- Casts personnalisés
- Scopes de requête
- Méthodes utilitaires

#### Schedule
- Relations aux disponibilités
- Gestion des statuts
- Validation automatique
- Méthodes métier

#### Impediment
- Relations temporelles
- Gestion des raisons
- Validation des conflits
- Méthodes spécifiques

### Casts personnalisés
- TimezoneAwareDateTimeCast
- Conversion automatique
- Gestion des timezones
- Casts personnalisés

## Commandes Artisan
### Commandes de debug
- `roster:debug-rules`
- Analyse des règles
- Filtrage par entité
- Filtrage par opération

### Commandes de gestion
- `roster:cache-rules`
- Gestion du cache
- Statistiques
- Régénération

### Commandes d'installation
- `roster:install`
- Installation complète
- Configuration automatique
- Vérifications

## Utilisation avancée
### Observers
- EnforceDomainMutationObserver
- Protection des modèles
- Sécurité des mutations
- Configuration

### Middleware
- SetUserTimezone
- Détection automatique
- Gestion des timezones
- Priorité des sources

## Dépannage
### Problèmes courants
- Installation
- Configuration
- Validation
- Performances

### Exceptions
- Exceptions de validation
- Erreurs de contexte et autres

## Annexes
### Référence API
- Services complets
- Méthodes disponibles
- Paramètres d'entrée
- Valeurs de retour

### Changelog
- Historique des versions
- Nouvelles fonctionnalités
- Corrections de bugs
- Dépréciations

### À propos
- Équipe de développement
- Philosophie du projet
- Licence
