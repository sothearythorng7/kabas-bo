<?php

namespace App\Http\Controllers;

use App\Models\HomeContent;
use Illuminate\Http\Request;

class HomeContentController extends Controller
{
    public function edit()
    {
        $presentationText = HomeContent::where('key', 'presentation_text')->first();

        return view('home-content.edit', compact('presentationText'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'presentation_text_fr' => 'required|string|max:1000',
            'presentation_text_en' => 'required|string|max:1000',
        ]);

        HomeContent::updateByKey('presentation_text', [
            'fr' => $validated['presentation_text_fr'],
            'en' => $validated['presentation_text_en'],
        ]);

        return redirect()->route('home-content.edit')
            ->with('success', 'Le contenu de la page d\'accueil a été mis à jour avec succès.');
    }
}
