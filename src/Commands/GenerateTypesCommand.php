<?php

declare(strict_types=1);

namespace Pawapay\Commands;

use Exception;
use Illuminate\Console\Command;
use Pawapay\Services\TypesGeneratorService;

/**
 * Console command to generate TypeScript definitions for Pawapay API.
 *
 * This command facilitates the generation of TypeScript type definitions
 * from PHP classes to enhance frontend development with type safety.
 */
class GenerateTypesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pawapay:generate-types
                            {--force : Force regeneration of all TypeScript files}
                            {--clean : Clean all generated TypeScript files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate TypeScript definitions for Pawapay API';

    /**
     * Execute the console command.
     *
     * @param TypesGeneratorService $typesGenerator Service responsible for generating TypeScript definitions
     *
     * @return int Returns Command::SUCCESS on success, Command::FAILURE on error
     *
     * @throws Exception If TypeScript generation fails
     */
    public function handle(TypesGeneratorService $typesGenerator): int
    {
        if ($this->option('clean')) {
            return $this->handleCleanOperation($typesGenerator);
        }

        return $this->handleGenerateOperation($typesGenerator);
    }

    /**
     * Handle the clean operation to remove all generated TypeScript files.
     *
     * @param TypesGeneratorService $typesGenerator
     *
     * @return int
     */
    private function handleCleanOperation(TypesGeneratorService $typesGenerator): int
    {
        $this->info('🧹 Cleaning TypeScript definitions...');

        if ($typesGenerator->clean()) {
            $this->info('✅ TypeScript definitions cleaned successfully.');
        } else {
            $this->info('ℹ️  No TypeScript definitions to clean.');
        }

        return self::SUCCESS;
    }

    /**
     * Handle the generation of TypeScript definitions.
     *
     * @param TypesGeneratorService $typesGenerator
     *
     * @return int
     */
    private function handleGenerateOperation(TypesGeneratorService $typesGenerator): int
    {
        $force = (bool) $this->option('force');

        $this->info('💻 Generating TypeScript definitions...');

        try {
            $generationResult = $typesGenerator->generate($force);

            $this->displayGenerationResults($generationResult);
            $this->displayTargetPath($typesGenerator);

            return self::SUCCESS;
        } catch (Exception $exception) {
            $this->displayGenerationError($exception);
            return self::FAILURE;
        }
    }

    /**
     * Display the results of the TypeScript generation process.
     *
     * @param array $generationResult
     *
     * @return void
     */
    private function displayGenerationResults(array $generationResult): void
    {
        if (!empty($generationResult['generated'])) {
            $this->info('✅ TypeScript types generated successfully:');

            foreach ($generationResult['generated'] as $file) {
                $this->line("   - <comment>{$file}</comment>");
            }
        }

        if (!empty($generationResult['skipped'])) {
            $this->warn('⚠️  Some files were skipped (use --force to overwrite):');

            foreach ($generationResult['skipped'] as $file) {
                $this->line("   - {$file}");
            }
        }

        if ($generationResult['total'] === 0 && empty($generationResult['generated']) && empty($generationResult['skipped'])) {
            $this->warn('⚠️  No TypeScript files were generated.');
        }
    }

    /**
     * Display the target path where TypeScript files are stored.
     *
     * @param TypesGeneratorService $typesGenerator
     *
     * @return void
     */
    private function displayTargetPath(TypesGeneratorService $typesGenerator): void
    {
        $this->newLine();
        $this->info('📁 Files location: <comment>' . $typesGenerator->getTargetPath() . '</comment>');
    }

    /**
     * Display an error message when TypeScript generation fails.
     *
     * @param Exception $exception
     *
     * @return void
     */
    private function displayGenerationError(Exception $exception): void
    {
        $this->error('❌ Failed to generate TypeScript types: ' . $exception->getMessage());
    }
}
