<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyPhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;


class PropertyPhotoController extends Controller
{
    public function gallery(Property $property)
    {
        $this->ensurePropertyOwnedByAuthenticatedAgent($property);

        $property->loadMissing(
            'photos',
            'province',
            'regency',
            'district',
            'village'
        );

        return view('agent.properties.photos-gallery', compact('property'));
    }

    public function store(Request $request, Property $property)
    {
        $this->ensurePropertyOwnedByAuthenticatedAgent($property);

        $request->validate([
            'photo' => 'required|image|max:5120' // Max 5MB
        ]);

        $path = $request->file('photo')->store('properties', 'public');

        PropertyPhoto::create([
            'property_id' => $property->id,
            'path' => $path
        ]);

        return back()->with('success', 'Photo uploaded');
    }

    public function destroy(Property $property, PropertyPhoto $photo)
    {
        $this->ensurePropertyOwnedByAuthenticatedAgent($property);

        // pastikan foto milik property ini
        abort_if($photo->property_id !== $property->id, 403);

        $path = (string) $photo->path;
        $hasOtherReferences = PropertyPhoto::query()
            ->where('path', $path)
            ->where('id', '!=', $photo->id)
            ->exists();

        // hapus file dari storage hanya jika tidak dipakai foto lain
        if (!$hasOtherReferences && $path !== '' && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($photo->path);
        }

        // hapus data dari database
        $photo->delete();

        return back()->with('success', 'Photo deleted successfully.');
    }

    private function ensurePropertyOwnedByAuthenticatedAgent(Property $property): void
    {
        abort_if(!Auth::check() || $property->agent_id !== Auth::id(), 403);
    }

}
