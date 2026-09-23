<?php

declare(strict_types=1);

namespace App\Domains\Presenca\Services;

use App\Domains\Presenca\Contracts\FaceRecognitionService;
use Illuminate\Support\Facades\Storage;

final class MockFaceRecognitionService implements FaceRecognitionService
{
    public function enroll(string $photoPath): string
    {
        if (is_file($photoPath)) {
            return hash_file('sha256', $photoPath);
        }

        $armazenada = storage_path('app/public/'.$photoPath);

        if (is_file($armazenada)) {
            return hash_file('sha256', $armazenada);
        }

        try {
            $viaDisco = Storage::disk('public')->path($photoPath);
            if (is_file($viaDisco)) {
                return hash_file('sha256', $viaDisco);
            }
        } catch (\Throwable) {
        }

        return hash('sha256', $photoPath);
    }

    public function compare(string $storedReference, string $candidatePhotoPath): bool
    {
        return hash_equals($storedReference, $this->enroll($candidatePhotoPath));
    }
}
