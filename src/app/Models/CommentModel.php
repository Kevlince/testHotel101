<?php

namespace App\Models;

use CodeIgniter\Model;

class CommentModel extends Model
{
    protected $table            = 'comments';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['name', 'text'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = '';

    public function getPaginated(int $page = 1, int $perPage = 3, string $sortBy = 'id', string $order = 'desc'): array
    {
        $builder = $this->builder();

        // Защита от неверных параметров
        $allowedSort = ['id', 'created_at'];
        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = 'id';
        }
        if (!in_array(strtolower($order), ['asc', 'desc'])) {
            $order = 'desc';
        }

        $builder->orderBy($sortBy, $order);

        $total = $builder->countAllResults(false);
        $offset = ($page - 1) * $perPage;

        $comments = $builder->limit($perPage, $offset)->get()->getResultArray();

        $totalPages = (int) ceil($total / $perPage);

        return [
                'comments'   => $comments,
                'pagination' => [
                        'current'  => $page,
                        'total'    => $total,
                        'pages'    => $totalPages,
                        'per_page' => $perPage,
                ],
        ];
    }
}