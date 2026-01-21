<?php

declare(strict_types=1);

namespace Pawapay\Commands;

use Illuminate\Console\Command;
use Pawapay\Services\TypesGeneratorService;

class GenerateTypesCommand extends Command
{
    protected $signature = 'pawapay:generate-types
                            {--force : Force regeneration of all TypeScript files}
                            {--clean : Clean all generated TypeScript files}';

    protected $description = 'Generate TypeScript definitions for Pawapay API';

    public function handle(TypesGeneratorService $typesGenerator): int
    {
        if ($this->option('clean')) {
            $this->info('🧹 Cleaning TypeScript definitions...');

            if ($typesGenerator->clean()) {
                $this->info('✅ TypeScript definitions cleaned successfully.');
            } else {
                $this->info('ℹ️  No TypeScript definitions to clean.');
            }

            return self::SUCCESS;
        }

        $force = (bool) $this->option('force');

        $this->info('💻 Generating TypeScript definitions...');

        try {
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
                $this->warn('⚠️  No TypeScript files were generated.');
            }

            $this->newLine();
            $this->info('📁 Files location: <comment>' . $typesGenerator->getTargetPath() . '</comment>');
        } catch (\Exception $e) {
            $this->error('❌ Failed to generate TypeScript types: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
