<?php

namespace App\Filament\Resources;

use App\Filament\Resources\KnowQuestionResource\Pages;
use App\Models\KnowQuestion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class KnowQuestionResource extends Resource
{
    protected static ?string $model = KnowQuestion::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationLabel = '知道问题';

    protected static ?string $navigationGroup = '扩展模块';

    protected static ?string $modelLabel = '知道问题';

    protected static ?string $pluralModelLabel = '知道问题';

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
                Forms\Components\RichEditor::make('content')
                    ->label('内容')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('reward')
                    ->label('悬赏')
                    ->numeric()
                    ->default(0),
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options([
                        'open' => '开放',
                        'closed' => '已关闭',
                    ])
                    ->required()
                    ->default('open'),
                Forms\Components\TextInput::make('views')
                    ->label('浏览')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('answers_count')
                    ->label('回答数')
                    ->numeric()
                    ->default(0)
                    ->disabled()
                    ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('title')->label('标题')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('reward')->label('悬赏')->sortable(),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('answers_count')->label('回答数')->sortable(),
                Tables\Columns\TextColumn::make('views')->label('浏览')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'open' => '开放',
                        'closed' => '已关闭',
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
            'index' => Pages\ListKnowQuestions::route('/'),
            'create' => Pages\CreateKnowQuestion::route('/create'),
            'edit' => Pages\EditKnowQuestion::route('/{record}/edit'),
        ];
    }
}
