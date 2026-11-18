<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = '用户';

    protected static ?string $modelLabel = '用户';

    protected static ?string $pluralModelLabel = '用户';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('姓名')
                    ->required()
                    ->maxLength(255),
                Forms\Components\FileUpload::make('avatar')
                    ->label('头像')
                    ->image()
                    ->disk('public')
                    ->directory('uploads/avatars')
                    ->avatar()
                    ->maxSize(5120),
                Forms\Components\TextInput::make('username')
                    ->label('用户名')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('email')
                    ->label('邮箱')
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('mobile')
                    ->label('手机')
                    ->tel()
                    ->maxLength(20),
                Forms\Components\TextInput::make('password')
                    ->label('密码')
                    ->password()
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->maxLength(255),
                Forms\Components\TextInput::make('company_name')
                    ->label('公司名称')
                    ->maxLength(255),
                Forms\Components\Select::make('level')
                    ->label('等级')
                    ->options([
                        0 => '普通',
                        1 => 'VIP',
                        2 => '高级VIP',
                        3 => '企业VIP',
                    ])
                    ->required()
                    ->default(0),
                Forms\Components\DateTimePicker::make('vip_expire_at')
                    ->label('VIP到期时间'),
                Forms\Components\Toggle::make('is_certified')
                    ->label('认证')
                    ->default(false),
                Forms\Components\Select::make('member_type')
                    ->label('会员类型')
                    ->options([
                        'personal' => '个人会员',
                        'company' => '企业会员',
                    ])
                    ->required()
                    ->default('personal')
                    ->helperText('个人：采购/下单；企业：供应/商品/旺铺/卖出订单'),
                Forms\Components\Select::make('group_code')
                    ->label('会员组')
                    ->options([
                        'admin' => '管理员',
                        'banned' => '禁止访问',
                        'guest' => '游客',
                        'pending' => '待审核会员',
                        'personal' => '个人会员',
                        'enterprise' => '企业会员',
                        'vip' => 'VIP渠道会员',
                    ])
                    ->required()
                    ->default('personal'),
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options([
                        'pending' => '待审核',
                        'active' => '正常',
                        'banned' => '禁用',
                    ])
                    ->required()
                    ->default('pending'),
                Forms\Components\Toggle::make('is_admin')
                    ->label('管理员')
                    ->default(false),
                Forms\Components\TextInput::make('points')
                    ->label('积分')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('balance')
                    ->label('余额')
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('deposit')
                    ->label('保证金')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('mobile_verified')->label('手机认证'),
                Forms\Components\Toggle::make('bank_verified')->label('银行认证'),
                Forms\Components\Toggle::make('truename_verified')->label('实名认证'),
                Forms\Components\TextInput::make('truename')->label('真实姓名')->maxLength(50),
                Forms\Components\Select::make('online_status')
                    ->label('在线状态')
                    ->options([
                        'online' => '在线',
                        'invisible' => '隐身',
                        'offline' => '离线',
                    ])
                    ->default('online'),
            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('name')->label('姓名')->searchable(),
                Tables\Columns\TextColumn::make('username')->label('用户名')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('邮箱')->searchable(),
                Tables\Columns\TextColumn::make('mobile')->label('手机')->searchable(),
                Tables\Columns\TextColumn::make('company_name')->label('公司')->toggleable(),
                Tables\Columns\TextColumn::make('member_type')
                    ->label('类型')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state === 'company' ? '企业' : '个人')
                    ->color(fn (?string $state) => $state === 'company' ? 'success' : 'gray'),
                Tables\Columns\TextColumn::make('level')->label('等级')->sortable(),
                Tables\Columns\TextColumn::make('group_code')->label('会员组')->badge(),
                Tables\Columns\TextColumn::make('status')->label('状态')->badge(),
                Tables\Columns\IconColumn::make('is_admin')->label('管理员')->boolean(),
                Tables\Columns\TextColumn::make('points')->label('积分')->sortable(),
                Tables\Columns\TextColumn::make('balance')->label('余额')->sortable(),
                Tables\Columns\TextColumn::make('deposit')->label('保证金')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->label('创建时间')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('member_type')
                    ->label('会员类型')
                    ->options([
                        'personal' => '个人',
                        'company' => '企业',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => '待审核',
                        'active' => '正常',
                        'banned' => '禁用',
                    ]),
                Tables\Filters\SelectFilter::make('group_code')
                    ->label('会员组')
                    ->options([
                        'pending' => '待审核',
                        'personal' => '个人',
                        'enterprise' => '企业',
                        'vip' => 'VIP',
                        'banned' => '禁用',
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
