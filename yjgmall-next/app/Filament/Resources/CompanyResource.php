<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CompanyResource\Pages;
use App\Models\Company;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = '企业';

    protected static ?string $modelLabel = '企业';

    protected static ?string $pluralModelLabel = '企业';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('用户')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('企业名称')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('logo')
                    ->label('企业Logo')
                    ->image()
                    ->disk('public')
                    ->directory('uploads/companies')
                    ->maxSize(5120),
                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('template')
                    ->label('旺铺模板')
                    ->options([
                        'default' => '经典橙',
                        'blue' => '商务蓝',
                        'orange' => '活力橙深',
                    ])
                    ->default('default'),
                Forms\Components\TextInput::make('contact')
                    ->label('联系人')
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->label('电话')
                    ->tel()
                    ->maxLength(50),
                Forms\Components\TextInput::make('website')
                    ->label('网站')
                    ->url()
                    ->maxLength(255),
                Forms\Components\TextInput::make('address')
                    ->label('地址')
                    ->maxLength(255),
                Forms\Components\TextInput::make('area')
                    ->label('地区')
                    ->maxLength(255),
                Forms\Components\Textarea::make('business')
                    ->label('经营范围')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('intro')
                    ->label('简介')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('content')
                    ->label('详情')
                    ->fileAttachmentsDisk('public')
                    ->fileAttachmentsDirectory('uploads/editor')
                    ->columnSpanFull(),
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options([
                        'pending' => '待审核',
                        'approved' => '已通过',
                        'rejected' => '已拒绝',
                        'offline' => '已下架',
                    ])
                    ->required()
                    ->default('pending'),
                Forms\Components\Toggle::make('is_featured')
                    ->label('推荐')
                    ->default(false),
                Forms\Components\Toggle::make('is_certified')
                    ->label('认证')
                    ->default(false),
            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\ImageColumn::make('logo')->label('Logo')->disk('public')->height(36),
                Tables\Columns\TextColumn::make('name')->label('企业名称')->searchable(),
                Tables\Columns\TextColumn::make('slug')->label('Slug')->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('用户')->sortable(),
                Tables\Columns\TextColumn::make('contact')->label('联系人'),
                Tables\Columns\TextColumn::make('phone')->label('电话'),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\IconColumn::make('is_featured')->label('推荐')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => '待审核',
                        'approved' => '已通过',
                        'rejected' => '已拒绝',
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
            'index' => Pages\ListCompanies::route('/'),
            'create' => Pages\CreateCompany::route('/create'),
            'edit' => Pages\EditCompany::route('/{record}/edit'),
        ];
    }
}
