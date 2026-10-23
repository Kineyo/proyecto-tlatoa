<?php
declare(strict_types=1);

use Tlatoa\Core\ViewHelper;

/**
 * @var array $escolaridadStats
 */

$stats = $escolaridadStats ?? [];
$total = $stats['total_legisladores'] ?? 0;
$niveles = $stats['niveles'] ?? [];
$posgrados = $stats['resumen_posgrados'] ?? ['total' => 0, 'porcentaje' => 0];
$licenciaturas = $stats['resumen_licenciaturas'] ?? ['total' => 0, 'porcentaje' => 0];
$truncas = $stats['resumen_truncas'] ?? ['total' => 0, 'porcentaje' => 0];
$mediaBasica = $stats['resumen_media_basica'] ?? ['total' => 0, 'porcentaje' => 0];
$mediaTrunca = $stats['resumen_media_trunca'] ?? [
    'total' => ($truncas['total'] + $mediaBasica['total']),
    'porcentaje' => $total > 0 ? round((($truncas['total'] + $mediaBasica['total']) / $total) * 100, 1) : 0,
];
$porCamara = $stats['por_camara'] ?? [];
$porPartido = $stats['por_partido'] ?? [];
$topDisciplinas = $stats['top_disciplinas'] ?? [];
$resumenInstitucion = $stats['resumen_tipo_institucion'] ?? [];
$topInstPub = $stats['top_instituciones_publicas'] ?? [];
$topInstPriv = $stats['top_instituciones_privadas'] ?? [];
$porCamaraInst = $stats['por_camara_institucion'] ?? [];
$porPartidoInst = $stats['por_partido_institucion'] ?? [];

$urlPosgrados = \Tlatoa\Core\Router::url('/estudios/posgrados');
$urlLicenciaturas = \Tlatoa\Core\Router::url('/estudios/licenciatura');
$urlMediaTrunca = \Tlatoa\Core\Router::url('/estudios/media-trunca');
$urlTodosEstudios = \Tlatoa\Core\Router::url('/estudios');
?>

<div class="card-civic p-4 mb-5 border shadow-sm" id="seccion-educacion">
    <!-- Header del Card -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom">
        <div>
            <div class="d-inline-flex align-items-center gap-2 px-2 py-1 rounded-pill bg-primary-subtle text-primary small fw-semibold mb-2">
                <i class="bi bi-mortarboard-fill"></i> Auditoría Académica y Curricular
            </div>
            <h2 class="h4 fw-bold text-dark mb-1">
                Radiografía Educativa del Congreso Federal
            </h2>
            <p class="text-secondary small mb-0">
                Nivel máximo de estudios cursados por los legisladores de la LXVI Legislatura (Fuente oficial: SIL SEGOB).
            </p>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge bg-light text-secondary border px-3 py-2 small">
                <strong class="text-dark"><?= (int)$total ?></strong> legisladores auditados
            </span>
            <a href="<?= $urlTodosEstudios ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-medium">
                Ver directorio completo &rarr;
            </a>
        </div>
    </div>

    <!-- 3 Métricas Principales (KPIs Unificados y Clickeables) -->
    <div class="row g-3 mb-4 text-center">
        <!-- 1. Posgrados Concluidos -->
        <div class="col-12 col-md-4">
            <a href="<?= $urlPosgrados ?>" class="text-decoration-none d-block h-100 card-kpi-anchor">
                <div class="p-3 p-lg-4 rounded-4 bg-light border h-100 d-flex flex-column justify-content-between card-kpi-interactive">
                    <div>
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary mb-2" style="width: 44px; height: 44px;">
                            <i class="bi bi-mortarboard fs-5"></i>
                        </div>
                        <div class="display-6 fw-bold text-primary mb-0"><?= $posgrados['porcentaje'] ?>%</div>
                        <div class="fw-semibold text-dark small mt-1">Posgrados Concluidos</div>
                        <div class="text-muted mt-1" style="font-size: 0.76rem;">
                            <strong><?= $posgrados['total'] ?></strong> representantes &bull; <?= $niveles['doctorado']['cantidad'] ?? 0 ?> Dr. / <?= $niveles['maestria']['cantidad'] ?? 0 ?> Mtr.
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-top text-primary small fw-semibold d-flex align-items-center justify-content-center gap-1" style="font-size: 0.78rem;">
                        <span>Ver legisladores</span>
                        <i class="bi bi-arrow-right"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- 2. Licenciatura / Ingeniería Concluida -->
        <div class="col-12 col-md-4">
            <a href="<?= $urlLicenciaturas ?>" class="text-decoration-none d-block h-100 card-kpi-anchor">
                <div class="p-3 p-lg-4 rounded-4 bg-light border h-100 d-flex flex-column justify-content-between card-kpi-interactive">
                    <div>
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success mb-2" style="width: 44px; height: 44px;">
                            <i class="bi bi-award fs-5"></i>
                        </div>
                        <div class="display-6 fw-bold text-success mb-0"><?= $licenciaturas['porcentaje'] ?>%</div>
                        <div class="fw-semibold text-dark small mt-1">Licenciatura / Ingeniería</div>
                        <div class="text-muted mt-1" style="font-size: 0.76rem;">
                            <strong><?= $licenciaturas['total'] ?></strong> representantes titulados
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-top text-success small fw-semibold d-flex align-items-center justify-content-center gap-1" style="font-size: 0.78rem;">
                        <span>Ver legisladores</span>
                        <i class="bi bi-arrow-right"></i>
                    </div>
                </div>
            </a>
        </div>

        <!-- 3. Educación Media Superior y Carrera Trunca (Unificados por certificación terminal) -->
        <div class="col-12 col-md-4">
            <a href="<?= $urlMediaTrunca ?>" class="text-decoration-none d-block h-100 card-kpi-anchor">
                <div class="p-3 p-lg-4 rounded-4 bg-light border h-100 d-flex flex-column justify-content-between card-kpi-interactive">
                    <div>
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-warning-subtle text-dark mb-2" style="width: 44px; height: 44px;">
                            <i class="bi bi-layers-half fs-5 text-warning-emphasis"></i>
                        </div>
                        <div class="display-6 fw-bold text-dark mb-0"><?= $mediaTrunca['porcentaje'] ?>%</div>
                        <div class="fw-semibold text-dark small mt-1">Media Superior / Trunca</div>
                        <div class="text-muted mt-1" style="font-size: 0.76rem;">
                            <strong><?= $mediaTrunca['total'] ?></strong> sin titulación superior &bull; <?= $truncas['total'] ?> trunca/pasante &bull; <?= $mediaBasica['total'] ?> básica
                        </div>
                    </div>
                    <div class="mt-3 pt-2 border-top text-secondary small fw-semibold d-flex align-items-center justify-content-center gap-1" style="font-size: 0.78rem;">
                        <span>Ver legisladores</span>
                        <i class="bi bi-arrow-right"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Barra de Distribución Porcentual Segmentada -->
    <div class="mb-4">
        <div class="d-flex justify-content-between align-items-center small text-secondary mb-2">
            <span class="fw-medium text-dark"><i class="bi bi-bar-chart-steps me-1"></i> Composición Porcentual Completa</span>
            <span style="font-size: 0.75rem;">100% de la muestra oficial</span>
        </div>
        <div class="progress rounded-pill shadow-none" style="height: 16px; background-color: #e9ecef;">
            <?php foreach ($niveles as $code => $n): 
                if ($n['cantidad'] === 0) continue;
            ?>
            <div class="progress-bar" 
                 role="progressbar" 
                 style="width: <?= $n['porcentaje'] ?>%; background-color: <?= $n['color'] ?>;" 
                 title="<?= ViewHelper::e($n['label']) ?>: <?= $n['cantidad'] ?> (<?= $n['porcentaje'] ?>%)"
                 data-bs-toggle="tooltip">
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Leyenda de colores -->
        <div class="d-flex flex-wrap gap-3 mt-3 justify-content-center" style="font-size: 0.76rem;">
            <?php foreach ($niveles as $code => $n): ?>
            <div class="d-flex align-items-center gap-1">
                <span class="d-inline-block rounded-circle" style="width: 10px; height: 10px; background-color: <?= $n['color'] ?>;"></span>
                <span class="text-dark"><?= ViewHelper::e($n['label']) ?></span>
                <span class="text-muted">(<?= $n['cantidad'] ?>)</span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Sección de Alma Máter: Escuelas Públicas vs. Universidades Privadas -->
    <?php if (!empty($resumenInstitucion)): 
        $conPub = $resumenInstitucion['con_estudios_en_publica'] ?? ['cantidad' => 0, 'porcentaje' => 0];
        $conPriv = $resumenInstitucion['con_estudios_en_privada'] ?? ['cantidad' => 0, 'porcentaje' => 0];
        $soloPub = $resumenInstitucion['publicas'] ?? ['cantidad' => 0, 'porcentaje' => 0];
        $soloPriv = $resumenInstitucion['privadas'] ?? ['cantidad' => 0, 'porcentaje' => 0];
        $mixtas = $resumenInstitucion['mixtas'] ?? ['cantidad' => 0, 'porcentaje' => 0];
        $noDet = $resumenInstitucion['no_determinadas_o_sin_estudios'] ?? ['cantidad' => 0, 'porcentaje' => 0];
    ?>
    <div class="p-3 p-md-4 rounded-4 bg-light border mb-4">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
            <div>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small mb-1">
                    <i class="bi bi-bank me-1"></i> Alma Máter de los Representantes
                </span>
                <h3 class="h5 fw-bold text-dark mb-1">
                    ¿Dónde estudiaron? Universidades Públicas vs. Privadas
                </h3>
                <p class="text-secondary small mb-0">
                    Origen institucional de los grados y posgrados de los <strong><?= (int)$total ?></strong> legisladores auditados en la LXVI Legislatura.
                </p>
            </div>
            <a href="<?= $urlTodosEstudios ?>" class="badge bg-white text-secondary border px-3 py-2 small text-decoration-none hover-primary">
                <i class="bi bi-mortarboard-fill text-primary me-1"></i> Ver directorio &rarr;
            </a>
        </div>

        <!-- Tarjetas comparativas Públicas vs Privadas -->
        <div class="row g-3 mb-3">
            <!-- Pública -->
            <div class="col-md-6">
                <div class="p-3 bg-white rounded-3 border h-100 shadow-2xs position-relative overflow-hidden">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="p-2 rounded-3 bg-primary-subtle text-primary">
                                <i class="bi bi-bank fs-5"></i>
                            </span>
                            <div>
                                <span class="fw-bold text-dark d-block">Universidad Pública</span>
                                <span class="text-muted" style="font-size: 0.72rem;">UNAM, UANL, UAT, UdeG, Escuelas Normales, IPN, UAM...</span>
                            </div>
                        </div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
                            Mayoritaria
                        </span>
                    </div>

                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <div class="display-6 fw-bold text-primary mb-0"><?= $conPub['porcentaje'] ?>%</div>
                        <span class="text-muted small">tienen paso por pública (<strong><?= $conPub['cantidad'] ?></strong>)</span>
                    </div>

                    <div class="p-2 rounded-2 bg-light border small text-secondary" style="font-size: 0.76rem;">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Exclusivamente pública:</span>
                            <strong class="text-dark"><?= $soloPub['cantidad'] ?> (<?= $soloPub['porcentaje'] ?>%)</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Top Alma Máter:</span>
                            <span class="text-dark fw-medium text-truncate ms-2" title="UNAM (71), UANL (23), UAT (22), UdeG (19), Escuelas Normales (17)">
                                UNAM (71), UANL (23), UAT (22), UdeG (19)
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Privada -->
            <div class="col-md-6">
                <div class="p-3 bg-white rounded-3 border h-100 shadow-2xs position-relative overflow-hidden">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="p-2 rounded-3 bg-info-subtle text-info-emphasis">
                                <i class="bi bi-mortarboard fs-5"></i>
                            </span>
                            <div>
                                <span class="fw-bold text-dark d-block">Universidad Privada</span>
                                <span class="text-muted" style="font-size: 0.72rem;">Tec de Monterrey, Ibero, UVM, UP/IPADE, ITAM, Anáhuac...</span>
                            </div>
                        </div>
                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle rounded-pill">
                            Privada
                        </span>
                    </div>

                    <div class="d-flex align-items-baseline gap-2 mb-2">
                        <div class="display-6 fw-bold text-info-emphasis mb-0"><?= $conPriv['porcentaje'] ?>%</div>
                        <span class="text-muted small">tienen paso por privada (<strong><?= $conPriv['cantidad'] ?></strong>)</span>
                    </div>

                    <div class="p-2 rounded-2 bg-light border small text-secondary" style="font-size: 0.76rem;">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Exclusivamente privada:</span>
                            <strong class="text-dark"><?= $soloPriv['cantidad'] ?> (<?= $soloPriv['porcentaje'] ?>%)</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Top Alma Máter:</span>
                            <span class="text-dark fw-medium text-truncate ms-2" title="Tec de Monterrey (54), Ibero (26), UVM (26), UP/IPADE (21), ITAM (19)">
                                ITESM (54), Ibero (26), UVM (26), UP (21)
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Barra segmentada de distribución institucional -->
        <div class="bg-white p-3 rounded-3 border mb-3">
            <div class="d-flex justify-content-between align-items-center small text-secondary mb-2" style="font-size: 0.76rem;">
                <span class="fw-semibold text-dark"><i class="bi bi-pie-chart-fill text-primary me-1"></i> Balance de Formación en el Congreso</span>
                <span><?= $total ?> representantes</span>
            </div>
            <div class="progress rounded-pill shadow-none" style="height: 14px; background-color: #e9ecef;">
                <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $soloPub['porcentaje'] ?>%;" title="Solo Pública: <?= $soloPub['cantidad'] ?> (<?= $soloPub['porcentaje'] ?>%)" data-bs-toggle="tooltip"></div>
                <div class="progress-bar bg-info" role="progressbar" style="width: <?= $soloPriv['porcentaje'] ?>%;" title="Solo Privada: <?= $soloPriv['cantidad'] ?> (<?= $soloPriv['porcentaje'] ?>%)" data-bs-toggle="tooltip"></div>
                <div class="progress-bar" role="progressbar" style="width: <?= $mixtas['porcentaje'] ?>%; background-color: #6f42c1;" title="Formación Mixta: <?= $mixtas['cantidad'] ?> (<?= $mixtas['porcentaje'] ?>%)" data-bs-toggle="tooltip"></div>
                <div class="progress-bar bg-secondary-subtle" role="progressbar" style="width: <?= $noDet['porcentaje'] ?>%;" title="Sin especificar o básica: <?= $noDet['cantidad'] ?> (<?= $noDet['porcentaje'] ?>%)" data-bs-toggle="tooltip"></div>
            </div>
            <div class="d-flex flex-wrap gap-3 mt-2 justify-content-between" style="font-size: 0.72rem;">
                <div class="d-flex align-items-center gap-1">
                    <span class="d-inline-block rounded-circle bg-primary" style="width: 8px; height: 8px;"></span>
                    <span class="text-dark fw-medium">Solo Pública:</span> <span class="text-muted"><?= $soloPub['cantidad'] ?> (<?= $soloPub['porcentaje'] ?>%)</span>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <span class="d-inline-block rounded-circle bg-info" style="width: 8px; height: 8px;"></span>
                    <span class="text-dark fw-medium">Solo Privada:</span> <span class="text-muted"><?= $soloPriv['cantidad'] ?> (<?= $soloPriv['porcentaje'] ?>%)</span>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <span class="d-inline-block rounded-circle" style="width: 8px; height: 8px; background-color: #6f42c1;"></span>
                    <span class="text-dark fw-medium">Mixta (ambas):</span> <span class="text-muted"><?= $mixtas['cantidad'] ?> (<?= $mixtas['porcentaje'] ?>%)</span>
                </div>
                <div class="d-flex align-items-center gap-1">
                    <span class="d-inline-block rounded-circle bg-secondary" style="width: 8px; height: 8px;"></span>
                    <span class="text-muted">Sin especificar: <?= $noDet['cantidad'] ?> (<?= $noDet['porcentaje'] ?>%)</span>
                </div>
            </div>
        </div>

        <!-- Pill informativo de Formación Mixta -->
        <div class="d-flex align-items-center gap-2 p-2 px-3 bg-white rounded-3 border" style="font-size: 0.78rem;">
            <i class="bi bi-shuffle text-purple fs-6"></i>
            <span class="text-secondary">
                <strong class="text-dark"><?= $mixtas['cantidad'] ?> legisladores (<?= $mixtas['porcentaje'] ?>%)</strong> cuentan con <strong>formación mixta</strong>: cursaron licenciatura en universidad pública y maestría/posgrado en universidad privada, o viceversa.
            </span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pestañas / Bloques Desplegables de Análisis Profundo -->
    <div class="accordion accordion-flush rounded-3 border" id="accordionDetalleEducacion">
        
        <!-- Bloque 1: Top Disciplinas y Carreras Más Estudiadas -->
        <div class="accordion-item rounded-top-3">
            <h3 class="accordion-header" id="headingDisciplinas">
                <button class="accordion-button collapsed fw-semibold text-dark small" type="button" data-bs-toggle="collapse" data-bs-target="#collapseDisciplinas" aria-expanded="false" aria-controls="collapseDisciplinas">
                    <i class="bi bi-journal-text text-primary me-2"></i>
                    ¿Qué carreras y disciplinas estudiaron más los legisladores?
                </button>
            </h3>
            <div id="collapseDisciplinas" class="accordion-collapse collapse" aria-labelledby="headingDisciplinas" data-bs-parent="#accordionDetalleEducacion">
                <div class="accordion-body pt-2 pb-3">
                    <p class="text-muted small mb-3">
                        Agrupación de las áreas profesionales más recurrentes en la Cámara de Diputados y el Senado:
                    </p>
                    <div class="row g-2">
                        <?php 
                        $rank = 1;
                        foreach ($topDisciplinas as $discNombre => $cant): 
                            $pct = $total > 0 ? round(($cant / $total) * 100, 1) : 0;
                        ?>
                        <div class="col-md-6">
                            <div class="p-2 px-3 bg-light rounded-3 d-flex align-items-center justify-content-between border">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary-subtle text-dark border rounded-circle" style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.72rem;">
                                        #<?= $rank++ ?>
                                    </span>
                                    <span class="fw-medium text-dark small"><?= ViewHelper::e($discNombre) ?></span>
                                </div>
                                <div class="text-end">
                                    <span class="fw-bold text-primary small"><?= $cant ?></span>
                                    <span class="text-muted" style="font-size: 0.72rem;">(<?= $pct ?>%)</span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bloque 2: Desglose por Bancada y Partido Político -->
        <div class="accordion-item">
            <h3 class="accordion-header" id="headingPartidos">
                <button class="accordion-button collapsed fw-semibold text-dark small" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePartidos" aria-expanded="false" aria-controls="collapsePartidos">
                    <i class="bi bi-flag-fill text-danger me-2"></i>
                    Desglose de Nivel de Estudios por Partido Político
                </button>
            </h3>
            <div id="collapsePartidos" class="accordion-collapse collapse" aria-labelledby="headingPartidos" data-bs-parent="#accordionDetalleEducacion">
                <div class="accordion-body pt-2 pb-3">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Partido</th>
                                    <th class="text-center">Total Auditados</th>
                                    <th class="text-center">Posgrado</th>
                                    <th class="text-center">Licenciatura</th>
                                    <th class="text-center">Trunca / Pasante</th>
                                    <th class="text-center">Prepa / Básica</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($porPartido as $partidoNombre => $pStats): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?= ViewHelper::renderPartyBadge($partidoNombre) ?>
                                            <span class="fw-medium text-dark"><?= ViewHelper::e($partidoNombre) ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center fw-bold"><?= $pStats['total'] ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            <?= $pStats['posgrados'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <?= $pStats['licenciaturas'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($pStats['truncas'] > 0): ?>
                                        <span class="badge bg-warning-subtle text-dark border border-warning-subtle">
                                            <?= $pStats['truncas'] ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($pStats['media_basica'] > 0): ?>
                                        <span class="badge bg-secondary-subtle text-secondary border">
                                            <?= $pStats['media_basica'] ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bloque 3: Comparativa Diputados vs Senadores -->
        <div class="accordion-item rounded-bottom-3">
            <h3 class="accordion-header" id="headingCamaras">
                <button class="accordion-button collapsed fw-semibold text-dark small" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCamaras" aria-expanded="false" aria-controls="collapseCamaras">
                    <i class="bi bi-columns-gap text-success me-2"></i>
                    Comparativa entre Cámara de Diputados y Senado
                </button>
            </h3>
            <div id="collapseCamaras" class="accordion-collapse collapse" aria-labelledby="headingCamaras" data-bs-parent="#accordionDetalleEducacion">
                <div class="accordion-body pt-2 pb-3">
                    <div class="row g-3 text-center">
                        <?php foreach (['Diputados', 'Senadores'] as $cName): 
                            $cData = $porCamara[$cName] ?? ['total' => 0, 'posgrado' => 0, 'licenciatura' => 0, 'trunca' => 0, 'media_basica' => 0];
                            $cTot = max(1, $cData['total']);
                            $pctPos = round(($cData['posgrado'] / $cTot) * 100, 1);
                            $pctLic = round(($cData['licenciatura'] / $cTot) * 100, 1);
                            $pctTru = round(($cData['trunca'] / $cTot) * 100, 1);
                        ?>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border text-start">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <strong class="text-dark">Cámara de <?= $cName ?></strong>
                                    <span class="badge bg-white text-secondary border"><?= $cData['total'] ?> legisladores</span>
                                </div>
                                <div class="d-flex flex-column gap-2 small">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Posgrado (Dr. / Mtr.):</span>
                                        <strong class="text-primary"><?= $cData['posgrado'] ?> (<?= $pctPos ?>%)</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Licenciatura / Ing.:</span>
                                        <strong class="text-success"><?= $cData['licenciatura'] ?> (<?= $pctLic ?>%)</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Carrera Trunca / Pasante:</span>
                                        <strong class="text-dark"><?= $cData['trunca'] ?> (<?= $pctTru ?>%)</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="text-muted">Preparatoria o Básica:</span>
                                        <strong class="text-secondary"><?= $cData['media_basica'] ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bloque 4: Ranking de Universidades y Alma Máter por Partido -->
        <div class="accordion-item rounded-bottom-3">
            <h3 class="accordion-header" id="headingInstituciones">
                <button class="accordion-button collapsed fw-semibold text-dark small" type="button" data-bs-toggle="collapse" data-bs-target="#collapseInstituciones" aria-expanded="false" aria-controls="collapseInstituciones">
                    <i class="bi bi-bank text-primary me-2"></i>
                    Ranking de Universidades y Alma Máter por Partido
                </button>
            </h3>
            <div id="collapseInstituciones" class="accordion-collapse collapse" aria-labelledby="headingInstituciones" data-bs-parent="#accordionDetalleEducacion">
                <div class="accordion-body pt-2 pb-3">
                    <!-- Top Universidades: Públicas vs Privadas -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                        <i class="bi bi-bank me-1"></i> Top Públicas
                                    </span>
                                    <span class="text-muted small">Más egresados en el Congreso</span>
                                </div>
                                <div class="list-group list-group-flush small">
                                    <?php 
                                    $rankPub = 1;
                                    foreach (array_slice($topInstPub, 0, 6, true) as $instNombre => $cant): 
                                    ?>
                                    <div class="list-group-item bg-transparent px-0 py-1 d-flex justify-content-between align-items-center">
                                        <span class="text-truncate pe-2">
                                            <strong class="text-secondary me-1">#<?= $rankPub++ ?></strong>
                                            <?= ViewHelper::e($instNombre) ?>
                                        </span>
                                        <span class="badge bg-primary-subtle text-primary rounded-pill"><?= $cant ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                                        <i class="bi bi-mortarboard me-1"></i> Top Privadas
                                    </span>
                                    <span class="text-muted small">Más egresados en el Congreso</span>
                                </div>
                                <div class="list-group list-group-flush small">
                                    <?php 
                                    $rankPriv = 1;
                                    foreach (array_slice($topInstPriv, 0, 6, true) as $instNombre => $cant): 
                                    ?>
                                    <div class="list-group-item bg-transparent px-0 py-1 d-flex justify-content-between align-items-center">
                                        <span class="text-truncate pe-2">
                                            <strong class="text-secondary me-1">#<?= $rankPriv++ ?></strong>
                                            <?= ViewHelper::e($instNombre) ?>
                                        </span>
                                        <span class="badge bg-info-subtle text-info-emphasis rounded-pill"><?= $cant ?></span>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla por Partido Político -->
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>Partido</th>
                                    <th class="text-center">Total</th>
                                    <th class="text-center">Pública</th>
                                    <th class="text-center">Privada</th>
                                    <th class="text-center">Mixta</th>
                                    <th class="text-center">% Con Pública</th>
                                    <th class="text-center">% Con Privada</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($porPartidoInst as $pName => $pData): 
                                    if ($pData['total'] === 0) continue;
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?= ViewHelper::renderPartyBadge($pName) ?>
                                            <span class="fw-medium text-dark"><?= ViewHelper::e($pName) ?></span>
                                        </div>
                                    </td>
                                    <td class="text-center fw-bold"><?= $pData['total'] ?></td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                                            <?= $pData['publica'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info-subtle text-info-emphasis border border-info-subtle">
                                            <?= $pData['privada'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-purple-subtle text-purple border-purple-subtle">
                                            <?= $pData['mixta'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold text-primary">
                                        <?= $pData['pct_publica'] ?>%
                                    </td>
                                    <td class="text-center fw-bold text-info-emphasis">
                                        <?= $pData['pct_privada'] ?>%
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bloque 5: Comparativa de Escolaridad por Entidad Federativa (¿Dónde hay más doctores y posgrados?) -->
        <?php if (!empty($estados)): 
            // Calcular ranking de estados ordenados por cantidad de doctores y posgrados
            $estadosRanking = [];
            foreach ($estados as $eSlug => $eData) {
                $eEsc = $eData['escolaridad'] ?? [];
                $eTot = $eData['total_representantes'] ?? 0;
                if ($eTot === 0) continue;
                $estadosRanking[] = [
                    'nombre' => $eData['nombre'],
                    'slug' => $eSlug,
                    'abbr' => $eData['abbr'] ?? 'MEX',
                    'total' => $eTot,
                    'doctores' => $eEsc['doctores'] ?? 0,
                    'maestrias' => $eEsc['maestrias'] ?? 0,
                    'posgrados' => $eEsc['posgrados'] ?? 0,
                    'pct_posgrados' => $eEsc['pct_posgrados'] ?? 0.0,
                    'licenciaturas' => $eEsc['licenciaturas'] ?? 0,
                    'media_trunca' => $eEsc['media_trunca'] ?? 0,
                ];
            }
            // Ordenar descendentemente por doctores y posgrados
            usort($estadosRanking, function ($a, $b) {
                if ($b['doctores'] !== $a['doctores']) {
                    return $b['doctores'] <=> $a['doctores'];
                }
                return $b['posgrados'] <=> $a['posgrados'];
            });
        ?>
        <div class="accordion-item rounded-bottom-3">
            <h3 class="accordion-header" id="headingEstadosEducacion">
                <button class="accordion-button collapsed fw-semibold text-dark small" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEstadosEducacion" aria-expanded="false" aria-controls="collapseEstadosEducacion">
                    <i class="bi bi-geo-alt-fill text-danger me-2"></i>
                    ¿Qué estados tienen más legisladores con Doctorado y Posgrado?
                </button>
            </h3>
            <div id="collapseEstadosEducacion" class="accordion-collapse collapse" aria-labelledby="headingEstadosEducacion" data-bs-parent="#accordionDetalleEducacion">
                <div class="accordion-body pt-2 pb-3">
                    <p class="text-muted small mb-3">
                        Comparativa de nivel de estudios por entidad federativa: cuántos doctores, maestros y licenciados representan a cada estado en el Congreso de la Unión.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0" style="font-size: 0.8rem;">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Entidad Federativa</th>
                                    <th class="text-center">Total Rep.</th>
                                    <th class="text-center">
                                        <span class="text-purple fw-bold"><i class="bi bi-mortarboard-fill me-1"></i>Doctores</span>
                                    </th>
                                    <th class="text-center">Maestrías</th>
                                    <th class="text-center">% Posgrado</th>
                                    <th class="text-center">Licenciatura</th>
                                    <th class="text-center">Media / Trunca</th>
                                    <th class="text-end">Ficha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rankE = 1;
                                foreach ($estadosRanking as $estItem): 
                                    $urlEstado = \Tlatoa\Core\Router::url('/estados/' . $estItem['slug']);
                                ?>
                                <tr>
                                    <td class="text-muted small fw-medium"><?= $rankE++ ?></td>
                                    <td>
                                        <a href="<?= $urlEstado ?>" class="text-decoration-none text-dark fw-bold hover-primary">
                                            <?= ViewHelper::e($estItem['nombre']) ?>
                                        </a>
                                    </td>
                                    <td class="text-center fw-medium"><?= $estItem['total'] ?></td>
                                    <td class="text-center">
                                        <?php if ($estItem['doctores'] > 0): ?>
                                        <span class="badge bg-purple-subtle text-purple border-purple-subtle px-2 py-1">
                                            <?= $estItem['doctores'] ?> Dr.
                                        </span>
                                        <?php else: ?>
                                        <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary border-primary-subtle px-2 py-1">
                                            <?= $estItem['maestrias'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold text-primary">
                                        <?= $estItem['pct_posgrados'] ?>%
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success-subtle text-success border-success-subtle px-2 py-1">
                                            <?= $estItem['licenciaturas'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center text-muted">
                                        <?= $estItem['media_trunca'] ?>
                                    </td>
                                    <td class="text-end">
                                        <a href="<?= $urlEstado ?>" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0" style="font-size: 0.72rem;">
                                            Ver estado &rarr;
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<style>
.card-kpi-anchor {
    color: inherit;
    transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.card-kpi-anchor:hover {
    color: inherit;
    transform: translateY(-3px);
}
.card-kpi-interactive {
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    cursor: pointer;
}
.card-kpi-anchor:hover .card-kpi-interactive {
    background-color: #ffffff !important;
    border-color: rgba(0, 122, 255, 0.35) !important;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.06);
}
.card-kpi-anchor:hover .bi-arrow-right {
    transform: translateX(4px);
    transition: transform 0.2s ease;
}
</style>
