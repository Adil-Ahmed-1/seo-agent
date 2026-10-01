<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KeywordResource\Pages;
use App\Models\Keyword;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class KeywordResource extends Resource
{
    protected static ?string $model = Keyword::class;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'SEO Management';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Keyword Details')
                ->schema([
                    Forms\Components\Select::make('website_id')
                        ->relationship('website', 'name')
                        ->required()
                        ->searchable()
                        ->preload(),
                    
                    Forms\Components\TextInput::make('keyword')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('digital marketing agency'),
                    
                    Forms\Components\Select::make('intent')
                        ->options([
                            'informational' => 'Informational',
                            'commercial'    => 'Commercial',
                            'transactional' => 'Transactional',
                            'navigational'  => 'Navigational',
                        ]),
                ])->columns(2),

            Forms\Components\Section::make('Metrics')
                ->schema([
                    Forms\Components\TextInput::make('search_volume')
                        ->numeric()
                        ->suffix('searches/mo'),
                    
                    Forms\Components\TextInput::make('difficulty')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('/100'),
                    
                    Forms\Components\TextInput::make('cpc')
                        ->numeric()
                        ->prefix('$')
                        ->step(0.01),
                ])->columns(3),

            Forms\Components\Section::make('Ranking')
                ->schema([
                    Forms\Components\TextInput::make('current_rank')
                        ->numeric()
                        ->suffix('position'),
                    
                    Forms\Components\TextInput::make('target_rank')
                        ->numeric()
                        ->default(10)
                        ->suffix('position'),
                    
                    Forms\Components\Select::make('status')
                        ->options([
                            'active'   => 'Active',
                            'paused'   => 'Paused',
                            'archived' => 'Archived',
                        ])
                        ->default('active'),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('keyword')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(50),
                
                Tables\Columns\TextColumn::make('website.name')
                    ->label('Website')
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('search_volume')
                    ->numeric()
                    ->sortable()
                    ->placeholder('—'),
                
                Tables\Columns\TextColumn::make('difficulty')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state === null => 'gray',
                        $state < 30    => 'success',
                        $state < 60    => 'warning',
                        default        => 'danger',
                    })
                    ->placeholder('—'),
                
                Tables\Columns\TextColumn::make('current_rank')
                    ->label('Rank')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state === null => 'gray',
                        $state <= 3    => 'success',
                        $state <= 10   => 'warning',
                        default        => 'danger',
                    })
                    ->placeholder('—'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'paused',
                        'gray'    => 'archived',
                    ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('website')
                    ->relationship('website', 'name'),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active'   => 'Active',
                        'paused'   => 'Paused',
                        'archived' => 'Archived',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListKeywords::route('/'),
            'create' => Pages\CreateKeyword::route('/create'),
            'edit'   => Pages\EditKeyword::route('/{record}/edit'),
        ];
    }
}