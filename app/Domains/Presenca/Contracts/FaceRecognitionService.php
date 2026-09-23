<?php

declare(strict_types=1);

namespace App\Domains\Presenca\Contracts;

interface FaceRecognitionService
{
    public function enroll(string $photoPath): string;

    public function compare(string $storedReference, string $candidatePhotoPath): bool;
}
