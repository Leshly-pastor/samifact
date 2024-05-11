<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\System\Error;
use Illuminate\Http\Request;
use Exception;

class ErrorsController extends Controller
{
    public function index()
    {
        $error = Error::findOrFail(1); // Supongo que estás buscando un único error aquí
        return view('system.403.index', compact('error'));
    }
    
    
    public function update(Request $request)
    {
        try {
            $error = Error::findOrFail(1); // Supongo que estás buscando un único error aquí

            // Manejar la carga de la imagen
            if ($request->hasFile('image_file')) {
                $image = $request->file('image_file');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('uploads'), $imageName);
                $imageUrl = 'uploads/' . $imageName;


                // Actualizar el campo 'img' con la ruta de la imagen
                $error->img = $imageUrl;
            }

            // Actualizar otros campos del error
            $error->titulo = $request->input('text1');
            $error->comentario2 = $request->input('text2');
            $error->adm = $request->input('text3');
            $error->save();
            
            // Redirige de nuevo a la página de errores con un mensaje de éxito
            return redirect()->route('system.403.index')->with('success', '¡El error se actualizó correctamente!');
        } catch (Exception $e) {
            // Si ocurre algún error, redirige de vuelta con un mensaje de error
            return redirect()->back()->with('errors', 'Error al actualizar el error: ' . $e->getMessage());
        }
    }
}
