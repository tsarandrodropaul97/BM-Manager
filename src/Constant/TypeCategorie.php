<?php

namespace App\Constant;

class TypeCategorie
{
    public const RESIDENTIEL = 'residentiel';
    public const COMMERCIAL = 'commercial';
    public const INDUSTRIEL = 'industriel';
    public const MIXTE = 'mixte';
    public const TERRAIN = 'terrain';
    public const PARKING = 'parking';

    /**
     * Retourne tous les types de catégories disponibles
     */
    public static function getTypes(): array
    {
        return [
            self::RESIDENTIEL => 'Résidentiel',
            self::COMMERCIAL => 'Commercial',
            self::INDUSTRIEL => 'Industriel',
            self::MIXTE => 'Mixte',
            self::TERRAIN => 'Terrain',
            self::PARKING => 'Parking/Garage',
        ];
    }

    /**
     * Retourne le libellé d'un type de catégorie
     */
    public static function getLibelle(string $type): string
    {
        $types = self::getTypes();
        return $types[$type] ?? 'Type inconnu';
    }

    /**
     * Vérifie si un type de catégorie est valide
     */
    public static function isValid(string $type): bool
    {
        return array_key_exists($type, self::getTypes());
    }

    /**
     * Retourne la liste des valeurs (clés) uniquement
     */
    public static function getValues(): array
    {
        return array_keys(self::getTypes());
    }
}
