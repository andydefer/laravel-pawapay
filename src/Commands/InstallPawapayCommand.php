<?php

declare(strict_types=1);

namespace Pawapay\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Pawapay\Services\TypesGeneratorService;

class InstallPawapayCommand extends Command
{
    protected $signature = 'pawapay:install
                            {--force : Force publish without confirmation}';

    protected $description = 'Install Pawapay package and publish all resources';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $this->info('🚀 Installing Laravel Pawapay Package...');

        $this->publishResources($force);
        $this->generateTypescriptTypes($force);

        $this->displaySuccessMessage();

        return self::SUCCESS;
    }

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
            // Note: On ne publie plus les types TypeScript ici
            // Ils sont générés directement par generateTypescriptTypes()
        ];

        foreach ($resources as $resource) {
            if (isset($resource['optional']) && $resource['optional']) {
                $this->info("📝 {$resource['name']} (optional)...");

                if ($this->confirm("Do you want to publish custom {$resource['name']}?", false)) {
                    $this->call('vendor:publish', [
                        '--provider' => 'Pawapay\\PawapayServiceProvider',
                        '--tag' => $resource['tag'],
                        '--force' => $force,
                    ]);
                } else {
                    $this->info("   Using default package {$resource['name']}");
                }
            } else {
                $this->info("📦 Publishing {$resource['name']}...");
                $this->call('vendor:publish', [
                    '--provider' => 'Pawapay\\PawapayServiceProvider',
                    '--tag' => $resource['tag'],
                    '--force' => $force,
                ]);
            }
        }
    }

    private function generateTypescriptTypes(bool $force): void
    {
        $this->info('💻 Generating TypeScript definitions...');

        try {
            /** @var TypesGeneratorService $typesGenerator */
            $typesGenerator = app(TypesGeneratorService::class);

            $result = $typesGenerator->generate($force);

            if (!empty($result['generated'])) {
                $this->info('✅ TypeScript types generated successfully:');
                foreach ($result['generated'] as $file) {
                    $this->line("   - <comment>{$file}</comment>");
                }
            }

            if (!empty($result['skipped'])) {
                $this->warn('⚠️  Some files were skipped (use --force to overwrite):');
                foreach ($result['skipped'] as $file) {
                    $this->line("   - {$file}");
                }
            }

            if ($result['total'] === 0 && empty($result['generated']) && empty($result['skipped'])) {
                $this->warn('⚠️  No TypeScript files were generated or found.');
            }
        } catch (\Exception $e) {
            $this->error('❌ Failed to generate TypeScript types: ' . $e->getMessage());
            $this->line('Please run <comment>php artisan pawapay:generate-types</comment> manually.');
        }
    }

    private function displaySuccessMessage(): void
    {
        $this->newLine();
        $this->info('🎉 Pawapay package installed successfully!');
        $this->newLine();

        $this->info('📋 Next steps:');
        $this->line('   1. Add your API token to <comment>.env</comment> file:');
        $this->line('      <comment>PAWAPAY_API_TOKEN=your_token_here</comment>');
        $this->line('   2. Set environment in <comment>.env</comment>:');
        $this->line('      <comment>PAWAPAY_ENVIRONMENT=sandbox</comment> (or production)');
        $this->newLine();

        $this->info('🌍 API Routes are now available at:');
        $this->line('   <comment>POST   /api/pawapay/predict-provider</comment>');
        $this->line('   <comment>POST   /api/pawapay/payment-page</comment>');
        $this->line('   <comment>POST   /api/pawapay/deposits</comment>');
        $this->line('   <comment>GET    /api/pawapay/deposits/{depositId}</comment>');
        $this->newLine();

        $this->info('🔧 Available artisan commands:');
        $this->line('   <comment>php artisan pawapay:generate-types</comment> - Generate/update TypeScript types');
        $this->line('   <comment>php artisan pawapay:install</comment> - Install/reinstall package');
        $this->newLine();

        $this->info('📁 Generated files:');
        $this->line('   - <comment>config/pawapay.php</comment>');
        $this->line('   - <comment>app/Http/Controllers/Api/PawapayController.php</comment>');
        $this->line('   - <comment>resources/js/pawapay/</comment> (TypeScript types)');

        if (file_exists(base_path('routes/pawapay.php'))) {
            $this->line('   - <comment>routes/pawapay.php</comment> (custom routes)');
        }
    }
}
