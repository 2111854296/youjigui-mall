<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticleResource\Pages;
use App\Models\Article;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    protected static ?string $navigationLabel = '文章';

    protected static ?string $modelLabel = '文章';

    protected static ?string $pluralModelLabel = '文章';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('标题')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('thumb')
                    ->label('标题图片')
                    ->image()
                    ->disk('public')
                    ->directory('uploads/articles')
                    ->maxSize(5120),
                Forms\Components\Select::make('category_id')
                    ->label('分类')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('author')
                    ->label('作者')
                    ->maxLength(255),
                Forms\Components\Textarea::make('summary')
                    ->label('摘要')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('content')
                    ->label('内容')
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('uploads/editor')
                    ->columnSpanFull(),
                Forms\Components\Select::make('type')
                    ->label('类型')
                    ->options([
                        'news' => '资讯',
                        'quote' => '行情',
                    ])
                    ->required()
                    ->default('news'),
                Forms\Components\Toggle::make('is_top')
                    ->label('置顶')
                    ->default(false),
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options([
                        'draft' => '草稿',
                        'published' => '已发布',
                        'offline' => '已下架',
                    ])
                    ->required()
                    ->default('draft'),
                Forms\Components\DateTimePicker::make('published_at')
                    ->label('发布时间'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\ImageColumn::make('thumb')->label('图')->disk('public')->height(40),
                Tables\Columns\TextColumn::make('title')->label('标题')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('author')->label('作者')->searchable(),
                Tables\Columns\TextColumn::make('type')->label('类型')->badge(),
                Tables\Columns\IconColumn::make('is_top')->label('置顶')->boolean(),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('published_at')->label('发布时间')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'news' => '资讯',
                        'quote' => '行情',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => '草稿',
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
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
