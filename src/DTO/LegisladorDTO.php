<?php
declare(strict_types=1);

namespace Tlatoa\DTO;

/**
 * Data Transfer Object que representa un legislador federal con todos sus atributos auditables.
 */
class LegisladorDTO
{
    public function __construct(
        public readonly string $referenciaSil,
        public readonly string $nombreCompleto,
        public readonly string $camara,
        public readonly int $legislatura,
        public readonly string $partido,
        public readonly string $principioEleccion,
        public readonly string $entidad,
        public readonly ?string $distrito = null,
        public readonly ?string $tipoCargo = 'Propietario',
        public readonly ?string $suplenteNombre = null,
        public readonly ?string $fechaTomaProtesta = null,
        public readonly ?string $fechaNacimiento = null,
        public readonly ?string $correoInstitucional = null,
        public readonly ?string $telefono = null,
        public readonly ?string $ubicacionOficina = null,
        public readonly ?string $ultimoGradoEstudios = null,
        public readonly ?string $preparacionAcademica = null,
        public readonly ?string $experienciaLegislativaResumen = null,
        public readonly ?string $fotoOficialUrl = null,
        public ?string $fotoLocalPath = null,
        public array $comisiones = [],
        public array $trayectoriaPolitica = [],
        public array $trayectoriaLegislativa = [],
        public array $trayectoriaAdministrativa = [],
        public array $escolaridadDetalle = [],
        public array $licencias = [],
        public array $iniciativas = [],
        public array $puntosAcuerdo = [],
        public array $redesSociales = [],
        public array $metadata = []
    ) {}

    public function toArray(): array
    {
        return [
            'referencia_sil' => $this->referenciaSil,
            'nombre_completo' => $this->nombreCompleto,
            'camara' => $this->camara,
            'legislatura' => $this->legislatura,
            'partido' => $this->partido,
            'principio_eleccion' => $this->principioEleccion,
            'entidad' => $this->entidad,
            'distrito' => $this->distrito,
            'tipo_cargo' => $this->tipoCargo,
            'suplente_nombre' => $this->suplenteNombre,
            'fecha_toma_protesta' => $this->fechaTomaProtesta,
            'fecha_nacimiento' => $this->fechaNacimiento,
            'correo_institucional' => $this->correoInstitucional,
            'telefono' => $this->telefono,
            'ubicacion_oficina' => $this->ubicacionOficina,
            'ultimo_grado_estudios' => $this->ultimoGradoEstudios,
            'preparacion_academica' => $this->preparacionAcademica,
            'experiencia_legislativa_resumen' => $this->experienciaLegislativaResumen,
            'foto_oficial_url' => $this->fotoOficialUrl,
            'foto_local_path' => $this->fotoLocalPath,
            'total_comisiones' => count($this->comisiones),
            'comisiones' => $this->comisiones,
            'trayectoria_politica' => $this->trayectoriaPolitica,
            'trayectoria_legislativa' => $this->trayectoriaLegislativa,
            'trayectoria_administrativa' => $this->trayectoriaAdministrativa,
            'escolaridad_detalle' => $this->escolaridadDetalle,
            'licencias' => $this->licencias,
            'iniciativas' => $this->iniciativas,
            'puntos_acuerdo' => $this->puntosAcuerdo,
            'redes_sociales' => $this->redesSociales,
            'metadata' => $this->metadata,
        ];
    }

    public static function fromArray(array $d): self
    {
        return new self(
            referenciaSil: (string)($d['referencia_sil'] ?? ''),
            nombreCompleto: (string)($d['nombre_completo'] ?? ''),
            camara: (string)($d['camara'] ?? ''),
            legislatura: (int)($d['legislatura'] ?? 66),
            partido: (string)($d['partido'] ?? ''),
            principioEleccion: (string)($d['principio_eleccion'] ?? ''),
            entidad: (string)($d['entidad'] ?? ''),
            distrito: $d['distrito'] ?? null,
            tipoCargo: $d['tipo_cargo'] ?? 'Propietario',
            suplenteNombre: $d['suplente_nombre'] ?? null,
            fechaTomaProtesta: $d['fecha_toma_protesta'] ?? null,
            fechaNacimiento: $d['fecha_nacimiento'] ?? null,
            correoInstitucional: $d['correo_institucional'] ?? null,
            telefono: $d['telefono'] ?? null,
            ubicacionOficina: $d['ubicacion_oficina'] ?? null,
            ultimoGradoEstudios: $d['ultimo_grado_estudios'] ?? null,
            preparacionAcademica: $d['preparacion_academica'] ?? null,
            experienciaLegislativaResumen: $d['experiencia_legislativa_resumen'] ?? null,
            fotoOficialUrl: $d['foto_oficial_url'] ?? null,
            fotoLocalPath: $d['foto_local_path'] ?? null,
            comisiones: $d['comisiones'] ?? [],
            trayectoriaPolitica: $d['trayectoria_politica'] ?? [],
            trayectoriaLegislativa: $d['trayectoria_legislativa'] ?? [],
            trayectoriaAdministrativa: $d['trayectoria_administrativa'] ?? [],
            escolaridadDetalle: $d['escolaridad_detalle'] ?? [],
            licencias: $d['licencias'] ?? [],
            iniciativas: $d['iniciativas'] ?? [],
            puntosAcuerdo: $d['puntos_acuerdo'] ?? [],
            redesSociales: $d['redes_sociales'] ?? [],
            metadata: $d['metadata'] ?? []
        );
    }
}
