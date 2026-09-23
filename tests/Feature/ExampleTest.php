<?php

it('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200)
        ->assertSee('Cadastro do clube')
        ->assertSee('Painel das quadras')
        ->assertSee('Secretaria ou facial');
});
