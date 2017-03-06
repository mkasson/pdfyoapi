<?php
namespace Pdfyo\PdfyoAPI;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
class PdfyoAPIServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;
    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot()
    {
        // use this if your package has views
        $this->loadViewsFrom(realpath(__DIR__.'/resources/views'), 'pdfyoapi');
        
        // use this if your package has lang files
        $this->loadTranslationsFrom(__DIR__.'/resources/lang', 'pdfyoapi');
        
        // use this if your package has routes
        $this->setupRoutes($this->app->router);
        
        // use this if your package needs a config file
        // $this->publishes([
        //         __DIR__.'/config/config.php' => config_path('pdfyoapi.php'),
        // ]);
        
        // use the vendor configuration file as fallback
        // $this->mergeConfigFrom(
        //     __DIR__.'/config/config.php', 'pdfyoapi'
        // );
    }
    /**
     * Define the routes for the application.
     *
     * @param  \Illuminate\Routing\Router  $router
     * @return void
     */
    public function setupRoutes(Router $router)
    {
        $router->group(['namespace' => 'Pdfyo\PdfyoAPI\Http\Controllers'], function($router)
        {
            require __DIR__.'/Http/routes.php';
        });
    }
    /**
     * Register any package services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerSkeleton();
        
        // use this if your package has a config file
        // config([
        //         'config/pdfyoapi.php',
        // ]);
    }
    private function registerSkeleton()
    {
        $this->app->bind('pdfyoapi',function($app){
            return new Skeleton($app);
        });
    }
}
