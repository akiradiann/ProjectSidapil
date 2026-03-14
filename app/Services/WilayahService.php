<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class WilayahService
{
    /**
     * Base URL for API wilayah Indonesia
     */
    private const BASE_URL = 'https://www.emsifa.com/api-wilayah-indonesia/api';

    /**
     * Get all districts (kecamatan) for a regency
     * 
     * @param string $regencyCode Default: 3310 (Klaten)
     * @return array
     */
    public static function getDistricts(string $regencyCode = '3310'): array
    {
        return Cache::remember("districts_{$regencyCode}", 3600, function () use ($regencyCode) {
            try {
                $response = Http::timeout(10)->get(self::BASE_URL . "/districts/{$regencyCode}.json");
                
                if ($response->successful()) {
                    $data = $response->json();
                    return is_array($data) ? $data : [];
                }
            } catch (\Exception $e) {
                \Log::error('Failed to fetch districts: ' . $e->getMessage());
            }
            
            return [];
        });
    }

    /**
     * Get all villages (desa) for a district
     * 
     * @param string $districtCode
     * @return array
     */
    public static function getVillages(string $districtCode): array
    {
        return Cache::remember("villages_{$districtCode}", 3600, function () use ($districtCode) {
            try {
                $response = Http::timeout(10)->get(self::BASE_URL . "/villages/{$districtCode}.json");
                
                if ($response->successful()) {
                    $data = $response->json();
                    return is_array($data) ? $data : [];
                }
            } catch (\Exception $e) {
                \Log::error('Failed to fetch villages: ' . $e->getMessage());
            }
            
            return [];
        });
    }

    /**
     * Get district by code
     * 
     * @param string $districtCode
     * @return array|null
     */
    public static function getDistrict(string $districtCode, string $regencyCode = '3310'): ?array
    {
        $districts = self::getDistricts($regencyCode);
        
        foreach ($districts as $district) {
            if (isset($district['id']) && (string)$district['id'] === (string)$districtCode) {
                return $district;
            }
        }
        
        return null;
    }

    /**
     * Get village by code
     * 
     * @param string $districtCode
     * @param string $villageCode
     * @return array|null
     */
    public static function getVillage(string $districtCode, string $villageCode): ?array
    {
        $villages = self::getVillages($districtCode);
        
        foreach ($villages as $village) {
            if (isset($village['id']) && (string)$village['id'] === (string)$villageCode) {
                return $village;
            }
        }
        
        return null;
    }

    /**
     * Format districts for select options
     * 
     * @param string $regencyCode
     * @return array
     */
    public static function getDistrictOptions(string $regencyCode = '3310'): array
    {
        $districts = self::getDistricts($regencyCode);
        $options = [];
        
        foreach ($districts as $district) {
            if (isset($district['id']) && isset($district['name'])) {
                $options[$district['id']] = $district['name'];
            }
        }
        
        return $options;
    }

    /**
     * Format villages for select options
     * 
     * @param string $districtCode
     * @return array
     */
    public static function getVillageOptions(string $districtCode): array
    {
        if (empty($districtCode)) {
            return [];
        }
        
        $villages = self::getVillages($districtCode);
        $options = [];
        
        foreach ($villages as $village) {
            if (isset($village['id']) && isset($village['name'])) {
                $options[$village['id']] = $village['name'];
            }
        }
        
        return $options;
    }
}

