<?php

namespace App\Controllers;

use App\Models\CommentModel;

class Comments extends BaseController {
    public function index() {
        return view('comments/index');
    }

    public function list() {
        $page = (int) $this->request->getGet('page') ?: 1;
        $sort = $this->request->getGet('sort') ?: 'created_at';
        $order = $this->request->getGet('order') ?: 'desc';

        $model = new CommentModel();
        $data = $model->getPaginated($page, 3, $sort, $order);

        return $this->response->setJSON($data);
    }

    public function add() {
        $validation = \Config\Services::validation();
        $rules = [
                'name' => 'required|valid_email|max_length[255]',
                'text' => 'required|min_length[5]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                    'success' => false,
                    'errors'  => $validation->getErrors(),
            ]);
        }

        $model = new CommentModel();
        $data = [
                'name' => $this->request->getPost('name'),
                'text' => $this->request->getPost('text'),
        ];

        $model->insert($data);

        return $this->response->setJSON(['success' => true]);
    }

    public function delete($id = null) {
        if (!$id) {
            return $this->response->setJSON(['success' => false]);
        }

        $model = new CommentModel();
        $model->delete($id);

        return $this->response->setJSON(['success' => true]);
    }
}