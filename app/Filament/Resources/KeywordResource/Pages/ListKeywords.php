<?php

namespace App\Filament\Resources\KeywordResource\Pages;

use App\Filament\Resources\KeywordResource;
use App\Models\Website;
use App\Models\Keyword;
use App\Services\Content\KeywordGenerator;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListKeywords extends ListRecords
{
    protected static string $resource = KeywordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('autoGenerate')
                ->label('Auto Generate Keywords')
                ->icon('heroicon-o-sparkles')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\Select::make('website_id')
                        ->label('Select Website')
                        ->options(
                            Website::query()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                        )
                        ->searchable()
                        ->required(),

                    \Filament\Forms\Components\TextInput::make('count')
                        ->label('Number of Keywords')
                        ->numeric()
                        ->default(20)
                        ->minValue(5)
                        ->maxValue(100)
                        ->required(),
                ])
                ->action(function (array $data) {

                    $website = Website::find($data['website_id']);

                    if (!$website) {
                        Notification::make()
                            ->title('Website not found')
                            ->danger()
                            ->send();

                        return;
                    }

                    $generator = app(KeywordGenerator::class);

                    $keywords = $generator->generate([
                        'name'        => $website->name,
                        'niche'       => $website->niche ?? '',
                        'description' => $website->business_description ?? '',
                        'audience'    => $website->target_audience ?? '',
                        'language'    => $website->language ?? 'English',
                    ], (int) $data['count']);

                    $added = 0;
                    $skipped = 0;

                    foreach ($keywords as $item) {

                        if (empty($item['keyword'])) {
                            continue;
                        }

                        $keywordText = trim($item['keyword']);

                        $exists = Keyword::where('website_id', $website->id)
                            ->where('keyword', $keywordText)
                            ->exists();

                        if ($exists) {
                            $skipped++;
                            continue;
                        }

                        Keyword::create([
                            'website_id'     => $website->id,
                            'keyword'        => $keywordText,
                            'search_volume'  => $item['search_volume_estimate'] ?? null,
                            'difficulty'     => $item['difficulty_estimate'] ?? null,
                            'cpc'            => $item['cpc'] ?? null,
                            'intent'         => $item['intent'] ?? 'informational',
                            'current_rank'   => null,
                            'target_rank'    => 10,
                            'status'         => 'active',
                        ]);

                        $added++;
                    }

                    Notification::make()
                        ->title('Keywords generated successfully')
                        ->body("Added: {$added} | Skipped duplicates: {$skipped}")
                        ->success()
                        ->send();
                }),

            Actions\CreateAction::make(),
        ];
    }
}