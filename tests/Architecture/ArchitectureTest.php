<?php

declare(strict_types=1);

namespace Tests\Architecture;

use Tests\TestCase;

class ArchitectureTest extends TestCase
{
    public function test_domain_entities_should_not_depend_on_infrastructure(): void
    {
        $domainPath = base_path('app/Domain');

        if (! is_dir($domainPath)) {
            $this->markTestSkipped('Domain directory does not exist');
        }

        $files = $this->getPhpFiles($domainPath);
        $violations = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);

            // Check for Infrastructure namespace usage in Domain layer
            if (preg_match('/use\s+App\\\\Infrastructure\\\\/', $content)) {
                $violations[] = $file;
            }

            // Check for Eloquent Model usage in Domain entities
            if (preg_match('/use\s+Illuminate\\\\Database\\\\Eloquent\\\\Model/', $content)) {
                $violations[] = $file;
            }
        }

        $this->assertEmpty(
            $violations,
            'Domain layer should not depend on Infrastructure: '.implode(', ', $violations)
        );
    }

    public function test_controllers_should_be_in_http_namespace(): void
    {
        $controllersPath = base_path('app/Http/Controllers');

        if (! is_dir($controllersPath)) {
            $this->markTestSkipped('Controllers directory does not exist');
        }

        $files = $this->getPhpFiles($controllersPath);

        foreach ($files as $file) {
            $content = file_get_contents($file);

            $this->assertMatchesRegularExpression(
                '/namespace\s+App\\\\Http\\\\Controllers/',
                $content,
                "Controller {$file} should be in App\\Http\\Controllers namespace"
            );
        }

        $this->assertTrue(true);
    }

    public function test_value_objects_should_be_immutable(): void
    {
        $domainPath = base_path('app/Domain');

        if (! is_dir($domainPath)) {
            $this->markTestSkipped('Domain directory does not exist');
        }

        $valueObjectDirs = glob($domainPath.'/*/ValueObjects', GLOB_ONLYDIR);
        $violations = [];

        foreach ($valueObjectDirs as $dir) {
            $files = $this->getPhpFiles($dir);

            foreach ($files as $file) {
                $content = file_get_contents($file);

                // Check for public setter methods (excluding constructor)
                if (preg_match('/public\s+function\s+set[A-Z]/', $content)) {
                    $violations[] = $file;
                }
            }
        }

        $this->assertEmpty(
            $violations,
            'Value Objects should not have public setter methods: '.implode(', ', $violations)
        );
    }

    /**
     * @return array<string>
     */
    private function getPhpFiles(string $directory): array
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }
}
