<?php

namespace App\Http\Controllers;

use App\Models\PagebuilderProject;
use App\Models\Post;
use App\Support\NewsCacheVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PagebuilderProjectController extends Controller
{
    /**
     * Speichert oder aktualisiert ein Pagebuilder-Projekt.
     */
    public function save(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|integer|exists:pagebuilder_projects,id',
                'data' => 'required|json',
                'html' => 'required|string',
                'css' => 'nullable|string',
                'allow_empty' => 'sometimes|boolean',
            ]);

            $existingProject = PagebuilderProject::findOrFail($validated['id']);

            if (! ($validated['allow_empty'] ?? false)
                && $this->hasMeaningfulContent((string) $existingProject->cleaned_html)
                && $this->isEmptyEditorExport($validated['data'], $validated['html'])) {
                throw ValidationException::withMessages([
                    'html' => 'Ein leerer Editor-Autosave wurde verworfen, damit vorhandener Inhalt nicht überschrieben wird.',
                ]);
            }

            $project = $existingProject;
            $project->fill([
                'data' => $validated['data'],
                'html' => $validated['html'],
                'last_edited_by' => Auth::id(),
            ]);
            $project->saveQuietly();

            Log::info('Projekt gespeichert', ['project_id' => $project->id, 'last_edited_by' => $project->last_edited_by]);
            // Seiten, Module und News verwenden denselben Cleaner. Der
            // Studio-CSS-Export bleibt dabei getrennt vom bereinigten HTML.
            $project->updateProjekt(
                array_key_exists('css', $validated)
                    ? (string) ($validated['css'] ?? '')
                    : null
            );

            if (Post::where('type', 'news')->where('pagebuilder_project_id', $project->id)->exists()) {
                NewsCacheVersion::bump();
            }

            return response()->json([
                'message' => 'Projekt gespeichert',
                'project' => $project,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validierungsfehler beim Speichern', ['errors' => $e->errors()]);

            return response()->json([
                'error' => 'Validierungsfehler',
                'messages' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Fehler beim Speichern des Projekts', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'error' => 'Interner Serverfehler',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function hasMeaningfulContent(string $html): bool
    {
        if (trim(strip_tags($html)) !== '') {
            return true;
        }

        return preg_match(
            '/<(?:img|picture|video|audio|iframe|svg|canvas|form|input|button)\b/i',
            $html
        ) === 1;
    }

    private function isEmptyEditorExport(string $data, string $html): bool
    {
        $project = json_decode($data, true);
        $pages = is_array($project) ? ($project['pages'] ?? []) : [];

        foreach ($pages as $page) {
            foreach (($page['frames'] ?? []) as $frame) {
                $components = $frame['component']['components'] ?? [];

                if (is_array($components) && $components !== []) {
                    return false;
                }
            }
        }

        $body = $html;

        if (preg_match('/<body\b[^>]*>(.*?)<\/body>/is', $html, $matches)) {
            $body = $matches[1];
        }

        return ! $this->hasMeaningfulContent($body);
    }

    public function load($id)
    {
        Log::info('Projektladeanfrage', ['user_id' => Auth::id(), 'project_id' => $id]);

        $project = PagebuilderProject::findOrFail($id);

        if (! $project) {
            Log::warning('Projekt nicht gefunden, neues wird erstellt', ['user_id' => Auth::id(), 'project_id' => $id]);

            // Standard-JSON für neue Projekte
            $defaultData = '{"assets":[],"styles":[],"pages":[{"frames":[{"component":{"type":"wrapper","attributes":{"id":"itix"},"components":[{"tagName":"section","classes":["text-gray-600","body-font","relative"],"attributes":{"id":"iyduu"},"components":[{"classes":["container","px-5","py-24","mx-auto"],"attributes":{"id":"i91ng"},"components":[{"classes":["flex","flex-col","text-center","w-full","mb-12"],"attributes":{"id":"in4uu"},"components":[{"type":"heading","classes":["sm:text-3xl","text-2xl","font-medium","title-font","mb-4","text-gray-900"],"attributes":{"id":"igmy6"},"components":[{"type":"textnode","content":"Neues Pagebuilder Project"}]},{"tagName":"p","type":"text","classes":["lg:w-2/3","mx-auto","leading-relaxed","text-base"],"attributes":{"id":"i0w6e"},"components":[{"type":"textnode","content":"Hier kannst du kreativ werden und deine Träume verwirklichen!"}]}]}]}]}],"doctype":"<!DOCTYPE html>","head":{"type":"head","components":[{"tagName":"meta","void":true,"attributes":{"charset":"utf-8"}},{"tagName":"meta","void":true,"attributes":{"name":"viewport","content":"width=device-width,initial-scale=1"}},{"tagName":"meta","void":true,"attributes":{"name":"robots","content":"index,follow"}},{"tagName":"meta","void":true,"attributes":{"name":"generator","content":"LMZ Studio Project"}},{"tagName":"link","type":"link","attributes":{"href":"https://admin850.regulierungs-check.de/adminresources/css/tailwind.min.css","rel":"stylesheet"}}]},"docEl":{"tagName":"html"}},"id":"8uKM3pEMmO8ZbWvE"}],"type":"main","id":"BGeRYNcKhJpNIMjv"}],"symbols":[],"dataSources":[],"custom":{"projectType":"web","id":""}}';

            $randomNumber = rand(1000, 9999);
            $projectName = "Neues Projekt {$randomNumber}";
            $maxOrderId = PagebuilderProject::max('order_id') ?? 0;
            $maxOrderIdIterated = $maxOrderId + 1;
            $project = PagebuilderProject::create([
                'name' => $projectName,
                'data' => $defaultData,
                'status' => 0,
                'order_id' => $maxOrderIdIterated,
            ]);

            Log::info('Neues Projekt erstellt', ['project_id' => $project->id]);
        } else {
            Log::info('Projekt geladen', ['project_id' => $project->id]);
        }

        return response(json_decode($project->data, true), 200, ['Content-Type' => 'application/json']);
    }
}
