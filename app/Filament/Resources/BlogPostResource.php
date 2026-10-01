<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BlogPostResource\Pages;
use App\Models\BlogPost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BlogPostResource extends Resource
{
    protected static ?string $model = BlogPost::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Content';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Content')
                ->schema([
                    Forms\Components\Select::make('website_id')
                        ->relationship('website', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->reactive(),
                    
                    Forms\Components\TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                    
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    
                    Forms\Components\TextInput::make('focus_keyword')
                        ->required()
                        ->maxLength(255)
                        ->label('Focus Keyword'),
                    
                    Forms\Components\TagsInput::make('secondary_keywords')
                        ->label('Secondary Keywords')
                        ->columnSpanFull(),
                    
                    Forms\Components\Textarea::make('meta_description')
                        ->required()
                        ->rows(2)
                        ->maxLength(160)
                        ->helperText('Max 155 characters for SEO')
                        ->columnSpanFull(),
                    
                    Forms\Components\RichEditor::make('content_html')
                        ->required()
                        ->columnSpanFull()
                        ->toolbarButtons([
                            'bold', 'italic', 'underline', 'strike',
                            'h2', 'h3', 'h4',
                            'link', 'bulletList', 'orderedList',
                            'blockquote', 'codeBlock',
                            'undo', 'redo',
                        ]),
                ])->columns(2),

            Forms\Components\Section::make('Publishing')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options([
                            'draft'          => '📝 Draft',
                            'pending_review' => '👀 Pending Review',
                            'approved'       => '✅ Approved',
                            'scheduled'      => '📅 Scheduled',
                            'publishing'     => '⏳ Publishing',
                            'published'      => '🎉 Published',
                            'failed'         => '❌ Failed',
                        ])
                        ->default('draft')
                        ->required(),
                    
                    Forms\Components\DateTimePicker::make('scheduled_at'),
                    
                    Forms\Components\TextInput::make('remote_url')
                        ->label('Published URL')
                        ->disabled()
                        ->dehydrated(false)
                        ->visible(fn ($record) => $record?->remote_url !== null),
                ])->columns(2),

            Forms\Components\Section::make('SEO Analysis')
                ->collapsed()
                ->schema([
                    Forms\Components\Placeholder::make('word_count')
                        ->content(fn ($record) => $record?->word_count . ' words' ?? 'N/A'),
                    
                    Forms\Components\Placeholder::make('seo_score')
                        ->content(fn ($record) => $record?->seo_score . '/100' ?? 'N/A'),
                    
                    Forms\Components\Placeholder::make('ai_model')
                        ->content(fn ($record) => $record?->ai_model ?? 'N/A'),
                    
                    Forms\Components\Placeholder::make('cost')
                        ->content(fn ($record) => '$' . number_format($record?->cost ?? 0, 4)),
                ])->columns(4)
                ->visible(fn ($record) => $record !== null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(50),
                
                Tables\Columns\TextColumn::make('website.name')
                    ->label('Website')
                    ->badge()
                    ->color('info')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('focus_keyword')
                    ->label('Keyword')
                    ->badge()
                    ->color('warning')
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('word_count')
                    ->label('Words')
                    ->numeric()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('seo_score')
                    ->label('SEO')
                    ->numeric()
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state >= 80 => 'success',
                        $state >= 60 => 'warning',
                        default      => 'danger',
                    })
                    ->suffix('/100'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray'    => 'draft',
                        'warning' => 'pending_review',
                        'info'    => 'approved',
                        'info'    => 'scheduled',
                        'warning' => 'publishing',
                        'success' => 'published',
                        'danger'  => 'failed',
                    ]),
                
                Tables\Columns\TextColumn::make('published_at')
                    ->since()
                    ->label('Published')
                    ->placeholder('—')
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('website')
                    ->relationship('website', 'name'),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft'          => 'Draft',
                        'pending_review' => 'Pending Review',
                        'approved'       => 'Approved',
                        'published'      => 'Published',
                        'failed'         => 'Failed',
                    ]),
            ])
           ->actions([
    // Test Connection Action
    Tables\Actions\Action::make('test_connection')
        ->label('Test')
        ->icon('heroicon-o-signal')
        ->color('info')
        ->action(function (BlogPost $record) {
            $publisher = \App\Services\Publisher\PublisherFactory::make($record->website);
            $ok = $publisher->testConnection();
            
            \Filament\Notifications\Notification::make()
                ->title($ok ? '✅ Connection Successful' : '❌ Connection Failed')
                ->body("Website: {$record->website->name} ({$record->website->platform})")
                ->color($ok ? 'success' : 'danger')
                ->send();
        }),
    
    // Publish Now Action
    Tables\Actions\Action::make('publish')
        ->label('Publish')
        ->icon('heroicon-o-rocket-launch')
        ->color('success')
        ->requiresConfirmation()
        ->modalHeading('Publish Blog?')
        ->modalDescription(fn (BlogPost $record) => "Ye blog '{$record->website->name}' website pe publish hoga. Confirm karo?")
        ->modalSubmitActionLabel('Yes, Publish')
        ->visible(fn (BlogPost $record) => ! in_array($record->status, ['published', 'publishing']))
        ->action(function (BlogPost $record) {
            $post = $record;
            
            \Filament\Notifications\Notification::make()
                ->title('🚀 Publishing started')
                ->body("Blog '{$post->title}' publish ho raha hai...")
                ->info()
                ->duration(5000)
                ->send();

            // Dispatch job (sync mode me immediate)
            try {
                \App\Jobs\PublishBlogJob::dispatchSync($post);
                
                // Refresh post
                $post->refresh();
                
                if ($post->status === 'published') {
                    \Filament\Notifications\Notification::make()
                        ->title('✅ Published Successfully!')
                        ->body("URL: {$post->remote_url}")
                        ->success()
                        ->duration(10000)
                        ->send();
                } else {
                    \Filament\Notifications\Notification::make()
                        ->title('❌ Publish Failed')
                        ->body($post->error_message ?? 'Unknown error')
                        ->danger()
                        ->persistent()
                        ->send();
                }
            } catch (\Exception $e) {
                \Filament\Notifications\Notification::make()
                    ->title('❌ Publish Failed')
                    ->body($e->getMessage())
                    ->danger()
                    ->persistent()
                    ->send();
            }
        }),
    
    // View Published URL
    Tables\Actions\Action::make('view')
        ->label('Visit')
        ->url(fn (BlogPost $record) => $record->remote_url, true)
        ->icon('heroicon-o-arrow-top-right-on-square')
        ->color('primary')
        ->visible(fn (BlogPost $record) => $record->remote_url !== null),
    
    Tables\Actions\EditAction::make(),
    Tables\Actions\DeleteAction::make(),
])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBlogPosts::route('/'),
            'create' => Pages\CreateBlogPost::route('/create'),
            'edit'   => Pages\EditBlogPost::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending_review')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}