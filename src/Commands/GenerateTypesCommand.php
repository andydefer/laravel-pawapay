<?php

declare(strict_types=1);

namespace Pawapay\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Pawapay\Services\TypesGeneratorService;

final class GenerateTypesCommand extends Command
{
    protected $signature = 'pawapay:generate-types
                            {--force : Overwrite existing files without confirmation}';

    protected $description = 'Generate TypeScript types and enums for Pawapay API';

    public function handle(TypesGeneratorService $generatorService): int
    {
        try {
            $force = (bool) $this->option('force');

            $this->info('Generating Pawapay TypeScript definitions...');

            $result = $generatorService->generate($force);

            $this->newLine();

            if ($result['enums']) {
                $this->info('✅ Enums generated: ' . $result['enums']);
            }

            if ($result['interfaces']) {
                $this->info('✅ Interfaces generated: ' . $result['interfaces']);
            }

            if ($result['skipped']) {
                $this->warn('⚠️  Skipped (already exist): ' . $result['skipped']);
            }

            $this->newLine();
            $this->info('🎉 TypeScript definitions generated successfully!');
            $this->info('📁 Location: ' . resource_path('js/pawapay'));

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('❌ Error generating types: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
