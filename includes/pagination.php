<?php
function renderPagination($currentPage, $totalPages, $url) {
    if ($totalPages <= 1) return '';
    
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    // Adjust start and end pages to always show 5 pages when possible
    if ($endPage - $startPage < 4) {
        if ($startPage == 1) {
            $endPage = min($totalPages, 5);
        } else if ($endPage == $totalPages) {
            $startPage = max(1, $totalPages - 4);
        }
    }
    
    $html = '<nav aria-label="Page navigation" class="mt-4">';
    $html .= '<ul class="pagination justify-content-center">';
    
    // Previous button
    $prevDisabled = $currentPage <= 1 ? 'disabled' : '';
    $html .= '<li class="page-item ' . $prevDisabled . '">';
    $html .= '<a class="page-link rounded-circle mx-1" href="javascript:void(0)" data-page="' . ($currentPage - 1) . '" aria-label="Previous">';
    $html .= '<span aria-hidden="true">&laquo;</span>';
    $html .= '</a></li>';
    
    // First page
    if ($startPage > 1) {
        $html .= '<li class="page-item">';
        $html .= '<a class="page-link rounded-circle mx-1" href="javascript:void(0)" data-page="1">1</a>';
        $html .= '</li>';
        if ($startPage > 2) {
            $html .= '<li class="page-item disabled">';
            $html .= '<span class="page-link rounded-circle mx-1">...</span>';
            $html .= '</li>';
        }
    }
    
    // Page numbers
    for ($i = $startPage; $i <= $endPage; $i++) {
        $active = $i == $currentPage ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '">';
        $html .= '<a class="page-link rounded-circle mx-1" href="javascript:void(0)" data-page="' . $i . '">' . $i . '</a>';
        $html .= '</li>';
    }
    
    // Last page
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<li class="page-item disabled">';
            $html .= '<span class="page-link rounded-circle mx-1">...</span>';
            $html .= '</li>';
        }
        $html .= '<li class="page-item">';
        $html .= '<a class="page-link rounded-circle mx-1" href="javascript:void(0)" data-page="' . $totalPages . '">' . $totalPages . '</a>';
        $html .= '</li>';
    }
    
    // Next button
    $nextDisabled = $currentPage >= $totalPages ? 'disabled' : '';
    $html .= '<li class="page-item ' . $nextDisabled . '">';
    $html .= '<a class="page-link rounded-circle mx-1" href="javascript:void(0)" data-page="' . ($currentPage + 1) . '" aria-label="Next">';
    $html .= '<span aria-hidden="true">&raquo;</span>';
    $html .= '</a></li>';
    
    $html .= '</ul></nav>';
    
    return $html;
}
?> 