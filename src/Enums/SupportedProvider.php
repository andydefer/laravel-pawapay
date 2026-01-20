<?php

declare(strict_types=1);

namespace Pawapay\Enums;

enum SupportedProvider: string
{
    // Benin
    case MTN_MOMO_BEN = 'MTN_MOMO_BEN';
    case MOOV_BEN = 'MOOV_BEN';
        // Burkina Faso
    case MOOV_BFA = 'MOOV_BFA';
    case ORANGE_BFA = 'ORANGE_BFA';
        // Cameroon
    case MTN_MOMO_CMR = 'MTN_MOMO_CMR';
    case ORANGE_CMR = 'ORANGE_CMR';
        // Côte d'Ivoire
    case MTN_MOMO_CIV = 'MTN_MOMO_CIV';
    case ORANGE_CIV = 'ORANGE_CIV';
    case WAVE_CIV = 'WAVE_CIV';
        // DR Congo
    case VODACOM_MPESA_COD = 'VODACOM_MPESA_COD';
    case AIRTEL_COD = 'AIRTEL_COD';
    case ORANGE_COD = 'ORANGE_COD';
        // Ethiopia
    case MPESA_ETH = 'MPESA_ETH';
        // Gabon
    case AIRTEL_GAB = 'AIRTEL_GAB';
        // Ghana
    case MTN_MOMO_GHA = 'MTN_MOMO_GHA';
    case AIRTELTIGO_GHA = 'AIRTELTIGO_GHA';
    case VODAFONE_GHA = 'VODAFONE_GHA';
        // Kenya
    case MPESA_KEN = 'MPESA_KEN';
        // Lesotho
    case MPESA_LSO = 'MPESA_LSO';
        // Malawi
    case AIRTEL_MWI = 'AIRTEL_MWI';
    case TNM_MWI = 'TNM_MWI';
        // Mozambique
    case MOVITEL_MOZ = 'MOVITEL_MOZ';
    case VODACOM_MOZ = 'VODACOM_MOZ';
        // Nigeria
    case AIRTEL_NGA = 'AIRTEL_NGA';
    case MTN_MOMO_NGA = 'MTN_MOMO_NGA';
        // Republic of Congo
    case AIRTEL_COG = 'AIRTEL_COG';
    case MTN_MOMO_COG = 'MTN_MOMO_COG';
        // Rwanda
    case AIRTEL_RWA = 'AIRTEL_RWA';
    case MTN_MOMO_RWA = 'MTN_MOMO_RWA';
        // Senegal
    case FREE_SEN = 'FREE_SEN';
    case ORANGE_SEN = 'ORANGE_SEN';
    case WAVE_SEN = 'WAVE_SEN';
        // Sierra Leone
    case ORANGE_SLE = 'ORANGE_SLE';
        // Tanzania
    case AIRTEL_TZA = 'AIRTEL_TZA';
    case VODACOM_TZA = 'VODACOM_TZA';
    case TIGO_TZA = 'TIGO_TZA';
    case HALOTEL_TZA = 'HALOTEL_TZA';
        // Uganda
    case AIRTEL_OAPI_UGA = 'AIRTEL_OAPI_UGA';
    case MTN_MOMO_UGA = 'MTN_MOMO_UGA';
        // Zambia
    case AIRTEL_OAPI_ZMB = 'AIRTEL_OAPI_ZMB';
    case MTN_MOMO_ZMB = 'MTN_MOMO_ZMB';
    case ZAMTEL_ZMB = 'ZAMTEL_ZMB';

    /**
     * Retourne le pays correspondant à ce provider
     */
    public function getCountry(): SupportedCountry
    {
        $countryCode = substr($this->name, strrpos($this->name, '_') + 1);
        return SupportedCountry::from($countryCode);
    }
}
