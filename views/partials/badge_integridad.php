<?php
declare(strict_types=1);

use Tlatoa\Core\ViewHelper;

/**
 * @var array{
 *     sha256?: string|null,
 *     source_url?: string|null,
 *     archive_url?: string|null,
 *     fecha_extraccion?: string|null
 * } $evidencia
 */

$hash = $evidencia['sha256'] ?? null;
$sourceUrl = $evidencia['source_url'] ?? null;
$archiveUrl = $evidencia['archive_url'] ?? null;
$fecha = $evidencia['fecha_extraccion'] ?? date('Y-m-d');
?>
<div class="card bg-light border-0 shadow-sm rounded-3 p-3 my-3">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                <i class="bi bi-shield-check me-1"></i> Evidencia Verificada
            </span>
            <small class="text-muted">Preservación Criptográfica</small>
        </div>
        <small class="text-secondary font-monospace" style="font-size: 0.75rem;">
            Extracción: <?= ViewHelper::e($fecha) ?>
        </small>
    </div>

    <?php if (!empty($hash)): ?>
        <div class="mb-2">
            <small class="text-muted d-block" style="font-size: 0.7rem;">HUELLA DIGITAL (SHA-256):</small>
            <div class="p-2 bg-white rounded border font-monospace text-break user-select-all" style="font-size: 0.75rem;">
                <?= ViewHelper::e($hash) ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="d-flex flex-wrap gap-2 mt-1">
        <?php if (!empty($sourceUrl)): ?>
            <a href="<?= ViewHelper::e($sourceUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-link-45deg me-1"></i> Fuente Oficial
            </a>
        <?php endif; ?>

        <?php if (!empty($archiveUrl)): ?>
            <a href="<?= ViewHelper::e($archiveUrl) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-info text-dark">
                <i class="bi bi-archive me-1"></i> Respaldo Inmutable
            </a>
        <?php endif; ?>
    </div>
</div>
