<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        //

        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        $this->mapApiRoutes();

        $this->mapWebRoutes();

        //
    }

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->namespace($this->namespace)
            ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     *
     * @return void
     */
    protected function mapApiRoutes()
    {
        // Route::prefix('api')
        //     ->middleware('api')
        //     ->namespace($this->namespace)
        //     ->group(base_path('routes/api.php'));
        Route::prefix('api')
            ->middleware('api')
            ->namespace($this->namespace)
            ->group(function () {
                require base_path('routes/teste.php');

                require base_path('routes/ml/app.php');
                require base_path('routes/ml/auth.php');
                require base_path('routes/ml/mail.php');

                require base_path('routes/ml/web/categoriadocumentos.php');
                require base_path('routes/ml/web/documentos.php');
                require base_path('routes/ml/web/escalas.php');
                require base_path('routes/ml/web/fornecedores.php');
                require base_path('routes/ml/web/prestadores.php');

                require base_path('routes/api.php');
            });
    }
}
