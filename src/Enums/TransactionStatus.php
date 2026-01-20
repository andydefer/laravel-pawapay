<?php

declare(strict_types=1);

namespace Pawapay\Enums;

enum TransactionStatus: string
{
    // Statuts d'initiation (réponse API)
    case ACCEPTED = 'ACCEPTED';               // Transaction acceptée pour traitement
    case REJECTED = 'REJECTED';               // Transaction rejetée
    case DUPLICATE_IGNORED = 'DUPLICATE_IGNORED'; // Requête dupliquée ignorée

        // Statuts finaux (callback/check status)
    case COMPLETED = 'COMPLETED';             // Transaction réussie (final)
    case FAILED = 'FAILED';                   // Transaction échouée (final)

        // Statuts intermédiaires
    case SUBMITTED = 'SUBMITTED';             // Soumis au MMO
    case ENQUEUED = 'ENQUEUED';               // En file d'attente
    case PROCESSING = 'PROCESSING';           // En cours de traitement
    case IN_RECONCILIATION = 'IN_RECONCILIATION'; // En cours de réconciliation

        // Statut de recherche
    case FOUND = 'FOUND';                     // Trouvé (check status response)
    case NOT_FOUND = 'NOT_FOUND';             // Non trouvé
}
