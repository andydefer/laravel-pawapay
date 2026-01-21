<?php

declare(strict_types=1);

namespace Pawapay\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;

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
            'enums' => 0,
            'interfaces' => 0,
            'skipped' => 0,
        ];

        // Générer les fichiers
        $files = [
            'enums.ts.stub' => 'enums.ts',
            'types.ts.stub' => 'types.ts',
            'index.ts.stub' => 'index.ts',
        ];

        foreach ($files as $stubFile => $targetFile) {
            $stub = $this->stubPath . '/' . $stubFile;
            $target = $this->targetPath . '/' . $targetFile;

            if (!$this->filesystem->exists($stub)) {
                throw new \RuntimeException("Stub file not found: {$stub}");
            }

            // Vérifier si le fichier existe déjà
            if ($this->filesystem->exists($target) && !$force) {
                $result['skipped']++;
                continue;
            }

            // Copier le stub
            $this->filesystem->copy($stub, $target);

            // Compter les fichiers générés
            if (str_contains($targetFile, 'enum')) {
                $result['enums']++;
            } else {
                $result['interfaces']++;
            }
        }

        return $result;
    }

    public function getTargetPath(): string
    {
        return $this->targetPath;
    }
}
