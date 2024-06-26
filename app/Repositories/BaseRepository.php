<?php
namespace App\Repositories;
use Illuminate\Database\Eloquent\Model;
abstract class BaseRepository
{
    protected $model;
    protected $columns = [];
    public function __construct(Model $model)
    {
        $this->model = $model;
    }
    public function all()
    {
        return $this->model->all();
    }
    public function paginate($params)
    {
        $query = $this->model;
        if (isset($params['page'])) {
            unset($params['page']);
        }
        $query = $this->applyFilters($query, $params);
        return $query->paginate(10);
    }
    protected function applyFilters($query, $params)
    {
        $query = $query->orderBy('id', 'desc');
        if (!empty($params['s'])) {
            $searchTerm = $params['s'];
            $searchableColumns = $this->columns;
            $query->where(function ($query) use ($searchTerm, $searchableColumns) {
                foreach ($searchableColumns as $column) {
                    $query->orWhere($column, 'like', "%$searchTerm%");
                }
            });
        }
        return $query;
    }
    public function findById($id)
    {
        return $this->model->find($id);
    }
    public function findByKey($key = '', $value = '')
    {
        return $this->model->where($key, $value)->first();
    }
    public function add(array $data)
    {
        return $this->model->create($data);
    }
    public function update($id, array $data)
    {
        $item = $this->model->findOrFail($id);
        $item->update($data);
        return $item;
    }
    public function delete($id)
    {
        return $this->model->destroy($id);
    }
    public function deleteMulti(array $ids)
    {
        return $this->model->whereIn('id', $ids)->delete();
    }
    public function getDataByKey($key = '', $value = '')
    {
        $item = $this->model->where($key, $value)->get();
        return $item;
    }
    public function checkKeyNotInArray($key = '',array $ids){
        return $this->model->whereNotIn($key, $ids)->get();
    }
    public function countAll()
    {
        return $this->model::count();
    }
}
