<?php

namespace Loom\Providers;

use Alexusmai\LaravelFileManager\FileManager as PackageFileManager;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Loom\Builder\SecureFileWriter;
use Loom\Console\CachePluginsCommand;
use Loom\Console\ClearPluginsCommand;
use Loom\Console\ConvertThemeBladesCommand;
use Loom\Console\ExportThemeContentCommand;
use Loom\Console\InstallGitHooksCommand;
use Loom\Console\MigrateThemeStructure;
use Loom\Features\FeatureManager;
use Loom\Support\FileManager\FileManager;
use Loom\System\PluginAutoloader;
use Loom\System\PluginManager;

class LoomServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        PluginAutoloader::register();

        $this->app->bind(PackageFileManager::class, FileManager::class);

        $this->app->singleton(SecureFileWriter::class, fn () => SecureFileWriter::make());

        $this->app->singleton(PluginManager::class);
        $this->app->alias(PluginManager::class, 'loom.plugins');

        $this->app->singleton(FeatureManager::class);
        $this->app->alias(FeatureManager::class, 'loom.features');

        $this->app->make(FeatureManager::class)->registerAll();
        $this->app->make(PluginManager::class)->registerAll();
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                CachePluginsCommand::class,
                ClearPluginsCommand::class,
                ExportThemeContentCommand::class,
                ConvertThemeBladesCommand::class,
                MigrateThemeStructure::class,
                InstallGitHooksCommand::class,
            ]);
        }

        $this->app->make(FeatureManager::class)->bootAll();
        $this->app->make(PluginManager::class)->bootAll();

        View::composer('admin.layout', function ($view) {
            $view->with('adminNavigation', app(FeatureManager::class)->getMergedNavigation());
        });
    }
}
