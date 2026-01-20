<?php

declare(strict_types=1);

namespace Pawapay\Enums;

enum Currency: string
{
    // West African CFA Franc (BCEAO)
    case XOF = 'XOF'; // Benin, Burkina Faso, Côte d'Ivoire, Senegal

        // Central African CFA Franc (BEAC)
    case XAF = 'XAF'; // Cameroon, Republic of Congo, Gabon

        // Congolese Franc
    case CDF = 'CDF'; // DR Congo

        // US Dollar
    case USD = 'USD'; // DR Congo

        // Ethiopian Birr
    case ETB = 'ETB'; // Ethiopia

        // Ghanaian Cedi
    case GHS = 'GHS'; // Ghana

        // Kenyan Shilling
    case KES = 'KES'; // Kenya

        // Lesotho Loti
    case LSL = 'LSL'; // Lesotho

        // Malawian Kwacha
    case MWK = 'MWK'; // Malawi

        // Mozambican Metical
    case MZN = 'MZN'; // Mozambique

        // Nigerian Naira
    case NGN = 'NGN'; // Nigeria

        // Rwandan Franc
    case RWF = 'RWF'; // Rwanda

        // Sierra Leonean Leone
    case SLE = 'SLE'; // Sierra Leone

        // Tanzanian Shilling
    case TZS = 'TZS'; // Tanzania

        // Ugandan Shilling
    case UGX = 'UGX'; // Uganda

        // Zambian Kwacha
    case ZMW = 'ZMW'; // Zambia
}
