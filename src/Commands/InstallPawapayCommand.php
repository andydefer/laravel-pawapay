<?php

declare(strict_types=1);

namespace Pawapay\Commands;

use Exception;
use Illuminate\Console\Command;
use Pawapay\Services\TypesGeneratorService;

/**
 * Command to install the Pawapay package and publish all necessary resources.
 *
 * This command handles the installation process including:
 * - Publishing configuration files
 * - Publishing controllers and routes
 * - Generating TypeScript type definitions
 */
class InstallPawapayCommand extends Command
{
    /** @var string */
    protected $signature = 'pawapay:install
                            {--force : Force publish without confirmation}';

    /** @var string */
    protected $description = 'Install Pawapay package and publish all resources';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $this->info('🚀 Installing Laravel Pawapay Package...');

        $this->publishResources($force);
        $this->generateTypescriptTypes($force);

        $this->displaySuccessMessage();

        return self::SUCCESS;
    }

    /**
     * Publish all package resources.
     *
     * @param bool $force
     * @return void
     */
    private function publishResources(bool $force): void
    {
        $resources = [
            'config' => [
                'name' => 'Configuration',
                'tag' => 'pawapay-config',
            ],
            'controller' => [
                'name' => 'API Controller',
                'tag' => 'pawapay-controller',
            ],
            'routes' => [
                'name' => 'Custom Routes',
                'tag' => 'pawapay-routes',
                'optional' => true,
            ],
        ];

        foreach ($resources as $resource) {
            $this->publishResource($resource, $force);
        }
    }

    /**
     * Publish a single resource.
     *
     * @param array $resource
     * @param bool $force
     * @return void
     */
    private function publishResource(array $resource, bool $force): void
    {
        $isOptional = isset($resource['optional']) && $resource['optional'];

        if ($isOptional) {
            $this->info("📝 {$resource['name']} (optional)...");

            if (! $this->confirm("Do you want to publish custom {$resource['name']}?", false)) {
                $this->info("   Using default package {$resource['name']}");
                return;
            }
        } else {
            $this->info("📦 Publishing {$resource['name']}...");
        }

        $this->call('vendor:publish', [
            '--provider' => 'Pawapay\\PawapayServiceProvider',
            '--tag' => $resource['tag'],
            '--force' => $force,
        ]);
    }

    /**
     * Generate TypeScript type definitions.
     *
     * @param bool $force
     * @return void
     */
    private function generateTypescriptTypes(bool $force): void
    {
        $this->info('💻 Generating TypeScript definitions...');

        try {
            /** @var TypesGeneratorService $typesGenerator */
            $typesGenerator = app(TypesGeneratorService::class);
            $result = $typesGenerator->generate($force);

            $this->displayTypesGenerationResult($result);
        } catch (Exception $exception) {
            $this->displayTypesGenerationError($exception);
        }
    }

    /**
     * Display TypeScript generation results.
     *
     * @param array $result
     * @return void
     */
    private function displayTypesGenerationResult(array $result): void
    {
        if (! empty($result['generated'])) {
            $this->info('✅ TypeScript types generated successfully:');
            foreach ($result['generated'] as $file) {
                $this->line("   - <comment>{$file}</comment>");
            }
        }

        if (! empty($result['skipped'])) {
            $this->warn('⚠️  Some files were skipped (use --force to overwrite):');
            foreach ($result['skipped'] as $file) {
                $this->line("   - {$file}");
            }
        }

        if ($result['total'] === 0 && empty($result['generated']) && empty($result['skipped'])) {
            $this->warn('⚠️  No TypeScript files were generated or found.');
        }
    }

    /**
     * Display TypeScript generation error.
     *
     * @param Exception $exception
     * @return void
     */
    private function displayTypesGenerationError(Exception $exception): void
    {
        $this->error('❌ Failed to generate TypeScript types: ' . $exception->getMessage());
        $this->line('Please run <comment>php artisan pawapay:generate-types</comment> manually.');
    }

    /**
     * Display installation success message with next steps.
     *
     * @return void
     */
    private function displaySuccessMessage(): void
    {
        $this->newLine();
        $this->info('🎉 Pawapay package installed successfully!');
        $this->newLine();

        $this->displayNextSteps();
        $this->displayApiRoutes();
        $this->displayArtisanCommands();
        $this->displayGeneratedFiles();
    }

    /**
     * Display next steps for configuration.
     *
     * @return void
     */
    private function displayNextSteps(): void
    {
        $this->info('📋 Next steps:');
        $this->line('   1. Add your API token to <comment>.env</comment> file:');
        $this->line('      <comment>PAWAPAY_API_TOKEN=your_token_here</comment>');
        $this->line('   2. Set environment in <comment>.env</comment>:');
        $this->line('      <comment>PAWAPAY_ENVIRONMENT=sandbox</comment> (or production)');
        $this->newLine();
    }

    /**
     * Display available API routes.
     *
     * @return void
     */
    private function displayApiRoutes(): void
    {
        $this->info('🌍 API Routes are now available at:');
        $this->line('   <comment>POST   /api/pawapay/predict-provider</comment>');
        $this->line('   <comment>POST   /api/pawapay/payment-page</comment>');
        $this->line('   <comment>POST   /api/pawapay/deposits</comment>');
        $this->line('   <comment>GET    /api/pawapay/deposits/{depositId}</comment>');
        $this->newLine();
    }

    /**
     * Display available artisan commands.
     *
     * @return void
     */
    private function displayArtisanCommands(): void
    {
        $this->info('🔧 Available artisan commands:');
        $this->line('   <comment>php artisan pawapay:generate-types</comment> - Generate/update TypeScript types');
        $this->line('   <comment>php artisan pawapay:install</comment> - Install/reinstall package');
        $this->newLine();
    }

    /**
     * Display generated files.
     *
     * @return void
     */
    private function displayGeneratedFiles(): void
    {
        $this->info('📁 Generated files:');
        $this->line('   - <comment>config/pawapay.php</comment>');
        $this->line('   - <comment>app/Http/Controllers/Api/PawapayController.php</comment>');
        $this->line('   - <comment>resources/js/pawapay/</comment> (TypeScript types)');

        if (file_exists(base_path('routes/pawapay.php'))) {
            $this->line('   - <comment>routes/pawapay.php</comment> (custom routes)');
        }
    }
}
