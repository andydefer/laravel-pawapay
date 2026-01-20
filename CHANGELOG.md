# Changelog

Toutes les modifications notables de ce projet seront documentées dans ce fichier.
Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet suit les principes de [Semantic Versioning](https://semver.org/spec/v2.0.0.html).


## [0.8.0] - 2024-01-04

### Added
- **Enhanced validation rule descriptions**: Added `getDescription()` method to `RuleInterface` to provide detailed descriptions of validation rules' purpose and validations.
- **Violation tracking enhancements**: Added `hasViolationFor()` method to `ValidationContextInterface` to check for violations on specific fields.
- **Rule-aware violation reporting**: Added `setViolationFromRule()` method to automatically populate violation data with rule information.
- **Detailed exception reporting**: Added `toDetailedArray()`, `getViolationsWithDescriptions()`, and `getFormattedMessage()` methods to `ValidationFailedException` for richer error reporting.
- **Violation Data Transfer Objects**: Introduced `ViolationData` class to encapsulate violation metadata including field, message, rule name, and rule description.

### Changed
- **BREAKING**: Validation violation structure changed from associative arrays (`array<string, string>`) to typed DTO objects (`array<int, ViolationData>`). Affects all methods returning or accepting violations.
- **BREAKING**: `setViolation()` method signature updated from `setViolation(string $field, string $message): void` to `setViolation(string $field, string $message, ?string $rule = null): void`. All existing calls must be updated.
- **BREAKING**: Updated `ValidationResult` class to use `ViolationData` objects internally, with method names changed for clarity (`isValid()` instead of `success`, `failed()` instead of `invalid`).
- **Refactored service layer**: Updated `AvailabilityService` and `AbstractService` to create `ViolationData` objects instead of simple arrays when throwing validation exceptions.
- **Enhanced PHPDoc**: Improved type hints and documentation across validation interfaces and classes, including clarified `array<int, RuleInterface>` type for additional rules.
- **Updated test suite**: All unit and integration tests updated to work with the new `ViolationData` structure, using `hasViolationFor()` method and object-based violation access.
- **Example documentation**: Updated `README.md` with consistent date examples for impediment creation.

### Fixed
- **Rule exception handling**: Updated `Validator::handleRuleException()` to use `setViolationFromRule()` for better error context.
- **Type safety**: Improved type declarations for `$violations` properties in `ValidationContext` and `ValidationFailedException` classes.