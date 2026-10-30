<?php
declare(strict_types=1);

namespace PulsoCivico\DTO;

/**
 * Representa una sesión formal del Pleno parlamentario (Diputados o Senadores).
 */
class SesionPlenoDTO
{
    public function __construct(
        public readonly int $idSesionOrigen,
        public readonly string $camara,
        public readonly int $legislatura,
        public readonly string $fecha,
        public readonly int $periodoId,
        public readonly string $periodoNombre,
        public readonly ?string $tipo = 'Ordinaria',
        public readonly array $asistenciasResumen = [],
        public array $asistenciasDetalle = []
    ) {}

    public function toArray(): array
    {
        return [
            'id_sesion_origen' => $this->idSesionOrigen,
            'camara' => $this->camara,
            'legislatura' => $this->legislatura,
            'fecha' => $this->fecha,
            'periodo_id' => $this->periodoId,
            'periodo_nombre' => $this->periodoNombre,
            'tipo' => $this->tipo,
            'resumen' => $this->asistenciasResumen,
            'total_diputados' => count($this->asistenciasDetalle),
            'detalle' => $this->asistenciasDetalle,
        ];
    }
}
