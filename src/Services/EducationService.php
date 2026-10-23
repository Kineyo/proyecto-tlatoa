<?php
declare(strict_types=1);

namespace Tlatoa\Services;

/**
 * EducationService: Analizador, clasificador y agregador de nivel de estudios
 * y trayectorias académicas de representantes populares.
 */
class EducationService
{
    public const NIVEL_DOCTORADO = 'doctorado';
    public const NIVEL_MAESTRIA = 'maestria';
    public const NIVEL_LICENCIATURA = 'licenciatura';
    public const NIVEL_TRUNCA = 'trunca';
    public const NIVEL_PREPARATORIA = 'preparatoria';
    public const NIVEL_BASICA = 'basica';
    public const NIVEL_SIN_REGISTRO = 'sin_registro';

    public const NIVELES_CONFIG = [
        self::NIVEL_DOCTORADO => [
            'codigo' => self::NIVEL_DOCTORADO,
            'label' => 'Doctorado',
            'icono' => 'mortarboard-fill',
            'badge_class' => 'bg-purple-subtle text-purple border-purple-subtle',
            'color' => '#6f42c1',
            'peso' => 6,
        ],
        self::NIVEL_MAESTRIA => [
            'codigo' => self::NIVEL_MAESTRIA,
            'label' => 'Maestría / Especialidad',
            'icono' => 'award-fill',
            'badge_class' => 'bg-primary-subtle text-primary border-primary-subtle',
            'color' => '#0d6efd',
            'peso' => 5,
        ],
        self::NIVEL_LICENCIATURA => [
            'codigo' => self::NIVEL_LICENCIATURA,
            'label' => 'Licenciatura / Ingeniería',
            'icono' => 'book-half',
            'badge_class' => 'bg-success-subtle text-success border-success-subtle',
            'color' => '#198754',
            'peso' => 4,
        ],
        self::NIVEL_TRUNCA => [
            'codigo' => self::NIVEL_TRUNCA,
            'label' => 'Carrera Trunca / Pasante',
            'icono' => 'exclamation-triangle-fill',
            'badge_class' => 'bg-warning-subtle text-dark border-warning-subtle',
            'color' => '#ffc107',
            'peso' => 3,
        ],
        self::NIVEL_PREPARATORIA => [
            'codigo' => self::NIVEL_PREPARATORIA,
            'label' => 'Bachillerato / Preparatoria',
            'icono' => 'building',
            'badge_class' => 'bg-info-subtle text-info-emphasis border-info-subtle',
            'color' => '#0dcaf0',
            'peso' => 2,
        ],
        self::NIVEL_BASICA => [
            'codigo' => self::NIVEL_BASICA,
            'label' => 'Educación Básica',
            'icono' => 'journal-bookmark',
            'badge_class' => 'bg-secondary-subtle text-secondary border-secondary-subtle',
            'color' => '#6c757d',
            'peso' => 1,
        ],
        self::NIVEL_SIN_REGISTRO => [
            'codigo' => self::NIVEL_SIN_REGISTRO,
            'label' => 'No reportado / Sin registro',
            'icono' => 'question-circle',
            'badge_class' => 'bg-light text-muted border',
            'color' => '#adb5bd',
            'peso' => 0,
        ],
    ];

    public const TIPO_INSTITUCION_PUBLICA = 'publica';
    public const TIPO_INSTITUCION_PRIVADA = 'privada';
    public const TIPO_INSTITUCION_MIXTA = 'mixta';
    public const TIPO_INSTITUCION_NO_DETERMINADA = 'no_determinada';

    public const INSTITUCIONES_CONFIG = [
        self::TIPO_INSTITUCION_PUBLICA => [
            'codigo' => self::TIPO_INSTITUCION_PUBLICA,
            'label' => 'Universidad Pública',
            'icono' => 'bank',
            'badge_class' => 'bg-primary-subtle text-primary border-primary-subtle',
            'color' => '#0d6efd',
        ],
        self::TIPO_INSTITUCION_PRIVADA => [
            'codigo' => self::TIPO_INSTITUCION_PRIVADA,
            'label' => 'Universidad Privada',
            'icono' => 'mortarboard',
            'badge_class' => 'bg-info-subtle text-info-emphasis border-info-subtle',
            'color' => '#0dcaf0',
        ],
        self::TIPO_INSTITUCION_MIXTA => [
            'codigo' => self::TIPO_INSTITUCION_MIXTA,
            'label' => 'Formación Mixta (Pública y Privada)',
            'icono' => 'shuffle',
            'badge_class' => 'bg-purple-subtle text-purple border-purple-subtle',
            'color' => '#6f42c1',
        ],
        self::TIPO_INSTITUCION_NO_DETERMINADA => [
            'codigo' => self::TIPO_INSTITUCION_NO_DETERMINADA,
            'label' => 'No Especificada en Ficha',
            'icono' => 'question-circle',
            'badge_class' => 'bg-light text-muted border',
            'color' => '#adb5bd',
        ],
    ];

    /**
     * Clasifica el historial educativo completo de un legislador y retorna su grado máximo y detalle.
     *
     * @param array $politico Datos completos del político (con escolaridad_items o escolaridad_detalle)
     * @return array Resumen clasificado del perfil académico
     */
    public function clasificarPolitico(array $politico): array
    {
        $items = [];

        // Recolectar todas las fuentes posibles de formación en la ficha
        if (!empty($politico['escolaridad_items']) && is_array($politico['escolaridad_items'])) {
            foreach ($politico['escolaridad_items'] as $item) {
                $exp = trim($item['experiencia'] ?? '');
                if ($exp !== '') {
                    $items[] = $exp;
                }
            }
        }

        if (!empty($politico['escolaridad_detalle']) && is_array($politico['escolaridad_detalle'])) {
            foreach ($politico['escolaridad_detalle'] as $item) {
                $exp = trim($item['experiencia'] ?? '');
                if ($exp !== '') {
                    $items[] = $exp;
                }
            }
        }

        if (!empty($politico['ultimo_grado_estudios'])) {
            $items[] = trim($politico['ultimo_grado_estudios']);
        }

        if (!empty($politico['preparacion_academica'])) {
            $items[] = trim($politico['preparacion_academica']);
        }

        $items = array_values(array_unique($items));

        if (empty($items)) {
            return [
                'grado_maximo' => self::NIVEL_SIN_REGISTRO,
                'grado_info' => self::NIVELES_CONFIG[self::NIVEL_SIN_REGISTRO],
                'titulo_destacado' => 'Sin registro académico reportado',
                'disciplina_principal' => 'No especificada',
                'tiene_trunca' => false,
                'total_estudios' => 0,
                'estudios_clasificados' => [],
                'institucion_resumen' => [
                    'tipo_predominante' => self::TIPO_INSTITUCION_NO_DETERMINADA,
                    'info' => self::INSTITUCIONES_CONFIG[self::TIPO_INSTITUCION_NO_DETERMINADA],
                    'tiene_publica' => false,
                    'tiene_privada' => false,
                    'instituciones' => [],
                    'alma_mater_destacada' => 'No especificada en ficha',
                ],
            ];
        }

        $estudiosClasificados = [];
        $gradoMaximoKey = self::NIVEL_SIN_REGISTRO;
        $maxPeso = -1;
        $tituloDestacado = $items[0];
        $tieneTrunca = false;
        $disciplinas = [];
        $tienePub = false;
        $tienePriv = false;
        $nombresInstituciones = [];

        foreach ($items as $estudioStr) {
            $clasificacion = $this->analizarTextoEstudio($estudioStr);
            $estudiosClasificados[] = $clasificacion;

            if ($clasificacion['es_trunca']) {
                $tieneTrunca = true;
            }

            if ($clasificacion['disciplina'] !== 'Otra / General') {
                $disciplinas[] = $clasificacion['disciplina'];
            }

            $tipoInst = $clasificacion['institucion_tipo'] ?? self::TIPO_INSTITUCION_NO_DETERMINADA;
            if ($tipoInst === self::TIPO_INSTITUCION_PUBLICA) {
                $tienePub = true;
            } elseif ($tipoInst === self::TIPO_INSTITUCION_PRIVADA) {
                $tienePriv = true;
            }

            if (!empty($clasificacion['institucion_nombre']) && $clasificacion['institucion_nombre'] !== 'No especificada en ficha') {
                $nombresInstituciones[] = $clasificacion['institucion_nombre'];
            }

            $nivelActual = $clasificacion['nivel'];
            $peso = self::NIVELES_CONFIG[$nivelActual]['peso'] ?? 0;

            if ($peso > $maxPeso) {
                $maxPeso = $peso;
                $gradoMaximoKey = $nivelActual;
                $tituloDestacado = $estudioStr;
            }
        }

        $disciplinaPrincipal = !empty($disciplinas) ? $disciplinas[0] : 'General / Diversa';
        $nombresInstituciones = array_values(array_unique($nombresInstituciones));

        if ($tienePub && $tienePriv) {
            $tipoPredominante = self::TIPO_INSTITUCION_MIXTA;
        } elseif ($tienePub) {
            $tipoPredominante = self::TIPO_INSTITUCION_PUBLICA;
        } elseif ($tienePriv) {
            $tipoPredominante = self::TIPO_INSTITUCION_PRIVADA;
        } else {
            $tipoPredominante = self::TIPO_INSTITUCION_NO_DETERMINADA;
        }

        return [
            'grado_maximo' => $gradoMaximoKey,
            'grado_info' => self::NIVELES_CONFIG[$gradoMaximoKey],
            'titulo_destacado' => $tituloDestacado,
            'disciplina_principal' => $disciplinaPrincipal,
            'tiene_trunca' => $tieneTrunca,
            'total_estudios' => count($items),
            'estudios_clasificados' => $estudiosClasificados,
            'institucion_resumen' => [
                'tipo_predominante' => $tipoPredominante,
                'info' => self::INSTITUCIONES_CONFIG[$tipoPredominante],
                'tiene_publica' => $tienePub,
                'tiene_privada' => $tienePriv,
                'instituciones' => $nombresInstituciones,
                'alma_mater_destacada' => !empty($nombresInstituciones) ? $nombresInstituciones[0] : 'No especificada en ficha',
            ],
        ];
    }

    /**
     * Analiza una línea o frase de estudio para extraer nivel, si es trunca y disciplina.
     */
    public function analizarTextoEstudio(string $texto): array
    {
        $norm = mb_strtolower($texto, 'UTF-8');

        // 1. Detección de trunca / pasantía
        $esTrunca = (
            str_contains($norm, 'trunca') ||
            str_contains($norm, 'trunco') ||
            str_contains($norm, 'pasante') ||
            str_contains($norm, 'inconcluso') ||
            str_contains($norm, 'créditos concluidos') ||
            str_contains($norm, 'creditos concluidos') ||
            str_contains($norm, 'estudios no concluidos')
        );

        // 2. Nivel jerárquico
        $nivel = self::NIVEL_SIN_REGISTRO;

        if (str_contains($norm, 'doctorado') || str_contains($norm, 'phd') || str_contains($norm, 'posdoctorado')) {
            $nivel = $esTrunca ? self::NIVEL_TRUNCA : self::NIVEL_DOCTORADO;
        } elseif (
            str_contains($norm, 'maestría') ||
            str_contains($norm, 'maestria') ||
            str_contains($norm, 'master') ||
            str_contains($norm, 'especialidad') ||
            str_contains($norm, 'posgrado')
        ) {
            $nivel = $esTrunca ? self::NIVEL_TRUNCA : self::NIVEL_MAESTRIA;
        } elseif (
            str_contains($norm, 'licenciatura') ||
            str_contains($norm, 'ingeniería') ||
            str_contains($norm, 'ingenieria') ||
            str_contains($norm, 'médico cirujano') ||
            str_contains($norm, 'medico cirujano') ||
            str_contains($norm, 'abogado') ||
            str_contains($norm, 'abogada') ||
            str_contains($norm, 'contador') ||
            str_contains($norm, 'arquitecto') ||
            str_contains($norm, 'profesor') ||
            str_contains($norm, 'carrera técnica superior')
        ) {
            $nivel = $esTrunca ? self::NIVEL_TRUNCA : self::NIVEL_LICENCIATURA;
        } elseif ($esTrunca) {
            $nivel = self::NIVEL_TRUNCA;
        } elseif (
            str_contains($norm, 'bachillerato') ||
            str_contains($norm, 'preparatoria') ||
            str_contains($norm, 'vocacional') ||
            str_contains($norm, 'medio superior') ||
            str_contains($norm, 'técnico') ||
            str_contains($norm, 'tecnico') ||
            str_contains($norm, 'diplomado')
        ) {
            $nivel = self::NIVEL_PREPARATORIA;
        } elseif (
            str_contains($norm, 'secundaria') ||
            str_contains($norm, 'primaria') ||
            str_contains($norm, 'básica') ||
            str_contains($norm, 'basica')
        ) {
            $nivel = self::NIVEL_BASICA;
        } else {
            // Si menciona universidad o facultad sin grado explícito, asumir licenciatura
            if (str_contains($norm, 'universidad') || str_contains($norm, 'facultad') || str_contains($norm, 'instituto tecnológico')) {
                $nivel = $esTrunca ? self::NIVEL_TRUNCA : self::NIVEL_LICENCIATURA;
            } else {
                $nivel = self::NIVEL_PREPARATORIA;
            }
        }

        // 3. Disciplina / Campo de estudio
        $disciplina = $this->detectarDisciplina($norm);

        // 4. Alma máter / Institución educativa
        $institucion = $this->clasificarInstitucion($texto);

        return [
            'texto_original' => $texto,
            'nivel' => $nivel,
            'nivel_info' => self::NIVELES_CONFIG[$nivel] ?? self::NIVELES_CONFIG[self::NIVEL_SIN_REGISTRO],
            'es_trunca' => $esTrunca,
            'disciplina' => $disciplina,
            'institucion_tipo' => $institucion['tipo'],
            'institucion_nombre' => $institucion['nombre'],
            'institucion_extranjera' => $institucion['es_extranjera'],
            'institucion_info' => self::INSTITUCIONES_CONFIG[$institucion['tipo']] ?? self::INSTITUCIONES_CONFIG[self::TIPO_INSTITUCION_NO_DETERMINADA],
        ];
    }

    /**
     * Clasifica si una institución educativa mencionada es pública o privada, y normaliza su nombre.
     *
     * @param string $texto Cadena de estudio o título
     * @return array ['tipo' => 'publica'|'privada'|'no_determinada', 'nombre' => string, 'es_extranjera' => bool]
     */
    public function clasificarInstitucion(string $texto): array
    {
        $norm = mb_strtolower($texto, 'UTF-8');
        // Limpieza de erratas frecuentes en el SIL (ej: 'universdiad')
        $norm = str_replace('universdiad', 'universidad', $norm);

        // Descartar textos sin mención de institución
        if (
            !str_contains($norm, 'por ') &&
            !str_contains($norm, 'en ') &&
            !str_contains($norm, 'unam') &&
            !str_contains($norm, 'ipn') &&
            !str_contains($norm, 'itam') &&
            !str_contains($norm, 'itesm') &&
            !str_contains($norm, 'ibero') &&
            !str_contains($norm, 'anáhuac') &&
            !str_contains($norm, 'anahuac') &&
            !str_contains($norm, 'universidad') &&
            !str_contains($norm, 'instituto') &&
            !str_contains($norm, 'facultad') &&
            !str_contains($norm, 'escuela') &&
            !str_contains($norm, 'colegio') &&
            !str_contains($norm, 'centro de estudios') &&
            !str_contains($norm, 'centro universitario')
        ) {
            return [
                'tipo' => self::TIPO_INSTITUCION_NO_DETERMINADA,
                'nombre' => 'No especificada en ficha',
                'es_extranjera' => false,
            ];
        }

        // ==========================================
        // 1. UNIVERSIDADES E INSTITUCIONES PRIVADAS
        // ==========================================
        if (str_contains($norm, 'itesm') || str_contains($norm, 'tec de monterrey') || str_contains($norm, 'tecnológico de monterrey') || str_contains($norm, 'tecnologico de monterrey') || str_contains($norm, 'estudios superiores de monterrey')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Tec de Monterrey (ITESM)', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'iberoamericana') || str_contains($norm, 'ibero') || str_contains($norm, ' uia')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad Iberoamericana', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'itam') || str_contains($norm, 'autónomo de méxico') || str_contains($norm, 'autonomo de mexico')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'ITAM', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'anáhuac') || str_contains($norm, 'anahuac')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad Anáhuac', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'panamericana') || str_contains($norm, 'ipade')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad Panamericana / IPADE', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'la salle') || str_contains($norm, 'lasalle') || str_contains($norm, 'ulsa')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad La Salle', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'valle de méxico') || str_contains($norm, 'valle de mexico') || str_contains($norm, 'uvm')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad del Valle de México (UVM)', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'unitec') || str_contains($norm, 'tecnológica de méxico') || str_contains($norm, 'tecnologica de mexico')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'UNITEC', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'iteso')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'ITESO', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'udem') || (str_contains($norm, 'universidad de monterrey') && !str_contains($norm, 'nuevo león') && !str_contains($norm, 'nuevo leon'))) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad de Monterrey (UDEM)', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'escuela bancaria y comercial') || str_contains($norm, 'ebc')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Escuela Bancaria y Comercial (EBC)', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'libre de derecho')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Escuela Libre de Derecho', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'tec milenio') || str_contains($norm, 'tecmilenio')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad Tecmilenio', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'de las américas') || str_contains($norm, 'de las americas') || str_contains($norm, 'udlap') || str_contains($norm, 'udla')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'UDLAP / UDLA', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'marista')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad Marista', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'valle de atemajac') || str_contains($norm, 'univa')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'UNIVA (Valle de Atemajac)', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'autónoma de guadalajara') || str_contains($norm, 'autonoma de guadalajara')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad Autónoma de Guadalajara (UAG)', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'autónoma de la laguna') || str_contains($norm, 'autonoma de la laguna')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad Autónoma de la Laguna', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'universidad de león') || str_contains($norm, 'universidad de leon')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad de León', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'intercontinental') || str_contains($norm, ' uic')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad Intercontinental', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'insurgentes')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad Insurgentes', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'humanitas')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad Humanitas', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'cuauhtémoc') || str_contains($norm, 'cuauhtemoc')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad Cuauhtémoc', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'claustro de sor juana') || str_contains($norm, 'del claustro')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad del Claustro de Sor Juana', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'cristóbal colón') || str_contains($norm, 'cristobal colon')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad Cristóbal Colón', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'carlos septién') || str_contains($norm, 'carlos septien')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Escuela de Periodismo Carlos Septién García', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'centro hidalguense') || str_contains($norm, 'cenhies')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Centro Hidalguense de Estudios Superiores', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'universidad de xalapa')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad de Xalapa', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'americana de acapulco')) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad Americana de Acapulco', 'es_extranjera' => false];
        }
        if (
            str_contains($norm, 'universidad de tijuana') ||
            str_contains($norm, 'xochicalco') ||
            str_contains($norm, 'unifront') ||
            str_contains($norm, 'universidad kino') ||
            str_contains($norm, 'vasco de quiroga') ||
            str_contains($norm, 'universidad univer') ||
            str_contains($norm, 'valle del grijalva') ||
            str_contains($norm, 'universidad montrer') ||
            str_contains($norm, 'universidad ceuno') ||
            str_contains($norm, 'universidad cnci') ||
            str_contains($norm, 'universidad de santander') ||
            str_contains($norm, 'leonardo de vinci') ||
            str_contains($norm, 'instituto de ciencias jurídicas') ||
            str_contains($norm, 'instituto de ciencias juridicas') ||
            str_contains($norm, 'instituto universitario') ||
            str_contains($norm, 'centro de estudios universitarios') ||
            str_contains($norm, 'centro universitario de tijuana')
        ) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad Privada Estatal / Regional', 'es_extranjera' => false];
        }
        // Privadas extranjeras
        if (
            str_contains($norm, 'belgrano') ||
            str_contains($norm, 'harvard') ||
            str_contains($norm, 'columbia') ||
            str_contains($norm, 'georgetown') ||
            str_contains($norm, 'stanford') ||
            str_contains($norm, 'george washington') ||
            str_contains($norm, 'nebrija') ||
            str_contains($norm, 'pacific western') ||
            str_contains($norm, 'oberta de catalunya') ||
            str_contains($norm, 'san pablo ceu')
        ) {
            return ['tipo' => self::TIPO_INSTITUCION_PRIVADA, 'nombre' => 'Universidad Privada Extranjera', 'es_extranjera' => true];
        }

        // ==========================================
        // 2. UNIVERSIDADES E INSTITUCIONES PÚBLICAS
        // ==========================================
        if (str_contains($norm, 'unam') || str_contains($norm, 'nacional autónoma de méxico') || str_contains($norm, 'nacional autonoma de mexico')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UNAM', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'ipn') || str_contains($norm, 'politécnico nacional') || str_contains($norm, 'politecnico nacional')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'IPN', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'uam') || str_contains($norm, 'autónoma metropolitana') || str_contains($norm, 'autonoma metropolitana')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UAM', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'universidad de guadalajara') || str_contains($norm, 'udeg') || str_contains($norm, 'centro universitario de la costa') || str_contains($norm, 'cucea') || str_contains($norm, 'cucsh') || str_contains($norm, 'cucs')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'Universidad de Guadalajara (UdeG)', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'uanl') || str_contains($norm, 'autónoma de nuevo león') || str_contains($norm, 'autonoma de nuevo leon')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UANL', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'buap') || str_contains($norm, 'autónoma de puebla') || str_contains($norm, 'autonoma de puebla')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'BUAP', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'uabc') || (str_contains($norm, 'autónoma de baja california') && !str_contains($norm, 'baja california sur')) || (str_contains($norm, 'autonoma de baja california') && !str_contains($norm, 'baja california sur'))) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UABC', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'uas') || str_contains($norm, 'autónoma de sinaloa') || str_contains($norm, 'autonoma de sinaloa')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UAS', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'michoacana') || str_contains($norm, 'san nicolás de hidalgo') || str_contains($norm, 'san nicolas de hidalgo') || str_contains($norm, 'umsnh')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UMSNH', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'universidad veracruzana') || str_contains($norm, 'normal veracruzana')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'Universidad Veracruzana', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'de sonora') || str_contains($norm, 'unison')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'Universidad de Sonora (UNISON)', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'del estado de méxico') || str_contains($norm, 'del estado de mexico') || str_contains($norm, 'uaemex')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UAEMéx', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'de coahuila') || str_contains($norm, 'uadec')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'Universidad Autónoma de Coahuila', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'de chihuahua') || str_contains($norm, 'uach')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UACH', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'de yucatán') || str_contains($norm, 'de yucatan') || str_contains($norm, 'uady')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UADY', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'de tamaulipas') || str_contains($norm, 'uat')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UAT', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'de san luis potosí') || str_contains($norm, 'de san luis potosi') || str_contains($norm, 'uaslp')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UASLP', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'de querétaro') || str_contains($norm, 'de queretaro') || str_contains($norm, 'uaq')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UAQ', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'de zacatecas') || str_contains($norm, 'uaz')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UAZ', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'de aguascalientes') || str_contains($norm, 'uaa')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UAA', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'de chiapas') || str_contains($norm, 'unach')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UNACH', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'de guerrero') || str_contains($norm, 'uagro')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UAGro', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'benito juárez de oaxaca') || str_contains($norm, 'benito juarez de oaxaca') || str_contains($norm, 'uabjo')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UABJO', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'del estado de hidalgo') || str_contains($norm, 'uaeh')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UAEH', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'del estado de morelos') || str_contains($norm, 'uaem')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UAEM (Morelos)', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'de nayarit') || str_contains($norm, 'uan')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UAN', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'de campeche') || str_contains($norm, 'uacam')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UACAM', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'de tlaxcala') || str_contains($norm, 'uatx')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UATx', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'de baja california sur') || str_contains($norm, 'uabcs')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UABCS', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'juárez del estado de durango') || str_contains($norm, 'juarez del estado de durango') || str_contains($norm, 'ujed')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UJED', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'juárez autónoma de tabasco') || str_contains($norm, 'juarez autonoma de tabasco') || str_contains($norm, 'ujat')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'UJAT', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'de colima') || str_contains($norm, 'ucol')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'Universidad de Colima', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'de guanajuato') || str_contains($norm, 'ugto')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'Universidad de Guanajuato', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'de quintana roo') || str_contains($norm, 'uqroo')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'Universidad de Quintana Roo', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'cide') || str_contains($norm, 'colegio de méxico') || str_contains($norm, 'colegio de mexico') || str_contains($norm, 'colmex') || str_contains($norm, 'cinvestav') || str_contains($norm, 'ciesas') || str_contains($norm, 'colef')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'Centro Público de Investigación (CONAHCYT / Colmex / CIDE)', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'instituto tecnológico') || str_contains($norm, 'instituto tecnologico') || str_contains($norm, 'tecnológico regional') || str_contains($norm, 'tecnologico regional') || str_contains($norm, 'tecnm')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'Tecnológico Nacional de México (Público)', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'normal superior') || str_contains($norm, 'escuela normal') || str_contains($norm, 'normalista') || str_contains($norm, 'normal de')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'Escuela Normal Pública', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'colegio militar') || str_contains($norm, 'escuela militar') || str_contains($norm, 'médico militar') || str_contains($norm, 'medico militar') || str_contains($norm, 'fuerza aérea') || str_contains($norm, 'fuerza aerea')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'Colegio / Escuela Militar', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'pedagógica nacional') || str_contains($norm, 'pedagogica nacional') || str_contains($norm, ' upn')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'Universidad Pedagógica Nacional (UPN)', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'unadm') || str_contains($norm, 'abierta y a distancia') || str_contains($norm, 'unad ')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'Universidad Abierta y a Distancia de México (UnADM)', 'es_extranjera' => false];
        }
        if (str_contains($norm, 'universidad politécnica') || str_contains($norm, 'universidad politecnica') || str_contains($norm, 'universidad tecnológica') || str_contains($norm, 'universidad tecnologica')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'Universidad Tecnológica / Politécnica Pública', 'es_extranjera' => false];
        }
        if (
            str_contains($norm, 'complutense') ||
            str_contains($norm, 'salamanca') ||
            str_contains($norm, 'buenos aires') ||
            str_contains($norm, 'barcelona') ||
            str_contains($norm, 'alcalá') ||
            str_contains($norm, 'alcala') ||
            str_contains($norm, 'university of texas')
        ) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'Universidad Pública Extranjera', 'es_extranjera' => true];
        }
        if (str_contains($norm, 'autónoma') || str_contains($norm, 'autonoma')) {
            return ['tipo' => self::TIPO_INSTITUCION_PUBLICA, 'nombre' => 'Universidad Pública Estatal Autónoma', 'es_extranjera' => false];
        }

        return [
            'tipo' => self::TIPO_INSTITUCION_NO_DETERMINADA,
            'nombre' => 'No especificada en ficha',
            'es_extranjera' => false,
        ];
    }

    /**
     * Categoriza el área temática del estudio.
     */
    private function detectarDisciplina(string $norm): string
    {
        if (str_contains($norm, 'derecho') || str_contains($norm, 'leyes') || str_contains($norm, 'jurídic') || str_contains($norm, 'penal')) {
            return 'Derecho y Ciencias Jurídicas';
        }
        if (
            str_contains($norm, 'política') || str_contains($norm, 'politica') ||
            str_contains($norm, 'administración pública') || str_contains($norm, 'administracion publica') ||
            str_contains($norm, 'gobierno') || str_contains($norm, 'políticas públicas') ||
            str_contains($norm, 'relaciones internacionales')
        ) {
            return 'Ciencia Política y Gobierno';
        }
        if (
            str_contains($norm, 'economía') || str_contains($norm, 'economia') ||
            str_contains($norm, 'contadur') || str_contains($norm, 'finanza') ||
            str_contains($norm, 'administración de empresas') || str_contains($norm, 'comercio') ||
            str_contains($norm, 'negocios') || str_contains($norm, 'mercadotecnia')
        ) {
            return 'Economía, Finanzas y Negocios';
        }
        if (
            str_contains($norm, 'médic') || str_contains($norm, 'medicin') ||
            str_contains($norm, 'salud') || str_contains($norm, 'enfermer') ||
            str_contains($norm, 'psicolog') || str_contains($norm, 'odontolog')
        ) {
            return 'Salud y Medicina';
        }
        if (
            str_contains($norm, 'educación') || str_contains($norm, 'educacion') ||
            str_contains($norm, 'pedagog') || str_contains($norm, 'normal') ||
            str_contains($norm, 'docencia') || str_contains($norm, 'enseñanza')
        ) {
            return 'Educación y Pedagogía';
        }
        if (
            str_contains($norm, 'ingenier') || str_contains($norm, 'sistemas') ||
            str_contains($norm, 'computac') || str_contains($norm, 'tecnolog') ||
            str_contains($norm, 'arquitect') || str_contains($norm, 'agronom') ||
            str_contains($norm, 'civil') || str_contains($norm, 'químic') || str_contains($norm, 'fisic')
        ) {
            return 'Ingeniería, Tecnología y Arquitectura';
        }
        if (
            str_contains($norm, 'comunicación') || str_contains($norm, 'comunicacion') ||
            str_contains($norm, 'periodis') || str_contains($norm, 'letras') ||
            str_contains($norm, 'sociolog') || str_contains($norm, 'filosof') ||
            str_contains($norm, 'historia') || str_contains($norm, 'arte')
        ) {
            return 'Ciencias Sociales y Comunicación';
        }

        return 'Otra / General';
    }

    /**
     * Genera métricas analíticas agregadas para un conjunto de políticos.
     *
     * @param array<string, array> $politicos Colección de políticos
     * @return array Estadísticas globales, por cámara, por partido y disciplinas
     */
    public function generarMetricas(array $politicos): array
    {
        $total = count($politicos);
        if ($total === 0) {
            return [
                'total_legisladores' => 0,
                'niveles' => [],
                'resumen_posgrados' => ['total' => 0, 'porcentaje' => 0],
                'resumen_licenciaturas' => ['total' => 0, 'porcentaje' => 0],
                'resumen_truncas' => ['total' => 0, 'porcentaje' => 0],
                'resumen_media_basica' => ['total' => 0, 'porcentaje' => 0],
                'por_camara' => [],
                'por_partido' => [],
                'top_disciplinas' => [],
            ];
        }

        // Inicializar conteos de niveles
        $conteos = [];
        foreach (self::NIVELES_CONFIG as $key => $conf) {
            $conteos[$key] = [
                'codigo' => $key,
                'label' => $conf['label'],
                'icono' => $conf['icono'],
                'badge_class' => $conf['badge_class'],
                'color' => $conf['color'],
                'cantidad' => 0,
                'porcentaje' => 0.0,
            ];
        }

        $camaraConteos = [
            'Diputados' => ['total' => 0, 'posgrado' => 0, 'licenciatura' => 0, 'trunca' => 0, 'media_basica' => 0],
            'Senadores' => ['total' => 0, 'posgrado' => 0, 'licenciatura' => 0, 'trunca' => 0, 'media_basica' => 0],
        ];

        $camaraInstitucion = [
            'Diputados' => ['total' => 0, 'publica' => 0, 'privada' => 0, 'mixta' => 0, 'no_det' => 0],
            'Senadores' => ['total' => 0, 'publica' => 0, 'privada' => 0, 'mixta' => 0, 'no_det' => 0],
        ];

        $partidoConteos = [];
        $partidoInstitucion = [];
        $disciplinaConteos = [];
        $institucionesFrecuencia = [];
        $institucionesPublicasFrecuencia = [];
        $institucionesPrivadasFrecuencia = [];
        $conCarreraTruncaFlag = 0;
        $resumenPublicas = 0;
        $resumenPrivadas = 0;
        $resumenMixtas = 0;
        $resumenNoDet = 0;

        foreach ($politicos as $pol) {
            $analisis = $this->clasificarPolitico($pol);
            $nivel = $analisis['grado_maximo'];
            $camara = $pol['camara'] ?? 'Diputados';
            $partido = trim($pol['partido'] ?? 'Sin Partido');
            $disciplina = $analisis['disciplina_principal'];

            $conteos[$nivel]['cantidad']++;

            if ($analisis['tiene_trunca']) {
                $conCarreraTruncaFlag++;
            }

            // Agregados por cámara (Nivel)
            if (!isset($camaraConteos[$camara])) {
                $camaraConteos[$camara] = ['total' => 0, 'posgrado' => 0, 'licenciatura' => 0, 'trunca' => 0, 'media_basica' => 0];
            }
            $camaraConteos[$camara]['total']++;
            if (in_array($nivel, [self::NIVEL_DOCTORADO, self::NIVEL_MAESTRIA], true)) {
                $camaraConteos[$camara]['posgrado']++;
            } elseif ($nivel === self::NIVEL_LICENCIATURA) {
                $camaraConteos[$camara]['licenciatura']++;
            } elseif ($nivel === self::NIVEL_TRUNCA) {
                $camaraConteos[$camara]['trunca']++;
            } else {
                $camaraConteos[$camara]['media_basica']++;
            }

            // Agregados por cámara (Institución)
            if (!isset($camaraInstitucion[$camara])) {
                $camaraInstitucion[$camara] = ['total' => 0, 'publica' => 0, 'privada' => 0, 'mixta' => 0, 'no_det' => 0];
            }
            $camaraInstitucion[$camara]['total']++;

            // Agregados por partido (Nivel)
            if (!isset($partidoConteos[$partido])) {
                $partidoConteos[$partido] = [
                    'partido' => $partido,
                    'total' => 0,
                    'posgrados' => 0,
                    'licenciaturas' => 0,
                    'truncas' => 0,
                    'media_basica' => 0,
                ];
            }
            $partidoConteos[$partido]['total']++;
            if (in_array($nivel, [self::NIVEL_DOCTORADO, self::NIVEL_MAESTRIA], true)) {
                $partidoConteos[$partido]['posgrados']++;
            } elseif ($nivel === self::NIVEL_LICENCIATURA) {
                $partidoConteos[$partido]['licenciaturas']++;
            } elseif ($nivel === self::NIVEL_TRUNCA) {
                $partidoConteos[$partido]['truncas']++;
            } else {
                $partidoConteos[$partido]['media_basica']++;
            }

            // Agregados por partido (Institución)
            if (!isset($partidoInstitucion[$partido])) {
                $partidoInstitucion[$partido] = [
                    'partido' => $partido,
                    'total' => 0,
                    'publica' => 0,
                    'privada' => 0,
                    'mixta' => 0,
                    'no_det' => 0,
                    'pct_publica' => 0.0,
                    'pct_privada' => 0.0,
                ];
            }
            $partidoInstitucion[$partido]['total']++;

            // Clasificación del legislador por tipo de alma máter
            $instRes = $analisis['institucion_resumen'] ?? [];
            $tipoPred = $instRes['tipo_predominante'] ?? self::TIPO_INSTITUCION_NO_DETERMINADA;

            if ($tipoPred === self::TIPO_INSTITUCION_PUBLICA) {
                $resumenPublicas++;
                $camaraInstitucion[$camara]['publica']++;
                $partidoInstitucion[$partido]['publica']++;
            } elseif ($tipoPred === self::TIPO_INSTITUCION_PRIVADA) {
                $resumenPrivadas++;
                $camaraInstitucion[$camara]['privada']++;
                $partidoInstitucion[$partido]['privada']++;
            } elseif ($tipoPred === self::TIPO_INSTITUCION_MIXTA) {
                $resumenMixtas++;
                $camaraInstitucion[$camara]['mixta']++;
                $partidoInstitucion[$partido]['mixta']++;
            } else {
                $resumenNoDet++;
                $camaraInstitucion[$camara]['no_det']++;
                $partidoInstitucion[$partido]['no_det']++;
            }

            // Frecuencia individual de cada institución mencionada
            foreach ($analisis['estudios_clasificados'] ?? [] as $ec) {
                $nInst = $ec['institucion_nombre'] ?? '';
                $tInst = $ec['institucion_tipo'] ?? '';
                if ($nInst !== '' && $nInst !== 'No especificada en ficha') {
                    $institucionesFrecuencia[$nInst] = ($institucionesFrecuencia[$nInst] ?? 0) + 1;
                    if ($tInst === self::TIPO_INSTITUCION_PUBLICA) {
                        $institucionesPublicasFrecuencia[$nInst] = ($institucionesPublicasFrecuencia[$nInst] ?? 0) + 1;
                    } elseif ($tInst === self::TIPO_INSTITUCION_PRIVADA) {
                        $institucionesPrivadasFrecuencia[$nInst] = ($institucionesPrivadasFrecuencia[$nInst] ?? 0) + 1;
                    }
                }
            }

            // Disciplinas
            if ($disciplina !== 'Otra / General' && $disciplina !== 'No especificada') {
                $disciplinaConteos[$disciplina] = ($disciplinaConteos[$disciplina] ?? 0) + 1;
            }
        }

        // Calcular porcentajes globales
        foreach ($conteos as $k => &$v) {
            $v['porcentaje'] = $total > 0 ? round(($v['cantidad'] / $total) * 100, 1) : 0.0;
        }
        unset($v);

        // Calcular porcentajes partidistas de alma máter
        foreach ($partidoInstitucion as &$pi) {
            $totP = $pi['total'];
            $pi['pct_publica'] = $totP > 0 ? round((($pi['publica'] + $pi['mixta']) / $totP) * 100, 1) : 0.0;
            $pi['pct_privada'] = $totP > 0 ? round((($pi['privada'] + $pi['mixta']) / $totP) * 100, 1) : 0.0;
        }
        unset($pi);

        // Agrupación ejecutiva para cards principales
        $posgradosTotal = $conteos[self::NIVEL_DOCTORADO]['cantidad'] + $conteos[self::NIVEL_MAESTRIA]['cantidad'];
        $licenciaturasTotal = $conteos[self::NIVEL_LICENCIATURA]['cantidad'];
        $truncasTotal = $conteos[self::NIVEL_TRUNCA]['cantidad'];
        $mediaBasicaTotal = $conteos[self::NIVEL_PREPARATORIA]['cantidad'] + $conteos[self::NIVEL_BASICA]['cantidad'];

        // Agrupación de nivel de estudios sin título superior
        $mediaTruncaTotal = $truncasTotal + $mediaBasicaTotal + ($conteos[self::NIVEL_SIN_REGISTRO]['cantidad'] ?? 0);

        arsort($disciplinaConteos);
        $topDisciplinas = array_slice($disciplinaConteos, 0, 5, true);

        // Ordenar rankings de universidades
        arsort($institucionesFrecuencia);
        arsort($institucionesPublicasFrecuencia);
        arsort($institucionesPrivadasFrecuencia);

        // Ordenar partidos por tamaño de bancada
        uasort($partidoConteos, fn($a, $b) => $b['total'] <=> $a['total']);
        uasort($partidoInstitucion, fn($a, $b) => $b['total'] <=> $a['total']);

        return [
            'total_legisladores' => $total,
            'niveles' => $conteos,
            'resumen_posgrados' => [
                'total' => $posgradosTotal,
                'porcentaje' => round(($posgradosTotal / $total) * 100, 1),
            ],
            'resumen_licenciaturas' => [
                'total' => $licenciaturasTotal,
                'porcentaje' => round(($licenciaturasTotal / $total) * 100, 1),
            ],
            'resumen_media_trunca' => [
                'total' => $mediaTruncaTotal,
                'porcentaje' => round(($mediaTruncaTotal / $total) * 100, 1),
                'truncas' => $truncasTotal,
                'preparatoria' => $conteos[self::NIVEL_PREPARATORIA]['cantidad'] ?? 0,
                'basica' => $conteos[self::NIVEL_BASICA]['cantidad'] ?? 0,
                'sin_registro' => $conteos[self::NIVEL_SIN_REGISTRO]['cantidad'] ?? 0,
            ],
            'resumen_truncas' => [
                'total' => $truncasTotal,
                'porcentaje' => round(($truncasTotal / $total) * 100, 1),
                'total_con_mencion_trunca' => $conCarreraTruncaFlag,
            ],
            'resumen_media_basica' => [
                'total' => $mediaBasicaTotal,
                'porcentaje' => round(($mediaBasicaTotal / $total) * 100, 1),
            ],
            // Resumen Alma Máter: Pública vs. Privada
            'resumen_tipo_institucion' => [
                'publicas' => [
                    'cantidad' => $resumenPublicas,
                    'porcentaje' => round(($resumenPublicas / $total) * 100, 1),
                ],
                'privadas' => [
                    'cantidad' => $resumenPrivadas,
                    'porcentaje' => round(($resumenPrivadas / $total) * 100, 1),
                ],
                'mixtas' => [
                    'cantidad' => $resumenMixtas,
                    'porcentaje' => round(($resumenMixtas / $total) * 100, 1),
                ],
                'no_determinadas_o_sin_estudios' => [
                    'cantidad' => $resumenNoDet,
                    'porcentaje' => round(($resumenNoDet / $total) * 100, 1),
                ],
                'con_estudios_en_publica' => [
                    'cantidad' => $resumenPublicas + $resumenMixtas,
                    'porcentaje' => round((($resumenPublicas + $resumenMixtas) / $total) * 100, 1),
                ],
                'con_estudios_en_privada' => [
                    'cantidad' => $resumenPrivadas + $resumenMixtas,
                    'porcentaje' => round((($resumenPrivadas + $resumenMixtas) / $total) * 100, 1),
                ],
            ],
            'por_camara' => $camaraConteos,
            'por_camara_institucion' => $camaraInstitucion,
            'por_partido' => $partidoConteos,
            'por_partido_institucion' => $partidoInstitucion,
            'top_disciplinas' => $topDisciplinas,
            'top_instituciones_publicas' => array_slice($institucionesPublicasFrecuencia, 0, 10, true),
            'top_instituciones_privadas' => array_slice($institucionesPrivadasFrecuencia, 0, 10, true),
            'top_instituciones' => array_slice($institucionesFrecuencia, 0, 15, true),
        ];
    }

    /**
     * Determina la clave de categoría agrupada para filtrado y navegación.
     * Retorna: 'posgrados' | 'licenciatura' | 'media-trunca'
     */
    public function determinarCategoriaAgrupada(string $nivel): string
    {
        return match ($nivel) {
            self::NIVEL_DOCTORADO, self::NIVEL_MAESTRIA => 'posgrados',
            self::NIVEL_LICENCIATURA => 'licenciatura',
            default => 'media-trunca',
        };
    }
}

