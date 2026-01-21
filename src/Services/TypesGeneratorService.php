<?php

declare(strict_types=1);

namespace Pawapay\Services;

use Illuminate\Filesystem\Filesystem;

class TypesGeneratorService
{
    private Filesystem $filesystem;
    private string $stubPath;
    private string $targetPath;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
        $this->stubPath = __DIR__ . '/../stubs/typescript';
        $this->targetPath = resource_path('js/pawapay');
    }

    public function generate(bool $force = false): array
    {
        // Créer le répertoire cible s'il n'existe pas
        if (!$this->filesystem->exists($this->targetPath)) {
            $this->filesystem->makeDirectory($this->targetPath, 0755, true);
        }

        $result = [
            'generated' => [],
            'skipped' => [],
            'total' => 0,
        ];

        // Fichiers à générer depuis les stubs internes
        $files = [
            'enums.ts.stub' => 'enums.ts',
            'types.ts.stub' => 'types.ts',
            'index.ts.stub' => 'index.ts',
        ];

        foreach ($files as $stubFile => $targetFile) {
            $stubPath = $this->stubPath . '/' . $stubFile;
            $targetPath = $this->targetPath . '/' . $targetFile;

            // Vérifier si le stub interne existe
            if (!$this->filesystem->exists($stubPath)) {
                throw new \RuntimeException("Stub file not found: {$stubFile}");
            }

            // Vérifier si le fichier cible existe déjà
            if ($this->filesystem->exists($targetPath) && !$force) {
                $result['skipped'][] = $targetFile;
                continue;
            }

            // Lire le contenu du stub et le traiter
            $content = $this->filesystem->get($stubPath);

            // Optionnel: Effectuer des transformations si nécessaire
            // $content = $this->processContent($content);

            // Écrire le fichier TypeScript final
            $this->filesystem->put($targetPath, $content);

            $result['generated'][] = $targetFile;
            $result['total']++;
        }

        return $result;
    }

    public function clean(): bool
    {
        if (!$this->filesystem->exists($this->targetPath)) {
            return true;
        }

        // Supprimer tous les fichiers générés
        $filesToDelete = ['enums.ts', 'types.ts', 'index.ts'];
        $deleted = 0;

        foreach ($filesToDelete as $file) {
            $filePath = $this->targetPath . '/' . $file;
            if ($this->filesystem->exists($filePath)) {
                $this->filesystem->delete($filePath);
                $deleted++;
            }
        }

        // Si le dossier est vide, le supprimer
        $filesInDir = $this->filesystem->files($this->targetPath);
        if (empty($filesInDir)) {
            $this->filesystem->deleteDirectory($this->targetPath);
        }

        return $deleted > 0;
    }

    public function getTargetPath(): string
    {
        return $this->targetPath;
    }

    public function isGenerated(): bool
    {
        $requiredFiles = ['enums.ts', 'types.ts', 'index.ts'];

        foreach ($requiredFiles as $file) {
            if (!$this->filesystem->exists($this->targetPath . '/' . $file)) {
                return false;
            }
        }

        return true;
    }
}
