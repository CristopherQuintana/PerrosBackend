<?php

// app/Repositories/PerroRepository.php

namespace App\Repositories;

use App\Models\Perro;
use App\Models\Interaccion;

class PerroRepository
{
    public function createPerro($request)
    {
        try {
            $perro = Perro::create([
                'nombre' => $request->nombre,
                'foto_url' => $request->foto,
                'descripcion' => $request->descripcion,
            ]);

            if (!$perro) {
                return response()->json(['message' => 'Perro no creado'], 404);
            }

            return response()->json($perro, 201);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function readPerro($id)
    {
        try {
            $perro = Perro::find($id);

            if (!$perro) {
                return response()->json(['message' => 'Perro no encontrado'], 404);
            }

            return response()->json($perro, 200);

        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function updatePerro($id, $request)
    {
        try {
            $perro = Perro::find($id);
            // Obtener los atributos de la solicitud que están presentes
            $atributos = array_filter([
                'nombre' => $request->nombre ?? null,
                'foto_url' => $request->foto ?? null,
                'descripcion' => $request->descripcion ?? null,
            ]);
            // Actualizar el perro solo con los atributos presentes
            $perro->update($atributos);
            if (!$perro) {
                return response()->json(['message' => 'Perro no encontrado'], 404);
            }
            return response()->json($perro, 201);
        } catch (Exception $e) {
            $this->handleException($e);
        }
        
    }

    public function deletePerro($id)
    {
        try {
            $perro = Perro::find($id);
            if (!$perro) {
                return response()->json(['message' => 'Perro no encontrado'], 404);
            }
            $perro->delete();
            return response()->json(['message' => 'Perro eliminado correctamente'], 201);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function obtenerPerroRandom()
    {
        try {
            $perro = Perro::inRandomOrder()->select('id', 'nombre')->first();

            if (!$perro) {
                return response()->json(['message' => 'No hay Perros'], 200);
            }

            return response()->json($perro, 200);
        } catch (Exception $e) {
            $this->handleException($e);
        }
        
    }

    public function obtenerPerrosInteresados($id, $request)
    {
        try {
            $perros = Perro::inRandomOrder()
                ->where('id', '!=', $id)
                ->whereNotIn('id', function ($query) use ($id) {
                    $query->select('perro_candidato_id')
                        ->from('interacciones')
                        ->where('perro_interesado_id', '=', $id);
                })
                ->take($request->numero)
                ->get();

            if ($perros->isEmpty()) {
                return response()->json(['message' => 'No hay perros'], 200);
            }

            return response()->json($perros, 200);
        } catch (Exception $e) {
            $this->handleException($e);
        }
        
    }

    public function obtenerPerrosCandidatos($id, $request)
    {
        try {
            $perros = Perro::inRandomOrder()
                ->where('perros.id', '=', $id) // Filtrar por la ID proporcionada en el input
                ->join('interacciones', 'perros.id', '=', 'interacciones.perro_interesado_id')
                ->join('perros as perros_candidatos', 'interacciones.perro_candidato_id', '=', 'perros_candidatos.id')
                ->where('interacciones.preferencia', '=', null)
                ->select('perros_candidatos.*')
                ->take($request->numero)
                ->get();

            if ($perros->isEmpty()) {
                return response()->json(['message' => 'No hay perros'], 200);
            }

            return response()->json($perros, 200);
        } catch (Exception $e) {
            $this->handleException($e);
        }
        
    }

    public function guardarInteraccion($idInteresado, $idCandidato)
    {
        try {
            // Crear una nueva instancia de la interacción sin la preferencia
            $nuevaInteraccion = new Interaccion([
                'perro_interesado_id' => $idInteresado,
                'perro_candidato_id' => $idCandidato,
                'preferencia' => null,
            ]);

            // Guardar la interacción en la base de datos
            $nuevaInteraccion->save();

            return response()->json(['message' => 'Interacción guardada exitosamente'], 200);
        } catch (Exception $e) {
            // Manejar la excepción según tus necesidades
            return $this->handleException($e);
        }
    }

    public function guardarPreferencias($idInteresado, $idCandidato, $request)
    {
        try {
            if(!$request->preferencia){
                return response()->json(['message' => 'Preferencia no ha sido agregada'], 422);
            }

            $interaccion = Interaccion::where('perro_interesado_id', $idInteresado)
                ->where('perro_candidato_id', $idCandidato)
                ->first();

            if(!$interaccion){
                return response()->json(['message' => 'No existe tal interaccion'], 404);
            }

            $interaccion->preferencia = $request->preferencia;
            $interaccion->save();

            $match = false;

            // Verificar si hay un match
            if ($request->preferencia === 'aceptado') {
                $interaccionInversa = Interaccion::where('perro_interesado_id', $idCandidato)
                    ->where('perro_candidato_id', $idInteresado)
                    ->where('preferencia', 'aceptado')
                    ->first();

                if ($interaccionInversa) {
                    $match = true;
                }
            }

            return response()->json(['message' => $match ? 'Hay match' : 'Ok'], 200);
        }

         catch (Exception $e) {
            $this->handleException($e);
        }
    }
    
    private function validarInteraccion($perroInteresado, $perroCandidato, $preferencia)
    {
        // Aquí puedes agregar la lógica de validación específica
        // Puedes utilizar la lógica que mencionamos anteriormente para la restricción única
        // Asegúrate de ajustarla según las necesidades específicas de tu aplicación
    
        // Ejemplo:
        if (Interaccion::where('perro_interesado_id', $perroInteresado->id)
            ->where('perro_candidato_id', $perroCandidato->id)
            ->exists()) {
            return false; // La combinación ya existe
        }
    
        return true; // La combinación es válida
    }

    public function verPerros($id, $interes){
        // Obtener los perros interesados o no
        try {
            $perros = Perro::join('interacciones', function ($join) use ($id, $interes) {
                $join->on('perros.id', '=', 'interacciones.perro_candidato_id')
                    ->where('interacciones.perro_interesado_id', '=', $id)
                    ->where('interacciones.preferencia', '=', $interes);
            })
            ->select('perros.*')
            ->get();
    
            if ($perros->isEmpty()) {
                return response()->json(['message' => 'No hay perros'], 200);
            }
            return response()->json($perros, 200);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function verPerrosAceptados($id){
        return $this->verPerros($id, 'aceptado');
    }

    public function verPerrosRechazados($id){
        return $this->verPerros($id, 'rechazado');
    }

    public function verPerrosGeneral(){
        try {
            $perros = Perro::get();
    
            if ($perros->isEmpty()) {
                return response()->json(['message' => 'No hay perros'], 404);
            }
            return response()->json($perros, 200);
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    private function handleException(Exception $e)
    {
        Log::info([
            "error" => $e->getMessage(),
            "linea" => $e->getLine(),
            "file" => $e->getFile(),
            "metodo" => __METHOD__
        ]);

        return response()->json([
            "error" => $e->getMessage(),
            "linea" => $e->getLine(),
            "file" => $e->getFile(),
            "metodo" => __METHOD__
        ], Response::HTTP_BAD_REQUEST);
    }
}