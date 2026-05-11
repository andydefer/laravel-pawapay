<?php

declare(strict_types=1);

namespace Pawapay\Enums;

enum SupportedCountry: string
{
    case BEN = 'BEN'; // Benin (+229)
    case BFA = 'BFA'; // Burkina Faso (+226)
    case CMR = 'CMR'; // Cameroon (+237)
    case CIV = 'CIV'; // Côte d'Ivoire (+225)
    case COD = 'COD'; // DR Congo (+243)
    case ETH = 'ETH'; // Ethiopia (+251)
    case GAB = 'GAB'; // Gabon (+241)
    case GHA = 'GHA'; // Ghana (+233)
    case KEN = 'KEN'; // Kenya (+254)
    case LSO = 'LSO'; // Lesotho (+266)
    case MWI = 'MWI'; // Malawi (+265)
    case MOZ = 'MOZ'; // Mozambique (+258)
    case NGA = 'NGA'; // Nigeria (+234)
    case COG = 'COG'; // Republic of Congo (+242)
    case RWA = 'RWA'; // Rwanda (+250)
    case SEN = 'SEN'; // Senegal (+221)
    case SLE = 'SLE'; // Sierra Leone (+232)
    case TZA = 'TZA'; // Tanzania (+255)
    case UGA = 'UGA'; // Uganda (+256)
    case ZMB = 'ZMB'; // Zambia (+260)

    /**
     * Retourne l'indicatif téléphonique du pays
     */
    public function getPhoneCode(): string
    {
        return match ($this) {
            self::BEN => '+229',
            self::BFA => '+226',
            self::CMR => '+237',
            self::CIV => '+225',
            self::COD => '+243',
            self::ETH => '+251',
            self::GAB => '+241',
            self::GHA => '+233',
            self::KEN => '+254',
            self::LSO => '+266',
            self::MWI => '+265',
            self::MOZ => '+258',
            self::NGA => '+234',
            self::COG => '+242',
            self::RWA => '+250',
            self::SEN => '+221',
            self::SLE => '+232',
            self::TZA => '+255',
            self::UGA => '+256',
            self::ZMB => '+260',
        };
    }

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
            fn(SupportedProvider $provider): bool => $this->isProviderForCountry($provider)
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
