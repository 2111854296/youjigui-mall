<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvestProjectResource\Pages;
use App\Models\InvestProject;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InvestProjectResource extends Resource
{
    protected static ?string $model = InvestProject::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationLabel = '招商项目';

    protected static ?string $navigationGroup = '内容';

    protected static ?string $modelLabel = '招商项目';

    protected static ?string $pluralModelLabel = '招商项目';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('标题')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('area')
                    ->label('区域')
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount')
                    ->label('金额')
                    ->maxLength(255),
                Forms\Components\Textarea::make('summary')
                    ->label('摘要')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('content')
                    ->label('内容')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('contact_name')
                    ->label('联系人')
                    ->maxLength(255),
                Forms\Components\TextInput::make('contact_phone')
                    ->label('联系电话')
                    ->tel()
                    ->maxLength(30),
                Forms\Components\TextInput::make('sort')
                    ->label('排序')
                    ->numeric()
                    ->default(0),
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options([
                        'published' => '已发布',
                        'offline' => '已下架',
                    ])
                    ->required()
                    ->default('published'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('标题')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('area')->label('区域')->searchable(),
                Tables\Columns\TextColumn::make('amount')->label('金额'),
                Tables\Columns\TextColumn::make('contact_name')->label('联系人')->searchable(),
                Tables\Columns\TextColumn::make('contact_phone')->label('联系电话')->searchable(),
                Tables\Columns\TextColumn::make('sort')->label('排序')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'published' => '已发布',
                        'offline' => '已下架',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvestProjects::route('/'),
            'create' => Pages\CreateInvestProject::route('/create'),
            'edit' => Pages\EditInvestProject::route('/{record}/edit'),
        ];
    }
}
