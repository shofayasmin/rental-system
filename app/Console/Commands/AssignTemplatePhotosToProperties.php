<?php

namespace App\Console\Commands;

use App\Models\Property;
use App\Models\PropertyPhoto;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AssignTemplatePhotosToProperties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'properties:assign-template-photos
        {--mode=replace : replace, append, or only-empty}
        {--source=property-master : Source directory inside storage/app/public}
        {--required=front,living_room,bedroom,bathroom,kitchen : Required categories}
        {--dry-run : Show plan without writing data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign template photos to properties by cycling numbered filenames per category.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $mode = strtolower((string) $this->option('mode'));
        if (!in_array($mode, ['replace', 'append', 'only-empty'], true)) {
            $this->error("Invalid --mode={$mode}. Use replace, append, or only-empty.");
            return self::INVALID;
        }

        $sourceDir = trim((string) $this->option('source'), '/');
        if ($sourceDir === '') {
            $this->error('Option --source cannot be empty.');
            return self::INVALID;
        }

        $requiredCategories = collect(explode(',', (string) $this->option('required')))
            ->map(fn ($item) => strtolower(trim($item)))
            ->filter()
            ->values()
            ->all();

        if (empty($requiredCategories)) {
            $this->error('At least one required category is needed in --required.');
            return self::INVALID;
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($sourceDir)) {
            $this->error("Source directory storage/app/public/{$sourceDir} does not exist.");
            return self::FAILURE;
        }

        $templatesBySlot = $this->buildTemplateMap($sourceDir);
        if (empty($templatesBySlot)) {
            $this->error('No template files matched pattern: <category>-<n> or <category>_<nn> (jpg|jpeg|png|webp).');
            return self::FAILURE;
        }

        $slotNumbers = array_keys($templatesBySlot);
        sort($slotNumbers);

        foreach ($slotNumbers as $slot) {
            $categoriesForSlot = array_keys($templatesBySlot[$slot]);
            $missing = array_values(array_diff($requiredCategories, $categoriesForSlot));
            if (!empty($missing)) {
                $this->error("Slot {$slot} is missing required categories: " . implode(', ', $missing));
                return self::FAILURE;
            }
        }

        $propertyQuery = Property::query()
            ->select('id')
            ->withCount('photos')
            ->orderBy('id');

        if ($mode === 'only-empty') {
            $propertyQuery->doesntHave('photos');
        }

        $properties = $propertyQuery->get();
        if ($properties->isEmpty()) {
            $this->warn('No properties matched for this mode.');
            return self::SUCCESS;
        }

        $isDryRun = (bool) $this->option('dry-run');
        $assignedCount = 0;
        $insertedPhotoCount = 0;

        $this->info("Mode: {$mode}");
        $this->info("Source: storage/app/public/{$sourceDir}");
        $this->info('Slots found: ' . implode(', ', $slotNumbers));
        $this->info('Required categories: ' . implode(', ', $requiredCategories));

        foreach ($properties as $index => $property) {
            $slot = $slotNumbers[$index % count($slotNumbers)];
            $slotTemplates = $templatesBySlot[$slot];
            uksort($slotTemplates, static function (string $a, string $b): int {
                $aIsFront = $a === 'front' ? 0 : 1;
                $bIsFront = $b === 'front' ? 0 : 1;

                if ($aIsFront !== $bIsFront) {
                    return $aIsFront <=> $bIsFront;
                }

                return strcmp($a, $b);
            });

            if ($isDryRun) {
                $this->line("Property #{$property->id} -> slot {$slot} (" . implode(', ', array_keys($slotTemplates)) . ')');
                $assignedCount++;
                $insertedPhotoCount += count($slotTemplates);
                continue;
            }

            DB::transaction(function () use ($mode, $property, $slotTemplates, &$assignedCount, &$insertedPhotoCount) {
                $existingPaths = [];
                if ($mode === 'append') {
                    $existingPaths = PropertyPhoto::query()
                        ->where('property_id', $property->id)
                        ->pluck('path')
                        ->all();
                    $existingPaths = array_fill_keys($existingPaths, true);
                }

                if ($mode === 'replace') {
                    PropertyPhoto::query()
                        ->where('property_id', $property->id)
                        ->delete();
                }

                foreach ($slotTemplates as $path) {
                    if ($mode === 'append' && isset($existingPaths[$path])) {
                        continue;
                    }

                    PropertyPhoto::create([
                        'property_id' => $property->id,
                        'path' => $path,
                    ]);

                    $insertedPhotoCount++;
                }

                $assignedCount++;
            });
        }

        if ($isDryRun) {
            $this->info("Dry run complete. {$assignedCount} properties planned, {$insertedPhotoCount} photos planned.");
            return self::SUCCESS;
        }

        $this->info("Done. {$assignedCount} properties processed, {$insertedPhotoCount} photos inserted.");
        return self::SUCCESS;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function buildTemplateMap(string $sourceDir): array
    {
        $files = Storage::disk('public')->allFiles($sourceDir);
        sort($files);

        $templatesBySlot = [];

        foreach ($files as $file) {
            $filename = basename($file);
            // Support both: front-1.jpg and front_01.jpg
            if (!preg_match('/^([a-z0-9_]+)[_-]([0-9]{1,2})\.(jpg|jpeg|png|webp)$/i', $filename, $matches)) {
                continue;
            }

            $category = strtolower($matches[1]);
            $slot = (int) $matches[2];

            if (!isset($templatesBySlot[$slot])) {
                $templatesBySlot[$slot] = [];
            }

            // Keep first match per category-slot to avoid duplicates from nested folders.
            if (!isset($templatesBySlot[$slot][$category])) {
                $templatesBySlot[$slot][$category] = $file;
            }
        }

        ksort($templatesBySlot);

        return $templatesBySlot;
    }
}
