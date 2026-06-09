<?php

namespace App\Enums;

enum SocialCommentClassification: string
{
    case Normal = 'normal';
    case SalesLead = 'sales_lead';
    case CommercialQuestion = 'commercial_question';
    case Complaint = 'complaint';
    case NegativeOpinion = 'negative_opinion';
    case Spam = 'spam';
    case Offensive = 'offensive';
    case Positive = 'positive';
    case MedicalSensitive = 'medical_sensitive';
    case LegalSensitive = 'legal_sensitive';
    case NeedsHumanReview = 'needs_human_review';

    public function label(): string
    {
        return match ($this) {
            self::Normal => 'Normal',
            self::SalesLead => 'Lead comercial',
            self::CommercialQuestion => 'Pregunta comercial',
            self::Complaint => 'Queja',
            self::NegativeOpinion => 'Opinion negativa',
            self::Spam => 'Spam',
            self::Offensive => 'Ofensivo',
            self::Positive => 'Positivo',
            self::MedicalSensitive => 'Medico sensible',
            self::LegalSensitive => 'Legal sensible',
            self::NeedsHumanReview => 'Requiere revision humana',
        };
    }
}
