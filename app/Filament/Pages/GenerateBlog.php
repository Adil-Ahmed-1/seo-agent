<?php

namespace App\Filament\Pages;

use App\Jobs\GenerateBlogJob;
use App\Models\Website;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class GenerateBlog extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon  = 'heroicon-o-sparkles';
    protected static ?string $navigationGroup = 'Content';
    protected static ?int    $navigationSort  = 1;
    protected static ?string $title           = 'Generate AI Blog';

    protected static string $view = 'filament.pages.generate-blog';

    public ?array $data = [];

    public bool $isGenerating = false;

    public function mount(): void
    {
        $this->form->fill([
            'tone'       => 'professional',
            'word_count' => 1500,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Blog Configuration')
                    ->description('AI blog generate karne ke liye details bharo')
                    ->schema([
                        Forms\Components\Select::make('website_id')
                            ->label('Website')
                            ->options(Website::pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->helperText('Kis website ke liye blog generate karna hai?'),

                        Forms\Components\TextInput::make('keyword')
                            ->label('Target Keyword')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('professional web design services')
                            ->helperText('Main keyword jo rank karna hai'),

                        Forms\Components\Select::make('tone')
                            ->options([
                                'professional'  => 'Professional',
                                'casual'        => 'Casual',
                                'friendly'      => 'Friendly',
                                'authoritative' => 'Authoritative',
                            ])
                            ->default('professional')
                            ->required(),

                        Forms\Components\TextInput::make('word_count')
                            ->numeric()
                            ->default(1500)
                            ->minValue(800)
                            ->maxValue(3000)
                            ->suffix('words')
                            ->required(),

                        Forms\Components\Toggle::make('auto_publish')
                            ->label('Auto-publish after generation')
                            ->default(false)
                            ->helperText('Agar on karo to blog approve hoke direct publish ke liye ready hoga'),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function generate(): void
    {
        $data = $this->form->getState();

        $website = Website::findOrFail($data['website_id']);

        try {
            $this->isGenerating = true;

            Notification::make()
                ->title('Blog generation started')
                ->body("AI is writing blog for '{$data['keyword']}'. This may take 30-60 seconds.")
                ->info()
                ->duration(5000)
                ->send();

            // Sync job (immediate)
            if (config('queue.default') === 'sync') {
                GenerateBlogJob::dispatchSync($website, $data['keyword'], [
                    'tone'         => $data['tone'],
                    'word_count'   => $data['word_count'],
                    'auto_publish' => $data['auto_publish'],
                ]);

                Notification::make()
                    ->title('✅ Blog generated successfully!')
                    ->body("'{$data['keyword']}' ka blog ready hai. Blog Posts page pe dekho.")
                    ->success()
                    ->duration(10000)
                    ->send();
            } else {
                // Background queue
                GenerateBlogJob::dispatch($website, $data['keyword'], [
                    'tone'         => $data['tone'],
                    'word_count'   => $data['word_count'],
                    'auto_publish' => $data['auto_publish'],
                ]);

                Notification::make()
                    ->title('📝 Blog generation queued')
                    ->body("Background me generate ho raha hai. 1-2 minutes me notification aayega.")
                    ->success()
                    ->send();
            }

            // Reset form
            $this->form->fill([
                'website_id' => $data['website_id'],
                'tone'       => 'professional',
                'word_count' => 1500,
            ]);

        } catch (\Exception $e) {
            Log::error('Generate blog failed', ['error' => $e->getMessage()]);

            Notification::make()
                ->title('❌ Blog generation failed')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
        } finally {
            $this->isGenerating = false;
        }
    }
}