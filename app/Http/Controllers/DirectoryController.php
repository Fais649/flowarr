<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DirectoryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $rawPath = $request->query('path', '/');
        $depth = (int) $request->query('depth', 5);

        $this->validatePath($rawPath);

        $path = $this->normalizePath($rawPath);

        $this->ensurePathExists($path);

        $directories = $this->listDirectories($path, $depth);

        return response()->json([
            'path' => $path,
            'directories' => $directories,
        ]);
    }

    private function validatePath(string $path): void
    {
        if (Str::contains($path, '..') || str_starts_with($path, '~') || str_contains($path, "\0")) {
            abort(422, 'Invalid path.');
        }
    }

    private function ensurePathExists(string $path): void
    {
        if (! is_dir($path)) {
            throw new NotFoundHttpException("Directory not found: {$path}");
        }
    }

    private function listDirectories(string $path, int $depth = 5): array
    {
        try {
            $iterator = new \DirectoryIterator($path);
        } catch (\UnexpectedValueException) {
            return [];
        }

        $directories = [];

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            if ($fileInfo->isLink()) {
                continue;
            }

            if (! $fileInfo->isDir()) {
                continue;
            }

            $dirName = $fileInfo->getFilename();

            if (str_starts_with($dirName, '.')) {
                continue;
            }

            $childPath = $this->normalizePath($fileInfo->getPathname());

            $entry = [
                'name' => $dirName,
                'path' => $childPath,
            ];

            if ($depth > 0) {
                $entry['children'] = $this->listDirectories($childPath, $depth - 1);
            }

            $directories[] = $entry;
        }

        usort($directories, fn (array $a, array $b) => strcasecmp($a['name'], $b['name']));

        return array_values($directories);
    }

    private function normalizePath(string $path): string
    {
        $path = rtrim($path, '/');

        return $path === '' ? '/' : '/'.ltrim($path, '/');
    }
}
