<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\WithoutMiddleware;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use WithFaker;
    use WithoutMiddleware;
}
