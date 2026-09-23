<?php

declare(strict_types=1);

use App\Domains\Presenca\Contracts\FaceRecognitionService;

test('comparacao facial mock reconhece a mesma foto', function () {
    $service = app(FaceRecognitionService::class);
    $referencia = $service->enroll('fotos/socio-1.jpg');

    expect($service->compare($referencia, 'fotos/socio-1.jpg'))->toBeTrue()
        ->and($service->compare($referencia, 'fotos/outra.jpg'))->toBeFalse();
});
