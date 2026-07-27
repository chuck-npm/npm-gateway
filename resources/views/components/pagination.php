<?php

declare(strict_types=1);

$currentPage = isset($currentPage) ? max(1, (int) $currentPage) : 1;
$totalPages = isset($totalPages) ? max(1, (int) $totalPages) : 1;
$pageUrlPattern = isset($pageUrlPattern) ? (string) $pageUrlPattern : '?page=%d';
?>
<?php if ($totalPages > 1): ?>
    <nav class="gateway-pagination" aria-label="Pagination">
        <ul class="pagination gateway-pagination__list">
            <li class="page-item gateway-pagination__item<?= $currentPage === 1 ? ' disabled' : '' ?>">
                <a class="page-link gateway-pagination__link"
                   href="<?= htmlspecialchars(sprintf($pageUrlPattern, max(1, $currentPage - 1)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                   <?= $currentPage === 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Previous</a>
            </li>
            <?php for ($page = 1; $page <= $totalPages; $page++): ?>
                <li class="page-item gateway-pagination__item<?= $page === $currentPage ? ' active' : '' ?>">
                    <a class="page-link gateway-pagination__link"
                       href="<?= htmlspecialchars(sprintf($pageUrlPattern, $page), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                       <?= $page === $currentPage ? 'aria-current="page"' : '' ?>>
                        <?= $page ?>
                    </a>
                </li>
            <?php endfor; ?>
            <li class="page-item gateway-pagination__item<?= $currentPage === $totalPages ? ' disabled' : '' ?>">
                <a class="page-link gateway-pagination__link"
                   href="<?= htmlspecialchars(sprintf($pageUrlPattern, min($totalPages, $currentPage + 1)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
                   <?= $currentPage === $totalPages ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Next</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>
