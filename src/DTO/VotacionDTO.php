<?php
declare(strict_types=1);

namespace Tlatoa\DTO;

/**
 * Representa una votación nominal electrónica en el Pleno.
 */
class VotacionDTO
{
    public function __construct(
        public readonly int $idVotacionOrigen,
        public readonly string $camara,
        public readonly int $legislatura,
        public readonly string $fecha,
        public readonly string $titulo,
        public readonly ?string $temaClave = null,
        public readonly array $resumenBancadas = [],
        public readonly array $totalesGenerales = [
            'favor' => 0,
            'contra' => 0,
            'abstencion' => 0,
            'ausentes' => 0,
            'total' => 0,
        ],
        public array $votosIndividuales = []
    ) {}

    public function toArray(): array
    {
        return [
            'id_votacion_origen' => $this->idVotacionOrigen,
            'camara' => $this->camara,
            'legislatura' => $this->legislatura,
            'fecha' => $this->fecha,
            'titulo' => $this->titulo,
            'tema_clave' => $this->temaClave,
            'resumen_bancadas' => $this->resumenBancadas,
            'totales' => $this->totalesGenerales,
            'total_votos_registrados' => count($this->votosIndividuales),
            'votos_individuales' => $this->votosIndividuales,
        ];
    }
}
