<?php

namespace app\controller\api;

use app\BaseController;
use app\model\ProductReview;
use think\Response;

class Review extends BaseController
{
    public function mine(): Response
    {
        return json_data(ProductReview::where('user_id', $this->request->user->id)->order('id', 'desc')->select()->toArray());
    }

    public function store($id): Response
    {
        $item = ProductReview::create([
            'user_id' => $this->request->user->id,
            'product_id' => $id,
            'rating' => $this->request->post('rating', 5),
            'content' => $this->request->post('content', ''),
        ]);

        return json_data($item->toArray(), 201);
    }
}
