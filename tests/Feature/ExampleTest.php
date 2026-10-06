<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_el_login_responde(): void
    {
        $this->get('/login')->assertOk();
    }
}
