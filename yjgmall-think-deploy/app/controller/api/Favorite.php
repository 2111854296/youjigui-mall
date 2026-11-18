<?php

namespace app\controller\api;

use app\BaseController;
use app\model\Favorite as FavoriteModel;
use think\Response;

class Favorite extends BaseController
{
    public function index(): Response
    {
        return json_data(FavoriteModel::where('user_id', $this->request->user->id)->order('id', 'desc')->select()->toArray());
    }

    public function store(): Response
    {
        $id = FavoriteModel::create([
            'user_id' => $this->request->user->id,
            'target_type' => $this->request->post('target_type', 'supply'),
            'target_id' => $this->request->post('target_id'),
        ])->id;

        return json_data(['id' => $id], 201);
    }

    public function destroy($id): Response
    {
        FavoriteModel::where('user_id', $this->request->user->id)->where('id', $id)->delete();

        return json_message('ok');
    }
}
