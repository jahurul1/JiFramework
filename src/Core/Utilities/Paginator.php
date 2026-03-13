<?php
namespace JiFramework\Core\Utilities;

class Paginator
{
    // =========================================================================
    // Core calculation
    // =========================================================================

    /**
     * Calculate pagination metadata from a total item count.
     *
     * Use this when you already have a total count (e.g. from an array,
     * an API response, or a manual COUNT query). For database queries use
     * QueryBuilder::paginate() which handles both the count and data fetch.
     *
     * @param int   $perPage    Number of items per page.
     * @param int   $totalItems Total number of items across all pages.
     * @param array $options    Optional:
     *                          - 'currentPage' (int)   Override the current page (default: $_GET['page']).
     *                          - 'queryParams' (array) Extra query params to carry through page links.
     * @return object{currentPage:int, totalPages:int, totalItems:int, perPage:int, offset:int,
     *                hasNext:bool, hasPrevious:bool, nextPage:int, previousPage:int, queryParams:string}
     */
    public function paginate(int $perPage, int $totalItems, array $options = []): object
    {
        $perPage  = max(1, $perPage);

        // Current page — option override, then $_GET, then default 1
        $currentPage = isset($options['currentPage'])
            ? (int) $options['currentPage']
            : (isset($_GET['page']) ? (int) $_GET['page'] : 1);
        $currentPage = max(1, $currentPage);

        // Total pages — always at least 1
        $totalPages  = max(1, (int) ceil($totalItems / $perPage));
        $currentPage = min($currentPage, $totalPages);

        $offset = ($currentPage - 1) * $perPage;

        // Carry existing query params through page links (exclude 'page' itself)
        $queryParams = $options['queryParams'] ?? $_GET;
        unset($queryParams['page']);
        $queryParamString = !empty($queryParams)
            ? http_build_query($queryParams, '', '&amp;') . '&amp;'
            : '';

        return (object) [
            'currentPage'  => $currentPage,
            'totalPages'   => $totalPages,
            'totalItems'   => $totalItems,
            'perPage'      => $perPage,
            'offset'       => $offset,
            'hasNext'      => $currentPage < $totalPages,
            'hasPrevious'  => $currentPage > 1,
            'nextPage'     => min($currentPage + 1, $totalPages),
            'previousPage' => max($currentPage - 1, 1),
            'queryParams'  => $queryParamString,
        ];
    }

    // =========================================================================
    // HTML rendering
    // =========================================================================

    /**
     * Render Bootstrap pagination links as an HTML string.
     *
     * Accepts the object returned by either Paginator::paginate() or
     * QueryBuilder::paginate() — both return the same shape.
     *
     * Returns an empty string when there is only one page (nothing to render).
     *
     * @param object $paginationData Pagination metadata object.
     * @param string $baseUrl        Base URL for page links (e.g. "/users" or "/search").
     * @param int    $maxPagesToShow Maximum number of numbered page links in the window. Default: 5.
     * @return string HTML <nav> block, or empty string when totalPages <= 1.
     */
    public function renderLinks(object $paginationData, string $baseUrl, int $maxPagesToShow = 5): string
    {
        if ($paginationData->totalPages <= 1) {
            return '';
        }

        $currentPage = $paginationData->currentPage;
        $totalPages  = $paginationData->totalPages;
        $queryParams = $paginationData->queryParams;
        $base        = htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8');

        // Sliding window: centre the window on the current page
        $startPage = max(1, $currentPage - (int) floor($maxPagesToShow / 2));
        $endPage   = min($totalPages, $startPage + $maxPagesToShow - 1);

        // Shift start left if window is cut short at the end
        if ($endPage - $startPage + 1 < $maxPagesToShow) {
            $startPage = max(1, $endPage - $maxPagesToShow + 1);
        }

        $html = '<nav aria-label="Page navigation"><ul class="pagination">';

        // Previous button
        if ($paginationData->hasPrevious) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $base . '?' . $queryParams . 'page=' . ($currentPage - 1) . '">&laquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
        }

        // Numbered page links
        for ($i = $startPage; $i <= $endPage; $i++) {
            if ($i === $currentPage) {
                $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
            } else {
                $html .= '<li class="page-item"><a class="page-link" href="' . $base . '?' . $queryParams . 'page=' . $i . '">' . $i . '</a></li>';
            }
        }

        // Next button
        if ($paginationData->hasNext) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $base . '?' . $queryParams . 'page=' . ($currentPage + 1) . '">&raquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
        }

        $html .= '</ul></nav>';

        return $html;
    }
}
