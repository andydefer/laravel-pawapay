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
* Supprimer **tous les commentaires parasites**, temporaires ou personnels :
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

## ▶️ DÉMARRAGE

Voici le code à analyser et améliorer :
