<?php

if (! function_exists('catalog_image_by_name')) {
    function catalog_image_by_name(string $name, bool $allowNameInFile = false): ?string
    {
        $normalizedName = preg_replace('/[^a-z0-9]/', '', mb_strtolower($name));
        $imageFiles = glob(FCPATH . 'PCTURES/*.{jpg,jpeg,png,webp}', GLOB_BRACE) ?: [];

        foreach ($imageFiles as $imageFile) {
            $fileName = preg_replace('/[^a-z0-9]/', '', mb_strtolower(pathinfo($imageFile, PATHINFO_FILENAME)));
            if ($fileName === $normalizedName || ($allowNameInFile && str_contains($fileName, $normalizedName))) {
                return 'PCTURES/' . rawurlencode(basename($imageFile));
            }
        }

        return null;
    }
}

if (! function_exists('service_image_url')) {
    function service_image_url(array $service): string
    {
        return catalog_image_by_name((string) ($service['service_name'] ?? ''), true) ?? 'PCTURES/solar-panels-home.jpg';
    }
}

if (! function_exists('product_image_url')) {
    function product_image_url(array $product): string
    {
        $imageUrl = trim((string) ($product['image_url'] ?? ''));
        if ($imageUrl !== '') {
            return $imageUrl;
        }

        $name = preg_replace('/[^a-z0-9]/', '', mb_strtolower((string) ($product['product_name'] ?? '')));
        $namedImage = catalog_image_by_name((string) ($product['product_name'] ?? ''));
        if ($namedImage !== null) {
            return $namedImage;
        }

        if (str_contains($name, 'batter') || str_contains($name, 'inverter') || str_contains($name, 'storage') || str_contains($name, 'wire')) {
            $image = 'solar-storage-system.jpg';
        } elseif (str_contains($name, 'rack') || str_contains($name, 'mount') || str_contains($name, 'install')) {
            $image = 'solar-panels-home.jpg';
        } else {
            $image = 'solar-farm.jpg';
        }

        return 'PCTURES/' . $image;
    }
}
