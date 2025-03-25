<?php
namespace JiFramework\Core\Utilities\Pagination;

class PaginationHelper 
{
    /**
     * Generate pagination data.
     *
     * @param int   $itemsPerPage The number of items per page.
     * @param int   $totalItems   The total number of items.
     * @param array $options      Optional parameters:
     *                            - 'currentPage' (int): Current page number (default from $_GET['page']).
     *                            - 'queryParams' (array): Additional query parameters to include in pagination links.
     * @return object             An object containing pagination data.
     */
    public function paginate($itemsPerPage, $totalItems, $options = [])
    {
        // Determine the current page; default to 1 if not specified.
        $currentPage = isset($options['currentPage']) ? (int) $options['currentPage'] : (isset($_GET['page']) ? (int) $_GET['page'] : 1);
        $currentPage = max($currentPage, 1); // Ensure current page is at least 1

        // Calculate the total number of pages
        $totalPages = ceil($totalItems / $itemsPerPage);
        $totalPages = max($totalPages, 1); // Ensure total pages is at least 1

        // Calculate the next and previous page numbers
        $nextPage = min($currentPage + 1, $totalPages);
        $previousPage = max($currentPage - 1, 1);

        // Calculate the offset for database queries
        $offset = ($currentPage - 1) * $itemsPerPage;

        // Preserve existing query parameters, excluding 'page'
        $queryParams = isset($options['queryParams']) ? $options['queryParams'] : $_GET;
        unset($queryParams['page']);
        $queryParamString = http_build_query($queryParams);
        $queryParamString = !empty($queryParamString) ? "{$queryParamString}&" : "";

        // Package pagination data
        $paginationData = [
            'currentPage'   => $currentPage,
            'nextPage'      => $nextPage,
            'previousPage'  => $previousPage,
            'offset'        => $offset,
            'itemsPerPage'  => $itemsPerPage,
            'totalPages'    => $totalPages,
            'totalItems'    => $totalItems,
            'queryParams'   => $queryParamString,
        ];

        return (object) $paginationData;
    }

    /**
     * Generate HTML pagination links.
     *
     * @param object $paginationData The pagination data object from paginate().
     * @param string $baseUrl        The base URL for pagination links.
     * @param int    $maxPagesToShow The maximum number of page links to display.
     * @return string                HTML string containing pagination links.
     */
    public function renderPaginationLinks($paginationData, $baseUrl, $maxPagesToShow = 5)
    {
        $html = '';

        if ($paginationData->totalPages <= 1) {
            return $html; // No pagination needed
        }

        $currentPage = $paginationData->currentPage;
        $totalPages  = $paginationData->totalPages;
        $queryParams = $paginationData->queryParams;

        $startPage = max(1, $currentPage - intval($maxPagesToShow / 2));
        $endPage   = min($totalPages, $startPage + $maxPagesToShow - 1);

        // Adjust startPage if we're near the end
        if ($endPage - $startPage + 1 < $maxPagesToShow) {
            $startPage = max(1, $endPage - $maxPagesToShow + 1);
        }

        $html .= '<nav aria-label="Page navigation"><ul class="pagination">';

        // Previous page link
        if ($currentPage > 1) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . $queryParams . 'page=' . ($currentPage - 1) . '">&laquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
        }

        // Page number links
        for ($i = $startPage; $i <= $endPage; $i++) {
            if ($currentPage == $i) {
                $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
            } else {
                $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . $queryParams . 'page=' . $i . '">' . $i . '</a></li>';
            }
        }

        // Next page link
        if ($currentPage < $totalPages) {
            $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?' . $queryParams . 'page=' . ($currentPage + 1) . '">&raquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
        }

        $html .= '</ul></nav>';

        return $html;
    }
}


