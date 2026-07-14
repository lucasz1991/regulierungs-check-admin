<?php

namespace App\Livewire\Admin\Cms\WebContent\News;

use App\Models\NewsCategory;
use App\Support\NewsCacheVersion;
use Illuminate\Support\Str;
use Livewire\Component;

class NewsCategoryManager extends Component
{
    public bool $show = false;

    public ?int $categoryId = null;
    public string $name = '';
    public string $color = '#2563EB';
    public ?string $icon = null;
    public int $sort_order = 0;

    protected $listeners = [
        'open-news-category-manager' => 'open',
    ];

    public function open(): void
    {
        $this->resetForm();
        $this->show = true;
    }

    public function resetForm(): void
    {
        $this->reset(['categoryId', 'name', 'icon']);
        $this->color = '#2563EB';
        $this->sort_order = (int) (NewsCategory::max('sort_order') ?? 0) + 1;
    }

    public function edit(int $id): void
    {
        $category = NewsCategory::findOrFail($id);

        $this->categoryId = $category->id;
        $this->name = $category->name;
        $this->color = $category->color;
        $this->icon = $category->icon;
        $this->sort_order = (int) $category->sort_order;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|min:2|max:100',
            'color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'integer|min:0',
        ]);

        $data = [
            'name' => $this->name,
            'color' => $this->color,
            'icon' => $this->icon ?: null,
            'sort_order' => $this->sort_order,
        ];

        if ($this->categoryId) {
            NewsCategory::findOrFail($this->categoryId)->update($data);
        } else {
            $data['slug'] = Str::slug($this->name);
            NewsCategory::create($data);
        }
        NewsCacheVersion::bump();

        $this->resetForm();
        $this->dispatch('refresh-news')->to('admin.cms.web-content.news.news-list');
    }

    public function delete(int $id): void
    {
        NewsCategory::findOrFail($id)->delete();
        NewsCacheVersion::bump();

        if ($this->categoryId === $id) {
            $this->resetForm();
        }

        $this->dispatch('refresh-news')->to('admin.cms.web-content.news.news-list');
    }

    public function render()
    {
        return view('livewire.admin.cms.web-content.news.news-category-manager', [
            'categories' => NewsCategory::ordered()->withCount('posts')->get(),
        ]);
    }
}
