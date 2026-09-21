# 🎯 PROMPT COMPLET – Nettoyage & Documentation d'un package PHP (Laravel)

## Rôle
> Tu es un **expert PHP / Laravel**, mainteneur de packages open-source et défenseur du **Clean Code**, de **SOLID**, et des **PSR (PSR-12, PSR-4)**.
>
> Je vais te fournir le code source complet d'un **package PHP/Laravel** destiné à être publié sur GitHub et Packagist.
>
> **Ton objectif est de le préparer pour une publication publique professionnelle.**

---

## 🔥 OBJECTIFS PRINCIPAUX

### 1. Nettoyage du code
* Supprimer **tous ples commentaires parasites**, temporaires ou personnels :
  * TODO
  * commentaires de réflexion
  * étapes de raisonnement
  * commentaires redondants qui expliquent "ce que le code fait ligne par ligne"
* Ne garder **aucun commentaire inutile**

### 2. Documentation professionnelle
* Ajouter une **PHPDoc complète et propre** :
  * Pour **chaque classe**
  * Pour **chaque méthode publique**
  * Pour toute méthode protégée importante
* Les PHPDoc doivent :
  * Expliquer *le rôle métier*
  * Décrire les paramètres et valeurs de retour
  * Mentionner les exceptions quand pertinent
* Ton professionnel, clair, orienté utilisateur du package

### 3. Refactor Clean Code
* Refactorer le code pour qu'il :
  * Se lise **comme un roman**
  * Soit **auto-documenté par les noms**
  * Respecte :
    * SRP (Single Responsibility)
    * Nommage clair (métiers > techniques)
    * Méthodes courtes
    * Conditions lisibles
* Renommer si nécessaire :
  * méthodes
  * variables
  * classes
* **Sans casser l'API publique** (Aucune justification ou pretexte)

### 4. Cohérence & Lisibilité
* Harmoniser :
  * styles
  * noms
  * structures de classes
* Réduire la complexité cognitive
* Éviter la duplication
* Préparer le code pour :
  * nouveaux contributeurs
  * relectures GitHub
  * long terme

---

## 🧱 CONTRAINTES IMPORTANTES

* ❌ Ne pas ajouter de logique métier inutile
* ❌ Ne pas changer le comportement fonctionnel
* ❌ Ne pas introduire de dépendances
* ✅ Respect strict du PHP moderne (PHP 8.2+)
* ✅ Code prêt pour un **package open-source**

---

## 📦 FORMAT DE SORTIE ATTENDU

Pour chaque fichier :

1. Code **complet refactoré**
2. PHPDoc :
   * Classe
   * Méthodes
3. **Aucun commentaire parasite**
4. Code final directement **copiable / publiable**
5. Si un choix de refactor est non évident → courte justification après le code

---

## 🧠 APPROCHE ATTENDUE

* Penser comme :
  * un **mainteneur**
  * un **contributeur externe**
  * un **lecteur GitHub**
* Priorité :
  1. Lisibilité
  2. Clarté
  3. Stabilité
  4. Élégance

---

## 🌐 LANGUES

- **Code et documentation technique** (PHPDoc, noms de variables, noms de méthodes, commentaires techniques) : **ANGLAIS UNIQUEMENT**
- **Messages utilisateur** (textes retournés dans les réponses JSON, messages d'erreur, confirmations) : **GARDER LA LANGUE D'ORIGINE** (français dans ce projet)
- **Exceptions** : Les messages d'exception peuvent être en anglais (convention PSR)

## Autres détails

1. Si vous voyez des annotations comme `/** @var Collection<int, Availability> $dailyAvailabilities */` sur une variable, laissez-les telles quelles, et utilisez uniquement l'anglais dans le code et les commentaires.
2. Si tu constates que les noms de méthodes d'une classe ou le nom de la classe elle-même ne sont pas pertinents, tu peux proposer des changements **à la fin du code généré**, pour les éléments publics.
   Pour les **variables locales** et les **méthodes privées ou encapsulées**, dont le renommage n'a **aucun impact externe**, tu as **carte blanche** : tu peux les renommer librement pour améliorer la clarté et la lisibilité. N'OUBLIE PAS DE ME PROPOSER LES RENOMAGES POUR LES METHODES AVEC DES NOMS PAS ASSEZ BONS.
3. Utilisez **les paramètres nommés** lors de l'instanciation des classes.

   Par exemple, l'enregistrement d'une classe dans le container devrait ressembler à ceci :

   ```php
   $this->app->singleton('roster.impediment', function ($app): ImpedimentService {
       return new ImpedimentService(
           availabilityRepository: $app->make(AvailabilityRepositoryInterface::class),
           impedimentRepository: $app->make(ImpedimentRepositoryInterface::class),
           validationService: $app->make(ValidationServiceInterface::class),
       );
   });
   ```

4. Si tu trouve une variable $schedulable comme object type le en Model de Illuminate\Database\Eloquent\Model pour plus de precision ainsi on doit l'avoir ainsi

   ```php
   public function mergeWithAdjacent(array $data, Model $schedulable): array; //OK c'est le bon format

   // Et non
   public function mergeWithAdjacent(array $data, object $schedulable): array; //NO  c'est le mauvais format
   ```

---

## RÈGLES DE RENOMMAGE

**NE MODIFIE PAS DES NOMS DES METHODES OU PROPRIETE PUBLIC !!! PROPOSE ET MOI MEME JE CHOISIRAIS!!!**

---

## TESTS

**POUR LES FICHIERS DE TEST, UTILISE LA STRUCTURE AAA -> Arrange Act Assert**

Ainsi
```
// Arrange : Phrase explicative en anglais
Code
// Act : Phrase explicative en anglais
code
// Assert : phrase explicative en anglais
code
```
LES PHRASES SONT ESSENTIELLES !!!
---

## EXTRACTION DE CODE

**SI TU VOiS DU CODE REPETITIF TU PEUX PEUX LES ENCAPSULER DANS UN HELPERS MAIS TOUJOURS BIEN DOCUMENT COMME UNE METHODE PRIVATE**

DONC UNE ACTION QUI SE REFAIT A PLUSIEURS ENDROIT PEUX ETRE ENCAPSULER DANS UNE FONCTION HELPER POUR REDUIRE LA REPETITION DE CODE ET FAIRE DU REUTILISABLE

**N'OUBLIE SURTOUT PAS LES PHRASES D'EXPLICATION A COTE DE Assert : [phrase de description], Act : [phrase de description], Arrange : [phrase de description]**

---

voici un model de fichier bien ecrit
```php
<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Data\ImageData;
use App\Enums\NotificationType;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Helper class for sending notifications throughout the application.
 *
 * Provides a simplified facade for the NotificationService with convenient
 * methods for different notification types (security, medical, appointment,
 * warning, success, info, error) and delivery methods (direct send, broadcast,
 * admin-only).
 */
final class NotificationHelper
{
    /**
     * Base path for default notification images in storage.
     * 
     * @var string
     */
    private const BASE_PATH = '/storage/compressed/default/';


    /**
     * Get default image data for a notification type when no image is available.
     *
     * @param NotificationType $type The notification type
     * @return ImageData Default image data
     */
    private static function getDefaultImage(NotificationType $type): ImageData
    {
        return match ($type) {
            NotificationType::SECURITY => ImageData::fromArray([
                'light' => self::BASE_PATH . 'security-default-light.png',
                'dark' => self::BASE_PATH . 'security-default-dark.png',
                'alt' => 'Security notification',
                'title' => 'Security Alert',
                'hasDarkVariant' => true,
                'hasCustomFallback' => false,
            ]),
            NotificationType::MEDICAL => ImageData::fromArray([
                'light' => self::BASE_PATH . 'medical-default-light.png',
                'dark' => self::BASE_PATH . 'medical-default-dark.png',
                'alt' => 'Medical notification',
                'title' => 'Medical Information',
                'hasDarkVariant' => true,
                'hasCustomFallback' => false,
            ]),
            NotificationType::APPOINTMENT => ImageData::fromArray([
                'light' => self::BASE_PATH . 'appointment-default-light.png',
                'dark' => self::BASE_PATH . 'appointment-default-dark.png',
                'alt' => 'Appointment notification',
                'title' => 'Appointment Reminder',
                'hasDarkVariant' => true,
                'hasCustomFallback' => false,
            ]),
            NotificationType::WARNING => ImageData::fromArray([
                'light' => self::BASE_PATH . 'warning-default-light.png',
                'dark' => self::BASE_PATH . 'warning-default-dark.png',
                'alt' => 'Warning notification',
                'title' => 'Warning',
                'hasDarkVariant' => true,
                'hasCustomFallback' => false,
            ]),
            NotificationType::SUCCESS => ImageData::fromArray([
                'light' => self::BASE_PATH . 'success-default-light.png',
                'dark' => self::BASE_PATH . 'success-default-dark.png',
                'alt' => 'Success notification',
                'title' => 'Success',
                'hasDarkVariant' => true,
                'hasCustomFallback' => false,
            ]),
            NotificationType::ERROR => ImageData::fromArray([
                'light' => self::BASE_PATH . 'error-default-light.png',
                'dark' => self::BASE_PATH . 'error-default-dark.png',
                'alt' => 'Error notification',
                'title' => 'Error',
                'hasDarkVariant' => true,
                'hasCustomFallback' => false,
            ]),
            default => ImageData::fromArray([
                'light' => self::BASE_PATH . 'info-default-light.png',
                'dark' => self::BASE_PATH . 'info-default-dark.png',
                'alt' => 'Information notification',
                'title' => 'Information',
                'hasDarkVariant' => true,
                'hasCustomFallback' => false,
            ]),
        };
    }

    /**
     * Safely get image data for a notification type with fallback.
     *
     * @param NotificationType $type The notification type
     * @return ImageData Image data or default if not available
     */
    private static function getSafeImageData(NotificationType $type): ImageData
    {
        try {
            $imageData = $type->getImageData();

            if ($imageData === null) {
                Log::warning("Missing image data for notification type: {$type->name}", [
                    'notification_type' => $type->value,
                    'fallback_used' => true,
                ]);
                return self::getDefaultImage($type);
            }

            // Validate that the image has required fields
            if (empty($imageData->light)) {
                Log::warning("Image data missing light URL for notification type: {$type->name}", [
                    'notification_type' => $type->value,
                    'fallback_used' => true,
                ]);
                return self::getDefaultImage($type);
            }

            return $imageData;
        } catch (\Exception $e) {
            Log::error("Failed to get image data for notification type: {$type->name}", [
                'notification_type' => $type->value,
                'error' => $e->getMessage(),
                'fallback_used' => true,
            ]);
            return self::getDefaultImage($type);
        }
    }

    /**
     * Sends a notification to specific users.
     *
     * @param User|array<User>|Collection $users Recipient(s) of the notification
     * @param string                      $title Notification title
     * @param string                      $message Notification message content
     * @param NotificationType            $type Type of notification (affects styling and icon)
     * @param string|null                 $actionUrl Optional URL for the action button
     * @param string|null                 $actionText Optional text for the action button
     * @param ImageData|null              $image Optional image to display with the notification
     */
    public static function send(
        User|array|Collection $users,
        string $title,
        string $message,
        NotificationType $type = NotificationType::INFO,
        ?string $actionUrl = null,
        ?string $actionText = null,
        ?ImageData $image = null
    ): void {
        $notificationService = app(NotificationService::class);

        $notificationService->sendSystemInformation(
            users: $users,
            title: $title,
            message: $message,
            type: $type,
            actionUrl: $actionUrl,
            actionText: $actionText,
            image: $image
        );
    }

    /**
     * Broadcasts a notification to all users in the system.
     *
     * @param string           $title   Notification title
     * @param string           $message Notification message content
     * @param NotificationType $type    Type of notification (affects styling and icon)
     */
    public static function broadcast(
        string $title,
        string $message,
        NotificationType $type = NotificationType::INFO
    ): void {
        $notificationService = app(NotificationService::class);

        $notificationService->broadcastToAllUsers(
            title: $title,
            message: $message,
            type: $type
        );
    }

    /**
     * Sends a notification to all admin users only.
     *
     * @param string           $title   Notification title
     * @param string           $message Notification message content
     * @param NotificationType $type    Type of notification (affects styling and icon)
     */
    public static function notifyAdmins(
        string $title,
        string $message,
        NotificationType $type = NotificationType::INFO
    ): void {
        $notificationService = app(NotificationService::class);

        $notificationService->notifyAdmins(
            title: $title,
            message: $message,
            type: $type
        );
    }

    /**
     * Sends a security notification with security-specific styling and image.
     *
     * @param User|array<User>|Collection $users      Recipient(s) of the notification
     * @param string                      $title      Notification title
     * @param string                      $message    Notification message content
     * @param string|null                 $actionUrl  Optional URL for the action button
     * @param string|null                 $actionText Optional text for the action button
     */
    public static function security(
        User|array|Collection $users,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $actionText = null
    ): void {
        self::send(
            users: $users,
            title: $title,
            message: $message,
            type: NotificationType::SECURITY,
            actionUrl: $actionUrl,
            actionText: $actionText,
            image: self::getSafeImageData(NotificationType::SECURITY)
        );
    }

    /**
     * Sends a medical notification with medical-specific styling and image.
     *
     * @param User|array<User>|Collection $users      Recipient(s) of the notification
     * @param string                      $title      Notification title
     * @param string                      $message    Notification message content
     * @param string|null                 $actionUrl  Optional URL for the action button
     * @param string|null                 $actionText Optional text for the action button
     */
    public static function medical(
        User|array|Collection $users,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $actionText = null
    ): void {
        self::send(
            users: $users,
            title: $title,
            message: $message,
            type: NotificationType::MEDICAL,
            actionUrl: $actionUrl,
            actionText: $actionText,
            image: self::getSafeImageData(NotificationType::MEDICAL)
        );
    }

    /**
     * Sends an appointment notification with appointment-specific styling and image.
     *
     * @param User|array<User>|Collection $users      Recipient(s) of the notification
     * @param string                      $title      Notification title
     * @param string                      $message    Notification message content
     * @param string|null                 $actionUrl  Optional URL for the action button
     * @param string|null                 $actionText Optional text for the action button
     */
    public static function appointment(
        User|array|Collection $users,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $actionText = null
    ): void {
        self::send(
            users: $users,
            title: $title,
            message: $message,
            type: NotificationType::APPOINTMENT,
            actionUrl: $actionUrl,
            actionText: $actionText,
            image: self::getSafeImageData(NotificationType::APPOINTMENT)
        );
    }

    /**
     * Sends a warning notification with warning-specific styling and image.
     *
     * @param User|array<User>|Collection $users      Recipient(s) of the notification
     * @param string                      $title      Notification title
     * @param string                      $message    Notification message content
     * @param string|null                 $actionUrl  Optional URL for the action button
     * @param string|null                 $actionText Optional text for the action button
     */
    public static function warning(
        User|array|Collection $users,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $actionText = null
    ): void {
        self::send(
            users: $users,
            title: $title,
            message: $message,
            type: NotificationType::WARNING,
            actionUrl: $actionUrl,
            actionText: $actionText,
            image: self::getSafeImageData(NotificationType::WARNING)
        );
    }

    /**
     * Sends a success notification with success-specific styling and image.
     *
     * @param User|array<User>|Collection $users      Recipient(s) of the notification
     * @param string                      $title      Notification title
     * @param string                      $message    Notification message content
     * @param string|null                 $actionUrl  Optional URL for the action button
     * @param string|null                 $actionText Optional text for the action button
     */
    public static function success(
        User|array|Collection $users,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $actionText = null
    ): void {
        self::send(
            users: $users,
            title: $title,
            message: $message,
            type: NotificationType::SUCCESS,
            actionUrl: $actionUrl,
            actionText: $actionText,
            image: self::getSafeImageData(NotificationType::SUCCESS)
        );
    }

    /**
     * Sends an info notification with info-specific styling and image.
     *
     * @param User|array<User>|Collection $users      Recipient(s) of the notification
     * @param string                      $title      Notification title
     * @param string                      $message    Notification message content
     * @param string|null                 $actionUrl  Optional URL for the action button
     * @param string|null                 $actionText Optional text for the action button
     */
    public static function info(
        User|array|Collection $users,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $actionText = null
    ): void {
        self::send(
            users: $users,
            title: $title,
            message: $message,
            type: NotificationType::INFO,
            actionUrl: $actionUrl,
            actionText: $actionText,
            image: self::getSafeImageData(NotificationType::INFO)
        );
    }

    /**
     * Sends an error notification with error-specific styling and image.
     *
     * @param User|array<User>|Collection $users      Recipient(s) of the notification
     * @param string                      $title      Notification title
     * @param string                      $message    Notification message content
     * @param string|null                 $actionUrl  Optional URL for the action button
     * @param string|null                 $actionText Optional text for the action button
     */
    public static function error(
        User|array|Collection $users,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $actionText = null
    ): void {
        self::send(
            users: $users,
            title: $title,
            message: $message,
            type: NotificationType::ERROR,
            actionUrl: $actionUrl,
            actionText: $actionText,
            image: self::getSafeImageData(NotificationType::ERROR)
        );
    }
}

```

## ▶️ DÉMARRAGE

Voici le code à analyser et améliorer :