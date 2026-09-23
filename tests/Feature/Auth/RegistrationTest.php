<?php

declare(strict_types=1);

test('auto cadastro publico esta desativado', function () {
    $this->get('/register')->assertNotFound();
});
