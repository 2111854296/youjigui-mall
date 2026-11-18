<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExhibitResource\Pages;
use App\Models\Exhibit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExhibitResource extends Resource
{
    protected static ?string $model = Exhibit::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = '展会';

    protected static ?string $navigationGroup = '扩展模块';

    protected static ?string $modelLabel = '展会';

    protected static ?string $pluralModelLabel = '展会';

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
                    ->label('地区')
                    ->maxLength(255),
                Forms\Components\TextInput::make('venue')
                    ->label('场馆')
                    ->maxLength(255),
                Forms\Components\DatePicker::make('starts_on')
                    ->label('开始日期'),
                Forms\Components\DatePicker::make('ends_on')
                    ->label('结束日期'),
                Forms\Components\TextInput::make('organizer')
                    ->label('主办方')
                    ->maxLength(255),
                Forms\Components\Textarea::make('summary')
                    ->label('摘要')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('content')
                    ->label('内容')
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options([
                        'published' => '已发布',
                        'offline' => '已下架',
                    ])
                    ->required()
                    ->default('published'),
                Forms\Components\TextInput::make('views')
                    ->label('浏览')
                    ->numeric()
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('标题')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('area')->label('地区')->toggleable(),
                Tables\Columns\TextColumn::make('venue')->label('场馆')->toggleable(),
                Tables\Columns\TextColumn::make('starts_on')->label('开始')->date()->sortable(),
                Tables\Columns\TextColumn::make('ends_on')->label('结束')->date()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('organizer')->label('主办方')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('views')->label('浏览')->sortable(),
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
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExhibits::route('/'),
            'create' => Pages\CreateExhibit::route('/create'),
            'edit' => Pages\EditExhibit::route('/{record}/edit'),
        ];
    }
}
