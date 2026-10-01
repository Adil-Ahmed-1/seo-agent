<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WebsiteResource\Pages;
use App\Models\Website;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WebsiteResource extends Resource
{
    protected static ?string $model = Website::class;
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'SEO Management';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Basic Information')
                ->description('Website ki basic details')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('My Business Website'),
                    
                    Forms\Components\TextInput::make('url')
                        ->url()
                        ->required()
                        ->prefix('https://')
                        ->placeholder('example.com')
                        ->unique(ignoreRecord: true)
                        ->helperText('Full URL with https://'),
                    
                    Forms\Components\TextInput::make('niche')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Digital Marketing Agency')
                        ->helperText('Website ka main topic/industry'),
                    
                    Forms\Components\Select::make('language')
                        ->options([
                            'en' => 'English',
                            'ur' => 'Urdu',
                            'hi' => 'Hindi',
                            'ar' => 'Arabic',
                        ])
                        ->default('en')
                        ->required(),
                ])->columns(2),

            Forms\Components\Section::make('Business Details')
                ->description('AI ko context dene ke liye')
                ->schema([
                    Forms\Components\Textarea::make('business_description')
                        ->rows(3)
                        ->placeholder('Hum ek digital marketing agency hain jo small businesses ki help karte hain...')
                        ->helperText('2-3 sentences me business describe karo. AI blog writer isko use karega.'),
                    
                    Forms\Components\TextInput::make('target_audience')
                        ->placeholder('Small business owners in USA')
                        ->helperText('Kon tumhara target customer hai?'),
                ]),

            Forms\Components\Section::make('Platform & Publishing')
                ->description('Blog kahan publish karna hai?')
                ->schema([
                    Forms\Components\Select::make('platform')
                        ->options([
                            'wordpress' => '🔵 WordPress',
                            'laravel'   => '🔴 Laravel App',
                            'static'    => '🟡 Static HTML/CSS/JS',
                        ])
                        ->reactive()
                        ->required()
                        ->default('wordpress'),

                    // WordPress Fields
                    Forms\Components\Group::make([
                        Forms\Components\TextInput::make('credentials.wp_username')
                            ->label('WordPress Username')
                            ->required()
                            ->placeholder('admin'),
                        Forms\Components\TextInput::make('credentials.wp_app_password')
                            ->label('Application Password')
                            ->password()
                            ->revealable()
                            ->required()
                            ->helperText('WP Admin → Users → Profile → Application Passwords se generate karo'),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('platform') === 'wordpress')
                    ->columns(2),

                    // Laravel Fields
                    Forms\Components\Group::make([
                        Forms\Components\TextInput::make('credentials.api_endpoint')
                            ->label('API Endpoint')
                            ->url()
                            ->required()
                            ->placeholder('https://client-site.com')
                            ->helperText('Client ki Laravel site ka base URL'),
                        Forms\Components\TextInput::make('credentials.api_token')
                            ->label('Bearer Token')
                            ->password()
                            ->revealable()
                            ->required(),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('platform') === 'laravel')
                    ->columns(2),

                    // Static Fields
                    Forms\Components\Group::make([
                        Forms\Components\TextInput::make('credentials.ftp_host')
                            ->label('FTP Host')
                            ->required()
                            ->placeholder('ftp.example.com'),
                        Forms\Components\TextInput::make('credentials.ftp_username')
                            ->label('FTP Username')
                            ->required(),
                        Forms\Components\TextInput::make('credentials.ftp_password')
                            ->label('FTP Password')
                            ->password()
                            ->revealable()
                            ->required(),
                        Forms\Components\TextInput::make('credentials.ftp_path')
                            ->label('Upload Path')
                            ->default('/public_html/blog/')
                            ->required()
                            ->helperText('Jahan blog files upload karni hain'),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('platform') === 'static')
                    ->columns(2),
                ]),

            Forms\Components\Section::make('Content Settings')
                ->schema([
                    Forms\Components\Select::make('settings.tone')
                        ->options([
                            'professional'  => 'Professional',
                            'casual'        => 'Casual',
                            'friendly'      => 'Friendly',
                            'authoritative' => 'Authoritative',
                        ])
                        ->default('professional'),
                    
                    Forms\Components\TextInput::make('settings.word_count')
                        ->numeric()
                        ->default(1800)
                        ->suffix('words'),
                    
                    Forms\Components\Toggle::make('settings.auto_publish')
                        ->label('Auto-publish without review')
                        ->default(false)
                        ->helperText('AI generate karke direct publish kar de'),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('url')
                    ->url(fn (Website $record) => $record->url, true)
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('primary')
                    ->limit(40),
                
                Tables\Columns\BadgeColumn::make('platform')
                    ->colors([
                        'info'    => 'wordpress',
                        'danger'  => 'laravel',
                        'warning' => 'static',
                    ])
                    ->icons([
                        'heroicon-o-check-badge' => 'wordpress',
                    ]),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'pending',
                        'danger'  => 'error',
                    ]),
                
                Tables\Columns\TextColumn::make('blog_posts_count')
                    ->counts('blogPosts')
                    ->label('Blogs')
                    ->badge()
                    ->color('success'),
                
                Tables\Columns\TextColumn::make('keywords_count')
                    ->counts('keywords')
                    ->label('Keywords')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('last_crawled_at')
                    ->since()
                    ->label('Last Crawled')
                    ->placeholder('Never'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('platform')
                    ->options([
                        'wordpress' => 'WordPress',
                        'laravel'   => 'Laravel',
                        'static'    => 'Static',
                    ]),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active'  => 'Active',
                        'pending' => 'Pending',
                        'error'   => 'Error',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('test_connection')
                    ->label('Test')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(function (Website $record) {
                        Notification::make()
                            ->title('Testing connection...')
                            ->info()
                            ->send();
                        
                        // TODO: Actual test via PublisherFactory (baad me)
                        Notification::make()
                            ->title('Connection test coming soon!')
                            ->warning()
                            ->send();
                    }),
                
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

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWebsites::route('/'),
            'create' => Pages\CreateWebsite::route('/create'),
            'edit'   => Pages\EditWebsite::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count() ?: null;
    }
}