<?php

declare(strict_types=1);

namespace App\Application\Workshop;

use App\Application\Shared\Validator;
use App\Domain\Workshop\WorkshopOptions;

/** Validates the fields an instructor writes about a workshop and maps them to columns. */
final readonly class WorkshopInput
{
    public function __construct(private WorkshopOptions $options)
    {
    }

    /**
     * @param array<string, mixed> $input
     * @return array<string, mixed> Column values.
     */
    public function columns(array $input): array
    {
        $data = Validator::validate($input, [
            'name' => 'required|string|max:200',
            'level' => 'required|in:' . implode(',', $this->options->levels),
            'public' => 'required|in:' . implode(',', $this->options->audiences),
            'duration' => 'required|int:15,480',
            'observations' => 'string|max:500',
        ]);

        return [
            'name' => $data['name'],
            'level' => $data['level'],
            'public' => $data['public'],
            'duration' => $data['duration'],
            'observations' => $data['observations'],
        ];
    }
}
