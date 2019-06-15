<?php namespace Ladybird\import;

use Illuminate\Support\ServiceProvider;

class ImportServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
    }

    public function register()
    {
        
    }
}