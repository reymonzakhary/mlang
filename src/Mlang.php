<?php

namespace Upon\Mlang;

use Upon\Mlang\Contracts\MlangContractInterface;

class Mlang implements MlangContractInterface
{
    /**
     * Laravel application.
     *
     * @var \Illuminate\Foundation\Application
     */
    public $app;

    /**
     * Create a new confide instance.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

}
