<?php

declare(strict_types=1);

namespace Pawapay\Enums;

enum SupportedCountry: string
{
    case BEN = 'BEN'; // Benin
    case BFA = 'BFA'; // Burkina Faso
    case CMR = 'CMR'; // Cameroon
    case CIV = 'CIV'; // Côte d'Ivoire
    case COD = 'COD'; // DR Congo
    case ETH = 'ETH'; // Ethiopia
    case GAB = 'GAB'; // Gabon
    case GHA = 'GHA'; // Ghana
    case KEN = 'KEN'; // Kenya
    case LSO = 'LSO'; // Lesotho
    case MWI = 'MWI'; // Malawi
    case MOZ = 'MOZ'; // Mozambique
    case NGA = 'NGA'; // Nigeria
    case COG = 'COG'; // Republic of Congo
    case RWA = 'RWA'; // Rwanda
    case SEN = 'SEN'; // Senegal
    case SLE = 'SLE'; // Sierra Leone
    case TZA = 'TZA'; // Tanzania
    case UGA = 'UGA'; // Uganda
    case ZMB = 'ZMB'; // Zambia

    /**
     * Retourne le pays correspondant à un provider
     */
    public static function fromProvider(SupportedProvider $provider): self
    {
        $countryCode = substr($provider->name, strrpos($provider->name, '_') + 1);
        return self::from($countryCode);
    }

    /**
     * Retourne tous les providers supportés dans ce pays
     *
     * @return array<SupportedProvider>
     */
    public function getProviders(): array
    {
        return array_filter(
            SupportedProvider::cases(),
            fn(SupportedProvider $provider) => $this->isProviderForCountry($provider)
        );
    }

    /**
     * Vérifie si un provider est pour ce pays
     */
    private function isProviderForCountry(SupportedProvider $provider): bool
    {
        $countryCode = substr($provider->name, strrpos($provider->name, '_') + 1);
        return $this->value === $countryCode;
    }
}
