<?php

namespace app\controller\api;

use app\BaseController;
use app\model\Company;
use app\model\Product;
use app\model\Supply;
use think\Response;

class Companies extends BaseController
{
    public function index(): Response
    {
        $query = Company::publishedQuery();
        if ($this->request->param('q')) {
            $query->whereLike('name', '%'.$this->request->param('q').'%');
        }
        if ($this->request->param('area')) {
            $query->whereLike('area', '%'.$this->request->param('area').'%');
        }
        $query->order('is_featured', 'desc')->order('id', 'desc');
        $paginator = $query->paginate([
            'list_rows' => min(50, max(1, (int) $this->request->param('per_page', 20))),
            'page' => (int) $this->request->param('page', 1),
        ]);

        return json(paginate_laravel($paginator, static fn ($row) => $row->toListArray()));
    }

    public function show($id): Response
    {
        $item = is_numeric($id)
            ? Company::publishedQuery()->find($id)
            : Company::publishedQuery()->where('slug', $id)->find();
        if (!$item) {
            return json_error('Not Found', 404);
        }
        $data = $item->toListArray();
        $data['content'] = $item->content;
        $data['contact'] = $item->contact;
        $data['phone'] = $item->phone;
        $data['address'] = $item->address;
        $data['website'] = $item->website;
        $data['supplies'] = [];
        foreach (Supply::publishedQuery()->where('user_id', $item->user_id)->order('refreshed_at', 'desc')->limit(10)->select() as $s) {
            $data['supplies'][] = $s->toListArray();
        }
        $data['products'] = [];
        foreach (Product::publishedQuery()->where('user_id', $item->user_id)->order('id', 'desc')->limit(8)->select() as $p) {
            $data['products'][] = $p->toListArray();
        }

        return json_data($data);
    }
}
