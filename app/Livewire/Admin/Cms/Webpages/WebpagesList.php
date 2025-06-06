<?php

namespace App\Livewire\Admin\Cms\Webpages;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Illuminate\Http\Request;
use App\Http\Controllers\Admin\MediaController;
use App\Models\WebPage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class WebpagesList extends Component
{
    use WithPagination, WithFileUploads;

    public $title, $slug, $meta_title, $meta_description, $meta_keywords, $canonical_url, $robots_meta;
    public $og_title, $og_description, $og_image;
    public $custom_css, $custom_js, $custom_meta;
    public $icon, $header_image, $new_header_image, $is_active, $published_from, $published_until, $language, $showHeader;
    public $editingId = null;
    public $modalOpen = false;
    public $page = null;





    public function create()
    {
        $this->resetForm();
        $this->modalOpen = true;
    }

    public function edit($id)
    {
        $this->page = WebPage::findOrFail($id);
        $this->editingId = $this->page->id;
        $this->fill($this->page->toArray());
        $this->header_image = $this->page->header_image; 
        $this->showHeader = $this->page->settings['showHeader'] ?? false;

        $this->modalOpen = true;
    }

    public function save()
    {
        $this->validate([
            'title' => 'required|string|max:255|unique:web_pages,title,' . $this->editingId,
            'slug' => 'required|string|max:255|unique:web_pages,slug,' . $this->editingId,
            'meta_description' => 'nullable|string',
            'meta_keywords' => 'nullable|string',
            'custom_meta' => 'nullable|array',
            'new_header_image' => 'nullable|image|max:2048',
        ]);

        if (!$this->slug) {
            $this->slug = Str::slug($this->title);
        }

        // Falls ein neues Bild hochgeladen wurde, speichere es
        if ($this->new_header_image) {
            if ($this->header_image) {
                $this->deleteImageViaMediaController($this->header_image); // Altes Bild löschen
            }
            $this->header_image = $this->uploadImageViaMediaController($this->new_header_image);
        }

        $data = [
            'title' => $this->title,
            'slug' => $this->slug,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'canonical_url' => $this->canonical_url,
            'robots_meta' => $this->robots_meta,
            'og_title' => $this->og_title,
            'og_description' => $this->og_description,
            'custom_css' => $this->custom_css,
            'custom_js' => $this->custom_js,
            'custom_meta' => $this->custom_meta,
            'icon' => $this->icon,
            'header_image' => $this->header_image,
            'is_active' => $this->is_active,
            'published_from' => $this->published_from,
            'published_until' => $this->published_until,
            'settings' => [ 
                'showHeader' => $this->showHeader, 
            ],
        ];

        if ($this->editingId) {
            WebPage::find($this->editingId)->update($data);
        } else {
            WebPage::create($data);
        }

        $this->modalOpen = false;
        $this->resetForm();
    }

    public function delete($id)
    {
        $page = WebPage::findOrFail($id);
        if (!$page->is_fixed) {
            // Falls ein Header-Bild existiert, löschen
            if ($page->header_image) {
                $this->deleteImageViaMediaController($page->header_image);
            }
            $page->delete();
        }
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->title = $this->slug = $this->meta_title = $this->meta_description = $this->meta_keywords = '';
        $this->canonical_url = $this->robots_meta = $this->og_title = $this->og_description = '';
        $this->custom_css = $this->custom_js = '';
        $this->custom_meta = [];
        $this->icon = null;
        $this->header_image = null;
        $this->new_header_image = null;
        $this->is_active = true;
        $this->published_from = $this->published_until = null;
        $this->page = null;
    }


    
    protected function uploadImageViaMediaController($file)
    {
                // Temporäres Request-Objekt mit dem File als 'file'
        $request = Request::create('/admin/media/upload', 'POST', [], [], ['file' => $file]);

        // MediaController manuell instanziieren und aufrufen
        $controller = new MediaController();
        $response = $controller->store($request);

        if (method_exists($response, 'getData')) {
            return $response->getData(true)['path'] ?? '';
        }

        throw new \Exception('Upload fehlgeschlagen.');
    }

    protected static function deleteImageViaMediaController($path)
    {
        if (!$path) {
            return;
        }

        try {
            // Temporären Request mit POST-Daten (obwohl eigentlich DELETE, das ist okay für internen Aufruf)
            $request = Request::create('/admin/media/delete', 'POST', ['path' => $path]);

            // MediaController aufrufen
            $controller = new MediaController();
            $response = $controller->destroy($request);

            if (method_exists($response, 'getData')) {
                $result = $response->getData(true);
                if (!($result['success'] ?? false)) {
                    \Log::warning('Löschen nicht erfolgreich: ' . ($result['message'] ?? 'unbekannt'));
                }
            }
        } catch (\Throwable $e) {
            \Log::warning('Bild konnte nicht über MediaController gelöscht werden: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.admin.cms.webpages.webpages-list', [
            'fixedPages' => WebPage::where('is_fixed', true)->paginate(10),
            'customPages' => WebPage::where('is_fixed', false)->paginate(10),
        ]);
    }
}
