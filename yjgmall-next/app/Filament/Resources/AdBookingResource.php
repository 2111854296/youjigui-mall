<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdBookingResource\Pages;
use App\Models\AdBooking;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AdBookingResource extends Resource
{
    protected static ?string $model = AdBooking::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = '广告预定';

    protected static ?string $navigationGroup = '运营';

    protected static ?string $modelLabel = '广告预定';

    protected static ?string $pluralModelLabel = '广告预定';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('用户')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('place_code')
                    ->label('广告位')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('title')
                    ->label('标题')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\FileUpload::make('image')
                    ->label('物料图片')
                    ->image()
                    ->disk('public')
                    ->directory('uploads/ads')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('contact_name')
                    ->label('联系人')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('contact_phone')
                    ->label('联系电话')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Textarea::make('remark')
                    ->label('备注')
                    ->rows(3)
                    ->columnSpanFull()
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options([
                        'pending' => '待审核',
                        'approved' => '已通过',
                        'rejected' => '已拒绝',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\ImageColumn::make('image')->label('物料')->disk('public')->height(40),
                Tables\Columns\TextColumn::make('title')->label('标题')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('user.name')->label('用户')->sortable(),
                Tables\Columns\TextColumn::make('place_code')->label('广告位')->searchable(),
                Tables\Columns\TextColumn::make('contact_name')->label('联系人')->searchable(),
                Tables\Columns\TextColumn::make('contact_phone')->label('联系电话')->searchable(),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'pending' => '待审核',
                        'approved' => '已通过',
                        'rejected' => '已拒绝',
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
            ->defaultSort('created_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdBookings::route('/'),
            'edit' => Pages\EditAdBooking::route('/{record}/edit'),
        ];
    }
}
